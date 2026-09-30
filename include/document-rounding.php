<?php

include_once __DIR__.'/../core.php';

use Common\DocumentRounding;
use Models\Module;
use Modules\Contratti\Contratto;
use Modules\Fatture\Fattura;
use Modules\Fatture\Stato as StatoFattura;
use Modules\Iva\Aliquota;
use Modules\Preventivi\Preventivo;

Permissions::check('rw');

$id_module = (int) get('id_module');
$id_record = (int) get('id_record');
$module = Module::find($id_module);
$defaultMode = DocumentRounding::defaultMode();

if (!$module || !$id_record || $defaultMode === null) {
    echo '<div class="alert alert-info"><i class="fa fa-info-circle"></i> '.tr('Arrotondamento non disponibile.').'</div>';
    return;
}

$document = null;
$isInvoice = false;
$blockedReasons = [];
$vatLocked = false;
$accountId = null;

switch ((string) $module->directory) {
    case 'preventivi':
        $document = Preventivo::find($id_record);
        if (!$document || !empty($document->stato->is_bloccato)) {
            echo '<div class="alert alert-warning"><i class="fa fa-lock"></i> '.tr('Preventivo non modificabile.').'</div>';
            return;
        }
        if (DocumentRounding::hasSections($document)) {
            $blockedReasons[] = 'sectioned_quote';
        }
        break;

    case 'contratti':
        $document = Contratto::find($id_record);
        if (!$document || !empty($document->stato->is_bloccato)) {
            echo '<div class="alert alert-warning"><i class="fa fa-lock"></i> '.tr('Contratto non modificabile.').'</div>';
            return;
        }
        break;

    case 'fatture':
        if ($module->name !== 'Fatture di vendita') {
            break;
        }
        $document = Fattura::find($id_record);
        $draftId = (int) StatoFattura::where('name', 'Bozza')->value('id');
        if (!$document || $document->direzione !== 'entrata' || (int) $document->id_stato !== $draftId) {
            echo '<div class="alert alert-warning"><i class="fa fa-lock"></i> '.tr('Disponibile solo sulle fatture di vendita in Bozza.').'</div>';
            return;
        }
        $isInvoice = true;
        $blockedReasons = array_merge($blockedReasons, DocumentRounding::invoiceGuard($document));
        $vatLocked = !empty($document->id_dichiarazione_intento);
        break;
}

if (!$document) {
    echo '<div class="alert alert-warning"><i class="fa fa-info-circle"></i> '.tr('Arrotondamento non disponibile per questo documento.').'</div>';
    return;
}

$dir = (string) $document->direzione;
$fallbackVat = $vatLocked
    ? (int) setting("Iva per lettere d'intento")
    : (int) ($document->anagrafica?->id_iva_vendite ?: setting('Iva predefinita'));
$context = DocumentRounding::context($document, $fallbackVat ?: null);
$existing = $context['existing'];

if ($context['duplicate_count'] > 1) {
    $blockedReasons[] = 'duplicate_rounding';
}

$selectedVatId = $vatLocked ? $fallbackVat : (int) ($context['default_vat_id'] ?: 0);
if ($isInvoice) {
    $accountId = $existing && !empty($existing->id_conto)
        ? (int) $existing->id_conto
        : (int) setting('Conto predefinito fatture di vendita');
    if (!$accountId) {
        $blockedReasons[] = 'missing_account';
    }
}

$vatIds = array_values(array_unique(array_filter(array_map('intval', $context['vat_ids']))));
if ($selectedVatId && !in_array($selectedVatId, $vatIds, true)) {
    $vatIds[] = $selectedVatId;
}
if ($vatLocked) {
    $vatIds = $selectedVatId ? [$selectedVatId] : [];
}

$vatChoices = [];
$plans = [];
$pricesIncludeVat = (bool) setting('Utilizza prezzi di vendita comprensivi di IVA');
foreach ($vatIds as $vatId) {
    $vat = Aliquota::find($vatId);
    if (!$vat) {
        continue;
    }

    $title = trim((string) $vat->getTranslation('title'));
    $rate = (float) $vat->percentuale;
    $rateText = rtrim(rtrim(number_format($rate, 3, ',', '.'), '0'), ',').'%';
    $vatChoices[$vatId] = [
        'id' => $vatId,
        'text' => $title === '' ? $rateText : (str_contains($title, '%') ? $title : $title.' ('.$rateText.')'),
    ];

    foreach (['down', 'nearest', 'up'] as $mode) {
        try {
            $plan = DocumentRounding::solve($context, $rate, $pricesIncludeVat, $mode);
            $plans[$vatId][$mode] = [
                'input' => number_format((float) $plan['input'], DocumentRounding::INPUT_DECIMALS, '.', ''),
                'target' => (float) $plan['target'],
                'difference' => (float) $plan['difference'],
                'adjustment' => (float) $plan['adjustment'],
                'requires_adjustment' => (bool) $plan['requires_adjustment'],
            ];
        } catch (Throwable) {
            $plans[$vatId][$mode] = null;
        }
    }
}

