<?php

include_once __DIR__.'/../../core.php';
include_once __DIR__.'/modutil.php';

if (Auth::admin() || $_SESSION['gruppo'] != 'Tecnici') {
    $costi = get_costi_intervento($id_record);

    echo '
<!-- Riepilogo dei costi -->
<table class="table table condensed table-striped table-hover table-bordered">
    <tr>
        <th width="40%"></th>
        <th width="20%" class="text-center">'.strtoupper(tr('Costo')).'</th>
        <th width="20%" class="text-center">'.strtoupper(tr('Addebito')).'</th>
        <th width="20%" class="text-center">'.strtoupper(tr('Tot. Scontato')).'</th>
    </tr>

    <tr>
        <th>'.strtoupper(tr('Totale manodopera')).'</th>
        <td class="text-right">'.Translator::numberToLocale($costi['manodopera_costo']).' &euro;</td>
        <td class="text-right">'.Translator::numberToLocale($costi['manodopera_addebito']).' &euro;</td>
        <td class="text-right">'.Translator::numberToLocale($costi['manodopera_scontato']).' &euro;</td>
    </tr>

    <tr>
        <th>'.strtoupper(tr('Totale viaggio')).'</th>
        <td class="text-right">'.Translator::numberToLocale($costi['viaggio_costo']).' &euro;</td>
        <td class="text-right">'.Translator::numberToLocale($costi['viaggio_addebito']).' &euro;</td>
        <td class="text-right">'.Translator::numberToLocale($costi['viaggio_scontato']).' &euro;</td>
    </tr>

    <tr>
        <th>'.strtoupper(tr('Totale articoli')).'</th>
        <td class="text-right">'.Translator::numberToLocale($costi['ricambi_costo']).' &euro;</td>
        <td class="text-right">'.Translator::numberToLocale($costi['ricambi_addebito']).' &euro;</td>
        <td class="text-right">'.Translator::numberToLocale($costi['ricambi_scontato']).' &euro;</td>
    </tr>

    <tr>
        <th>'.strtoupper(tr('Totale altre spese')).'</th>
        <td class="text-right">'.Translator::numberToLocale($costi['altro_costo']).' &euro;</td>
        <td class="text-right">'.Translator::numberToLocale($costi['altro_addebito']).' &euro;</td>
        <td class="text-right">'.Translator::numberToLocale($costi['altro_scontato']).' &euro;</td>
    </tr>

    <tr>
        <th>'.strtoupper(tr('Sconto totale')).'</th>
        <td class="text-right">-</td>
        <td class="text-right">-</td>
        <td class="text-right">'.Translator::numberToLocale(-$costi['sconto_globale']).' &euro;</td>
    </tr>

    <tr>
        <th>'.strtoupper(tr('Totale')).'</th>
        <th class="text-right">'.Translator::numberToLocale($costi['totale_costi']).' &euro;</th>
        <th class="text-right">'.Translator::numberToLocale($costi['totale_addebito']).' &euro;</th>
        <th class="text-right">'.Translator::numberToLocale($costi['totale_manodopera']).' &euro;</th>
    </tr>
</table>';
}

// Lettura dello sconto globale
$rss = $dbo->fetchArray('SELECT sconto_globale, tipo_sconto_globale FROM in_interventi WHERE id='.prepare($id_record));
$sconto = $rss[0]['sconto_globale'];
$tipo_sconto = $rss[0]['tipo_sconto_globale'];

echo '

<!-- SCONTO -->
<div class="row">
    <div class="col-md-4 pull-right">
        {[ "type": "number", "label": "'.tr('Sconto globale').'", "name": "sconto_globale", "value": "'.$sconto.'", "icon-after": "choice|untprc|'.$tipo_sconto.'" ]}
    </div>
</div>';