if (!$vatChoices) {
    $blockedReasons[] = 'missing_vat';
}
if (count($vatChoices) === 1 && !$selectedVatId) {
    $selectedVatId = (int) array_key_first($vatChoices);
}

$reasonLabels = [
    'sectioned_quote' => tr('Il preventivo contiene sezioni: il totale generale non viene arrotondato per non alterare il subtotale dell’ultima sezione.'),
    'credit_note' => tr('Le note di credito non sono supportate.'),
    'electronic_invoice_locked' => tr('Lo stato della fattura elettronica non consente modifiche.'),
    'percentage_collection_fee' => tr('Sono presenti spese d’incasso percentuali.'),
    'automatic_stamp_duty' => tr('È attivo il bollo automatico.'),
    'final_discount' => tr('È presente uno sconto finale sul documento.'),
    'withholdings_or_social_security' => tr('Sono presenti ritenute o cassa previdenziale.'),
    'duplicate_rounding' => tr('Sono presenti più righe di arrotondamento: rimuovere i duplicati prima di procedere.'),
    'missing_account' => tr('Imposta il conto predefinito delle fatture di vendita.'),
    'missing_vat' => tr('Nessuna aliquota IVA disponibile.'),
];

$format = static function (float $value, int $decimals): string {
    return Translator::numberToLocale(abs($value) < (0.5 / (10 ** $decimals)) ? 0 : $value, $decimals).' '.currency();
};

$initial = $selectedVatId ? ($plans[$selectedVatId][$defaultMode] ?? null) : null;
$currentText = $format((float) $context['source_total'], 3);
$targetText = $initial ? $format((float) $initial['target'], 2) : '—';
$differenceText = $initial ? $format((float) $initial['difference'], 3) : '—';
$canApply = empty($blockedReasons) && $initial && $initial['requires_adjustment'];
$action = base_path_osm().'/editor.php?id_module='.$id_module.'&id_record='.$id_record;

if ($blockedReasons) {
    echo '<div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i><ul class="mb-0">';
    foreach (array_unique($blockedReasons) as $reason) {
        echo '<li>'.($reasonLabels[$reason] ?? tr('Documento non arrotondabile.')).'</li>';
    }
    echo '</ul></div>';
}

echo '<form action="'.$action.'" method="post" id="document-rounding-form">
<input type="hidden" name="backto" value="record-edit">
<input type="hidden" name="op" value="manage_sconto">
<input type="hidden" name="idriga" value="'.($existing ? (int) $existing->id : '').'">
<input type="hidden" name="dir" value="'.htmlspecialchars($dir, ENT_QUOTES, 'UTF-8').'">
<input type="hidden" name="descrizione" value="'.DocumentRounding::DESCRIPTION.'">
<input type="hidden" name="note" value="">
<input type="hidden" name="sconto_unitario" data-role="rounding-input" value="'.($initial['input'] ?? '').'">';

if ($isInvoice) {
    echo '<input type="hidden" name="id_conto" value="'.(int) $accountId.'">
<input type="hidden" name="calcolo_ritenuta_acconto" value="">
<input type="hidden" name="id_ritenuta_acconto" value="">
<input type="hidden" name="ritenuta_contributi" value="0">
<input type="hidden" name="id_rivalsa_inps" value="">';
}

$fixedVat = count($vatChoices) === 1 || $vatLocked;
if ($fixedVat && $selectedVatId) {
    echo '<input type="hidden" name="id_iva" data-role="fixed-vat" value="'.$selectedVatId.'">';
}

echo '<div class="card card-outline card-primary mb-3"><div class="card-body"><div class="row align-items-end">
<div class="col-md-8"><label>'.tr('Modalità').'</label><div class="btn-group btn-group-toggle d-flex" data-toggle="buttons">';
foreach (['down' => ['fa-arrow-down', tr('Euro inferiore')], 'nearest' => ['fa-bullseye', tr('Euro più vicino')], 'up' => ['fa-arrow-up', tr('Euro superiore')]] as $mode => $data) {
    $active = $defaultMode === $mode ? 'btn-primary active' : 'btn-default';
    echo '<label class="btn '.$active.' flex-fill" data-role="mode-button"><input type="radio" name="rounding_mode" value="'.$mode.'" '.($defaultMode === $mode ? 'checked' : '').'> <i class="fa '.$data[0].'"></i> '.$data[1].'</label>';
}
echo '</div></div><div class="col-md-4"><label>'.tr('IVA').'</label>';

if ($fixedVat) {
    echo '<input type="text" class="form-control" readonly value="'.htmlspecialchars((string) ($vatChoices[$selectedVatId]['text'] ?? ''), ENT_QUOTES, 'UTF-8').'">';
} else {
    echo '{[ "type": "select", "name": "id_iva", "id": "rounding-vat", "values": '.json_encode(array_values($vatChoices), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).', "value": "'.($selectedVatId ?: '').'", "required": 1 ]}';
}

echo '</div></div></div></div>
<div class="card card-outline card-secondary mb-3"><div class="card-body p-0"><div class="row no-gutters">
<div class="col-md-4 p-3 border-right"><div class="text-muted small">'.tr('Totale attuale').'</div><div class="h4 mb-0">'.$currentText.'</div></div>
<div class="col-md-4 p-3 border-right bg-light"><div class="text-muted small">'.tr('Totale arrotondato').'</div><div class="h4 mb-0 text-primary font-weight-bold" data-role="target">'.$targetText.'</div></div>
<div class="col-md-4 p-3"><div class="text-muted small">'.tr('Rettifica').'</div><div class="h4 mb-0" data-role="difference">'.$differenceText.'</div></div>
</div></div></div>
<div class="row"><div class="col-sm-6">';
if ($existing) {
    echo '<button type="button" class="btn btn-danger" id="remove-rounding"><i class="fa fa-trash"></i> '.tr('Rimuovi').'</button>';
}
echo '</div><div class="col-sm-6 text-right"><button type="button" class="btn btn-default" data-dismiss="modal">'.tr('Annulla').'</button> <button type="button" class="btn btn-success" id="apply-rounding" '.($canApply ? '' : 'disabled').'><i class="fa fa-check"></i> '.tr('Applica').'</button></div></div>
</form>';

if ($existing) {
    echo '<form action="'.$action.'" method="post" id="remove-rounding-form" class="d-none"><input type="hidden" name="backto" value="record-edit"><input type="hidden" name="op" value="delete_riga"><input type="hidden" name="righe[]" value="'.(int) $existing->id.'"></form>';
}

$plansJson = json_encode($plans, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
$symbol = json_encode((string) currency(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$staticCanApply = empty($blockedReasons) ? 'true' : 'false';
$fixedVatJs = $fixedVat ? 'true' : 'false';

echo '<script>
(function(){
const plans='.$plansJson.', symbol='.$symbol.', staticCanApply='.$staticCanApply.', fixedVat='.$fixedVatJs.';
const form=$("#document-rounding-form"), apply=$("#apply-rounding"), vat=$("#rounding-vat");
function vatId(){return String(fixedVat ? form.find("[data-role=fixed-vat]").val() : (vat.val()||""));}
function mode(){return form.find("input[name=rounding_mode]:checked").val()||"nearest";}
function fmt(value,decimals){let n=Number(value); if(Math.abs(n)<0.5/Math.pow(10,decimals))n=0; return n.toLocaleString(undefined,{minimumFractionDigits:decimals,maximumFractionDigits:decimals})+" "+symbol;}
function refresh(){form.find("[data-role=mode-button]").removeClass("btn-primary").addClass("btn-default"); form.find("input[name=rounding_mode]:checked").closest("[data-role=mode-button]").removeClass("btn-default").addClass("btn-primary"); const p=plans[vatId()]?plans[vatId()][mode()]:null; if(!p){form.find("[data-role=target],[data-role=difference]").text("—"); apply.prop("disabled",true); return;} form.find("[data-role=target]").text(fmt(p.target,2)); form.find("[data-role=difference]").text(fmt(p.difference,3)); form.find("[data-role=rounding-input]").val(p.input); apply.prop("disabled",!staticCanApply||!p.requires_adjustment);}
form.on("change","input[name=rounding_mode]",refresh); vat.on("change",refresh);
apply.on("click",function(){refresh(); if($(this).prop("disabled"))return; salvaForm("#document-rounding-form",{id_module:"'.$id_module.'",id_record:"'.$id_record.'"}).then(function(){form.closest("div[id^=bs-popup]").modal("hide"); if(typeof caricaRighe==="function")caricaRighe(null);});});
$("#remove-rounding").on("click",function(){salvaForm("#remove-rounding-form",{id_module:"'.$id_module.'",id_record:"'.$id_record.'"}).then(function(){form.closest("div[id^=bs-popup]").modal("hide"); if(typeof caricaRighe==="function")caricaRighe(null);});});
refresh();
})();
</script>';
