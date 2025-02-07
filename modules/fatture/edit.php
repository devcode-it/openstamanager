<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

use Carbon\Carbon;
use Models\Module;
use Models\Plugin;
use Modules\Anagrafiche\Anagrafica;
use Modules\DDT\Stato as StatoDDT;
use Modules\Fatture\Fattura;
use Modules\Fatture\Gestori\Bollo;
use Modules\Fatture\Stato as StatoFattura;
use Modules\Interventi\Intervento;
use Modules\Iva\Aliquota;
use Modules\Ordini\Stato as StatoOrdine;
use Plugins\AssicurazioneCrediti\AssicurazioneCrediti;
use Plugins\ExportFE\Interaction;

include_once __DIR__.'/../../core.php';

$anagrafica_azienda = Anagrafica::find(setting('Azienda predefinita'));

$id_stato_bozza = StatoFattura::where('name', 'Bozza')->first()->id;
$id_stato_emessa = StatoFattura::where('name', 'Emessa')->first()->id;
$id_stato_pagato = StatoFattura::where('name', 'Pagato')->first()->id;
$id_stato_parz_pagato = StatoFattura::where('name', 'Parzialmente pagato')->first()->id;
$id_stato_non_valida = StatoFattura::where('name', 'Non valida')->first()->id;
$id_stato_annullata = StatoFattura::where('name', 'Annullata')->first()->id;

$id_modulo_anagrafiche = Module::where('name', 'Anagrafiche')->first()->id;

$block_edit = !empty($note_accredito) || in_array($fattura->stato->id, [$id_stato_parz_pagato, $id_stato_pagato, $id_stato_emessa]) || !$abilita_genera;

if ($dir == 'entrata') {
    $conto = 'vendite';
} else {
    $conto = 'acquisti';
}

// Informazioni sulla dichiarazione d'intento, visibili solo finché la fattura è in bozza
if ($dir == 'entrata' && !empty($fattura->dichiarazione)) {
    $diff = $fattura->dichiarazione->massimale - $fattura->dichiarazione->totale;
    $diff_in_days = Carbon::parse($fattura->dichiarazione->data_fine)->diffAsCarbonInterval($fattura->data);

    $id_iva = setting("Iva per lettere d'intento");
    $iva = Aliquota::find($id_iva);

    if (!empty($iva)) {
        if ($diff == 0) {
            echo '
<div class="alert alert-info">
    <i class="fa fa-info"></i> '.tr("La dichiarazione d'intento _PROTOCOLLO_ ha raggiunto il massimale previsto di _MONEY_.", [
                '_MONEY_' => moneyFormat(abs($fattura->dichiarazione->massimale)),
                '_PROTOCOLLO_' => $fattura->dichiarazione->numero_protocollo,
            ]).'.</b>
</div>';
        } elseif ($diff < 0) {
            echo '
<div class="alert alert-warning">
    <i class="fa fa-warning"></i> '.tr("La dichiarazione d'intento _PROTOCOLLO_ ha superato il massimale previsto di _MONEY_.", [
                '_MONEY_' => moneyFormat(abs($diff)),
                '_PROTOCOLLO_' => $fattura->dichiarazione->numero_protocollo,
            ]).'.</b>
</div>';
        } elseif ($diff_in_days < 0) {
            echo '
<div class="alert alert-warning">
    <i class="fa fa-warning"></i> '.tr("La dichiarazione d'intento _PROTOCOLLO_ ha come data fine validità _SCADENZA_ mentre la fattura ha data _DATA_", [
                '_SCADENZA_' => dateFormat($fattura->dichiarazione->data_fine),
                '_DATA_' => dateFormat($fattura->data),
                '_PROTOCOLLO_' => $fattura->dichiarazione->numero_protocollo,
            ]).'.</b>
</div>';
        }
    } else {
        // TODO link ad impostazioni con nuova ricerca rapida
        echo '
<div class="alert alert-warning">
    <i class="fa fa-warning"></i> '.tr("Attenzione nessuna aliq. IVA definita per la dichiarazione d'intento. _SETTING_", [
            '_SETTING_' => Modules::link('Impostazioni', null, tr('Selezionala dalle impostazioni'), true, null, true, null, "&search=Iva per lettere d'intento"),
        ]).'
</div>';
    }
}

// Autofattura
if (!empty($fattura_acquisto_originale)) {
    echo '
<div class="alert alert-info">
    <i class="fa fa-info"></i> '.tr("Questa è un'autofattura generata da una fattura di acquisto").':
    <b>'.Modules::link('Fatture di acquisto', $fattura_acquisto_originale->id, tr('Fattura num. _NUM_ del _DATE_', [
        '_NUM_' => $fattura_acquisto_originale->numero_esterno,
        '_DATE_' => dateFormat($fattura_acquisto_originale->data),
    ])).'</b>
</div>';
}

if ($abilita_autofattura) {
    echo '
<div class="alert alert-info">
    <i class="fa fa-info-circle"></i> '.tr("Per questa fattura è prevista la generazione di un'autofattura tramite <b>Crea » Autofattura</b>").'.
</div>';
} elseif ($fattura->id_autofattura != null) {
    echo '
<div class="alert alert-info">
    <i class="fa fa-info"></i> '.tr("È presente un'autofattura collegata").':
    <b>'.Modules::link('Fatture di vendita', $fattura->id_autofattura, tr('Fattura num. _NUM_ del _DATE_', [
        '_NUM_' => $autofattura_vendita->numero_esterno,
        '_DATE_' => dateFormat($autofattura_vendita->data),
    ])).'</b>
</div>';
} elseif ($autofattura_collegata != null) {
    echo '
<div class="alert alert-info">
    <i class="fa fa-info"></i> '.tr('Questa autofattura è già stata importata come fattura di acquisto').':
    <b>'.Modules::link('Fatture di acquisto', $autofattura_collegata->id, tr('Fattura num. _NUM_ del _DATE_', [
        '_NUM_' => $autofattura_collegata->numero_esterno,
        '_DATE_' => dateFormat($autofattura_collegata->data),
    ])).'</b>
</div>';
}

// Note di credito collegate
if (!empty($note_accredito)) {
    echo '
<div class="alert alert-info text-center">'.tr('Note di credito collegate').':';
    foreach ($note_accredito as $nota) {
        $text = tr('Rif. fattura _NUM_ del _DATE_', [
            '_NUM_' => $nota['numero'],
            '_DATE_' => Translator::dateToLocale($nota['data']),
        ]);

        echo '
    <br>'.Modules::link($dir == 'entrata' ? 'Fatture di vendita' : 'Fatture di acquisto', $nota['id'], $text, $text);
    }
    echo '
</div>';
}

// Fattura originale della Nota di credito
if (!empty($fattura->ref_documento) && $fattura->isNota()) {
    $nota = Fattura::find($fattura->ref_documento);
    echo '
<div class="alert alert-info">
    <i class="fa fa-info"></i> '.tr('Questa è una _TIPO_ generata dalla seguente fattura', [
        '_TIPO_' => $fattura->tipo->getTranslation('title'),
    ]).':
    <b>'.Modules::link($module->getTranslation('title'), $fattura->ref_documento, tr('Fattura num. _NUM_ del _DATE_', [
        '_NUM_' => $nota->numero_esterno,
        '_DATE_' => dateFormat($nota->data),
    ])).'</b>
</div>';
}

// Ricordo che si sta emettendo una fattura conto terzi
if ($dir == 'entrata' && $fattura->stato->id == $id_stato_bozza) {
    if ($fattura->is_fattura_conto_terzi) {
        echo '
<div class="alert alert-info">
    <i class="fa fa-info"></i> '.tr("Questa è una fattura per conto di terzi. Nell'XML della Fattura Elettronica sarà indicato il fornitore _FORNITORE_ come cessionario e il cliente come cedente/prestatore", ['_FORNITORE_' => '"<b>'.stripslashes((string) $database->fetchOne('SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica = '.prepare(setting('Azienda predefinita')))['ragione_sociale']).'</b>"']).'.</b>
</div>';
    }

    $assicurazione_crediti = AssicurazioneCrediti::where('id_anagrafica', $fattura->idanagrafica)->where('data_inizio', '<=', $fattura->data)->where('data_fine', '>=', $fattura->data)->first();
    if (!empty($assicurazione_crediti)) {
        if (($assicurazione_crediti->totale + $fattura->totale) >= $assicurazione_crediti->fido_assicurato) {
            echo '
<div class="alert alert-warning text-center">
    <i class="fa fa-exclamation-triangle"></i> '.tr('Attenzione! Il fido assicurato per questo cliente è stato superato!').'<br>('.moneyFormat($assicurazione_crediti->totale + $fattura->totale, 2).' / '.moneyFormat($assicurazione_crediti->fido_assicurato, 2).')
    '.Plugins::link('Assicurazione crediti', $fattura->idanagrafica).'
</div>';
        }
    }
}
// Verifica aggiuntive sulla sequenzialità dei numeri
if ($dir == 'entrata') {
    // Calcolo il numero previsto solo se la data della fattura è maggiore o uguale all'impostazione "Data inizio verifica contatore fattura di vendita" oppure l'impostazione non è valorizzata.
    if (!empty(setting('Data inizio verifica contatore fattura di vendita'))) {
        $dateFormat = 'd/m/Y';
        $carbonDate = Carbon::createFromFormat($dateFormat, setting('Data inizio verifica contatore fattura di vendita'));
        $data_inizio_verifica_contatore = (($carbonDate !== false) ? strtotime($carbonDate->format('Y-m-d')) : null);
    }
    $data = ($fattura->data ? strtotime($fattura->data) : '');
    if ($data >= $data_inizio_verifica_contatore || empty($data_inizio_verifica_contatore)) {
        $numero_previsto = verifica_numero_fattura($fattura);
    }

    if (!empty($numero_previsto)) {
        echo '
<div class="alert alert-warning">
    <i class="fa fa-warning"></i> '.tr("E' assente una fattura di vendita di numero _NUM_ in data precedente o corrispondente a _DATE_: si potrebbero verificare dei problemi con la numerazione corrente delle fatture", [
            '_DATE_' => dateFormat($fattura->data),
            '_NUM_' => '"'.$numero_previsto.'"',
        ]).'.</b>
</div>';
    }

    $data_fattura = new DateTime($fattura->data);
    $data_odierna = new DateTime();
    $differenza = $data_odierna->diff($data_fattura)->days;

    if ($fattura->codice_stato_fe == 'NS' && $fattura->stato->id != $id_stato_non_valida && ($differenza > setting('Giorni validità fattura scartata'))) {
        echo '
<div class="alert alert-danger">
    <i class="fa fa-warning"></i> '.tr('Questa fattura è stata scartata e sono trascorsi i termini di reinvio, è necessario invalidare il documento.').'</b>
    <button type="button" class="btn btn-xs btn-success" onclick="risolviStato()"><i class="fa fa-cog"></i> '.tr('Invalida il documento').'</button>
</div>';
    }

    // Verifica la data dell'intervento rispetto alla data della fattura
    $fatturazione_futura = false;
    $data_fattura = new Carbon($fattura->data);
    $interventi_collegati = $fattura->getDocumentiCollegati()[Intervento::class];
    if (!empty($interventi_collegati)) {
        foreach ($interventi_collegati as $intervento) {
            $fine_intervento = $intervento->fine;
            $fine_intervento = new Carbon($fine_intervento);

            if ($fine_intervento->diffInDays($data_fattura, false) < 0) {
                $fatturazione_futura = true;
                break;
            }
        }

        if ($fatturazione_futura) {
            echo '
<div class="alert alert-warning">
    <i class="fa fa-warning"></i> '.tr("Stai fatturando un'attività futura rispetto alla data di fatturazione.").'</b>
</div>';
        }
    }
}

$righe = $fattura->getRighe();
$righe_vuote = false;
foreach ($righe as $riga) {
    if ($riga->qta == 0) {
        $righe_vuote = true;
    }
}
if ($righe_vuote) {
    echo '
<div class="alert alert-warning" id="righe-vuote">
    <i class="fa fa-warning"></i> '.tr('Nel documento sono presenti delle righe con quantità a 0.').'</b>
</div>';
}

$query = 'SELECT `co_statidocumento`.*, `co_statidocumento`.`id` AS id, `colore` AS _bgcolor_, `co_statidocumento_lang`.`title` as descrizione FROM `co_statidocumento` LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento_lang`.`id_record` = `co_statidocumento`.`id` AND `co_statidocumento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')';
if (empty($record['is_fiscale'])) {
    $query .= " WHERE `co_statidocumento`.`id` = $id_stato_bozza";

    $plugin = $dbo->fetchArray('SELECT `zz_plugins`.`id` FROM `zz_plugins` LEFT JOIN `zz_plugins_lang` ON (`zz_plugins`.`id` = `zz_plugins_lang`.`id_record` AND `zz_plugins_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).") WHERE `title`='Fatturazione Elettronica' AND `idmodule_to` = ".prepare($id_module));
    echo '<script>$("#link-tab_'.$plugin[0]['id'].'").addClass("disabled");</script>';
}
// Forzo il passaggio della fattura da Bozza ad Emessa per il corretto calcolo del numero.
elseif ($fattura->stato->id == $id_stato_bozza) {
    $query .= ' WHERE `co_statidocumento`.`id` IN ('.$id_stato_emessa.', '.$id_stato_bozza.')';
}

$query .= ' ORDER BY `title`';

?>
<form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">


    <div class="row">
    <?php if ($dir == 'entrata') { ?>
        <div class="col-md-2 offset-md-8" <?php echo ($record['is_fiscale']) ? '' : 'hidden'; ?> >
            {[ "type": "select", "label": "<?php echo tr('Stato FE'); ?>", "name": "codice_stato_fe", "values": "query=SELECT `codice` as id, CONCAT_WS(' - ',`codice`, `title`) as text FROM `fe_stati_documento` LEFT JOIN `fe_stati_documento_lang` ON (`fe_stati_documento_lang`.`id_record` = `fe_stati_documento`.`codice` AND `fe_stati_documento_lang`.`id_lang` = <?php echo prepare(Models\Locale::getDefault()->id); ?>)", "value": "$codice_stato_fe$", "disabled": <?php echo intval(Interaction::isEnabled() || ($fattura->stato->id == $id_stato_bozza && $abilita_genera)); ?>, "class": "unblockable", "help": "<?php echo (!empty($record['data_stato_fe'])) ? Translator::timestampToLocale($record['data_stato_fe']) : ''; ?>" ]}
        </div>

    <?php }
    echo '
        <div class="col-md-2'.($dir == 'uscita' ? ' offset-md-10' : '').'">
            {[ "type": "select", "label": "'.tr('Stato').'", "name": "idstatodocumento", "required": 1, "values": "query='.$query.'", "value": "'.$fattura->stato->id.'", "class": "'.(($fattura->stato->id != $id_stato_bozza && !$abilita_genera) ? '' : 'unblockable').'", "extra": "onchange=\"return cambiaStato()\"" ]}
        </div>
    </div>

	<!-- DATI INTESTAZIONE -->
    <div class="card card-primary collapsable">
        <div class="card-header with-border">
            <h3 class="card-title">'.($dir == 'entrata' ? tr('Dati cliente') : tr('Dati fornitore')).'</h3>
            <div class="card-tools pull-right">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fa fa-minus"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <!-- RIGA 1 -->
            <div class="row">
                <div class="col-md-3">
                    '.Modules::link('Anagrafiche', $record['idanagrafica'], null, null, 'class="pull-right"');

if ($dir == 'entrata') {
    ?>
						{[ "type": "select", "label": "<?php echo tr('Cliente'); ?>", "name": "idanagrafica", "required": 1, "ajax-source": "clienti", "help": "<?php echo tr("In caso di autofattura indicare l'azienda: ").stripslashes((string) $database->fetchOne('SELECT `ragione_sociale` FROM `an_anagrafiche` WHERE `idanagrafica` = '.prepare(setting('Azienda predefinita')))['ragione_sociale']); ?>", "value": "$idanagrafica$" ]}
<?php
} else {
    ?>
						{[ "type": "select", "label": "<?php echo tr('Fornitore'); ?>", "name": "idanagrafica", "required": 1, "ajax-source": "fornitori", "value": "$idanagrafica$" ]}
<?php
}

echo '
                </div>';

if ($dir == 'entrata') {
    echo '
				<div class="col-md-3">';
    if ($record['idagente'] != 0) {
        echo Modules::link('Anagrafiche', $record['idagente_fattura'], null, null, 'class="pull-right"');
    }
    echo '
					{[ "type": "select", "label": "'.tr('Agente di riferimento').'", "name": "idagente", "ajax-source": "agenti", "select-options": {"idanagrafica": '.$record['idanagrafica'].'}, "value": "$idagente_fattura$" ]}
				</div>';
}

echo '
                <div class="col-md-3">';
if (!empty($record['idreferente'])) {
    echo Plugins::link('Referenti', $record['idanagrafica'], null, null, 'class="pull-right"');
}
$id_modulo_anagrafiche = Module::where('name', 'Anagrafiche')->first()->id;
echo '
                    {[ "type": "select", "label": "'.tr('Referente').'", "name": "idreferente", "value": "$idreferente$", "ajax-source": "referenti", "select-options": {"idanagrafica": '.$record['idanagrafica'].', "idsede_destinazione": '.($dir == 'entrata' ? $record['idsede_destinazione'] : $record['idsede_partenza']).'}, "icon-after": "add|'.$id_modulo_anagrafiche.'|id_plugin='.Plugin::where('name', 'Referenti')->first()->id.'&id_parent='.$record['idanagrafica'].'||'.(intval($block_edit) ? 'disabled' : '').'" ]}
                </div>
            </div>
        </div>
    </div>';
?>
	<div class="card card-primary">
		<div class="card-header">
			<h3 class="card-title"><?php echo tr('Intestazione'); ?></h3>
		</div>

		<div class="card-body">
			<div class="row">
                
                <!-- id_segment -->
				{[ "type": "hidden", "label": "Segmento", "name": "id_segment", "class": "text-center", "value": "$id_segment$" ]}

                <?php
                if ($dir == 'uscita') {
                    echo '
                <div class="col-md-2">
                    {[ "type": "text", "label": "'.tr('Numero fattura/protocollo').'", "required": 1, "name": "numero","class": "text-center alphanumeric-mask", "value": "$numero$" ]}
                </div>';
                    $label = tr('N. fattura del fornitore');
                } else {
                    $label = tr('Numero fattura');
                }
?>

				<div class="col-md-2">
					{[ "type": "text", "label": "<?php echo $label; ?>", "required": "<?php echo ($dir == 'uscita') ? 1 : 0; ?>", "name": "numero_esterno", "class": "text-center", "value": "$numero_esterno$", "help": "<?php echo (empty($record['numero_esterno']) and $dir == 'entrata') ? tr('Il numero della fattura sarà generato automaticamente in fase di emissione.') : ''; ?>" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "date", "label": "<?php echo tr('Data emissione'); ?>", "name": "data", "required": 1, "value": "$data$" ]}
				</div>

				<div class="col-md-2" <?php echo ($dir == 'entrata') ? 'hidden' : ''; ?>>
                    {[ "type": "date", "label": "<?php echo tr('Data registrazione'); ?>", <?php echo $readonly; ?> "name": "data_registrazione", "value": "$data_registrazione$", "help": "<?php echo tr('Data in cui si è effettuata la registrazione della fattura in contabilità'); ?>" ]}
				</div>

                <!-- TODO: da nascondere per le fatture di vendita in quanto questa data sarà sempre uguale alla data di emissione -->
                <div class="col-md-2" <?php echo ($record['is_fiscale']) ? '' : 'hidden'; ?>>
                    {[ "type": "date", "class":"unblockable", "label": "<?php echo tr('Data competenza'); ?>", "name": "data_competenza", "required": 1, "value": "$data_competenza$", "min-date": "$data$", "help": "<?php echo tr('Data nella quale considerare il movimento contabile, che può essere posticipato rispetto la data della fattura'); ?>" ]}
                </div>

<?php
// Conteggio numero articoli fatture
$articolo = $dbo->fetchArray('SELECT `mg_articoli`.`id` FROM ((`mg_articoli` INNER JOIN `co_righe_documenti` ON `mg_articoli`.`id`=`co_righe_documenti`.`idarticolo`) INNER JOIN `co_documenti` ON `co_documenti`.`id`=`co_righe_documenti`.`iddocumento`) WHERE `co_documenti`.`id`='.prepare($id_record));
$id_plugin_sedi = Plugin::where('name', 'Sedi aggiuntive')->first()->id;
if ($dir == 'entrata') {
    echo '
                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Sede partenza').'", "name": "idsede_partenza", "ajax-source": "sedi_azienda", "value": "$idsede_partenza$", "help": "'.tr("Sedi di partenza dell'azienda").'" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Sede destinazione').'", "name": "idsede_destinazione", "ajax-source": "sedi", "select-options": {"idanagrafica": '.$record['idanagrafica'].'}, "value": "$idsede_destinazione$", "help": "'.tr('Sedi del destinatario').'", "icon-after": "add|'.$id_modulo_anagrafiche.'|id_plugin='.$id_plugin_sedi.'&id_parent='.$record['idanagrafica'].'||'.(intval($block_edit) ? 'disabled' : '').'" ]}
                </div>';
} else {
    echo '
                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Sede partenza').'", "name": "idsede_partenza", "ajax-source": "sedi", "select-options": {"idanagrafica": '.$record['idanagrafica'].'}, "value": "$idsede_partenza$", "help": "'.tr('Sedi del mittente').'", "icon-after": "add|'.$id_modulo_anagrafiche.'|id_plugin='.$id_plugin_sedi.'&id_parent='.$record['idanagrafica'].'||'.(intval($block_edit) ? 'disabled' : '').'" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Sede destinazione').'", "name": "idsede_destinazione", "ajax-source": "sedi_azienda", "value": "$idsede_destinazione$", "help": "'.tr("Sedi di arrivo dell'azienda").'" ]}
                </div>';
}
?>
			</div>
			<hr>


			<div class="row">
				<div class="col-md-3">
					<!-- Nella realtà la fattura accompagnatoria non può esistere per la fatturazione elettronica, in quanto la risposta dal SDI potrebbe non essere immediata e le merci in viaggio. Dunque si può emettere una documento di viaggio valido per le merci ed eventualmente una fattura pro-forma per l'incasso della stessa, emettendo infine la fattura elettronica differita. -->

					{[ "type": "select", "label": "<?php echo tr('Tipo documento'); ?>", "name": "idtipodocumento", "required": 1, "values": "query=SELECT `co_tipidocumento`.`id`, CONCAT_WS(' - ',`codice_tipo_documento_fe`, `title`) AS descrizione FROM `co_tipidocumento` LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento`.`id` = `co_tipidocumento_lang`.`id_record` AND `co_tipidocumento_lang`.`id_lang` = <?php echo prepare(Models\Locale::getDefault()->id); ?>) WHERE `dir`='<?php echo $dir; ?>' AND ((`reversed` = 0 AND `id_segment` ='<?php echo $record['id_segment']; ?>') OR `co_tipidocumento`.`id` = <?php echo $record['idtipodocumento']; ?>) ORDER BY `codice_tipo_documento_fe`", "value": "$idtipodocumento$", "readonly": <?php echo intval($fattura->stato->id != $id_stato_bozza && $fattura->stato->id != $id_stato_annullata); ?>, "help": "<?php echo ($database->fetchOne('SELECT tipo FROM an_anagrafiche WHERE idanagrafica = '.prepare($record['idanagrafica']))['tipo'] == 'Ente pubblico') ? 'FPA12 - fattura verso PA (Ente pubblico)' : 'FPR12 - fattura verso soggetti privati (Azienda o Privato)'; ?>" ]}
				</div>
                
				<div class="col-md-3">
                    <?php echo !empty($record['idpagamento']) ? Modules::link('Pagamenti', $record['idpagamento'], null, null, 'class="pull-right"') : ''; ?>

					{[ "type": "select", "label": "<?php echo tr('Pagamento'); ?>", "name": "idpagamento", "required": 1, "ajax-source": "pagamenti", "value": "$idpagamento$", "extra": "onchange=\"if($(this).selectData()) {$('#id_banca_azienda').selectSetNew( $(this).selectData().id_banca_<?php echo $conto; ?>, $(this).selectData().descrizione_banca_<?php echo $conto; ?> ).change();} \" " ]}
				</div>

				<div class="col-md-3">
                    <?php if ($record['id_banca_azienda'] != 0) {
                        echo Modules::link('Banche', $record['id_banca_azienda'], null, null, 'class="pull-right"');
                    }
$id_module_banche = Module::where('name', 'Banche')->first()->id;
if ($dir == 'entrata') {
    echo '
                    {[ "type": "select", "label": "'.tr('Banca accredito').'", "name": "id_banca_azienda", "ajax-source": "banche", "select-options": '.json_encode(['id_anagrafica' => $anagrafica_azienda->id]).', "value": "$id_banca_azienda$", "icon-after": "add|'.$id_module_banche.'|id_anagrafica='.$anagrafica_azienda->id.'", "extra": "'.(intval($block_edit) ? 'disabled' : '').'" ]}
                </div>
                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Banca addebito').'", "name": "id_banca_controparte", "ajax-source": "banche", "select-options": '.json_encode(['id_anagrafica' => $record['idanagrafica']]).', "value": "$id_banca_controparte$", "icon-after": "add|'.$id_module_banche.'|idanagrafica='.$record['idanagrafica'].'", "extra": "'.(intval($block_edit) ? 'disabled' : '').'" ]}';
} else {
    echo '
                    {[ "type": "select", "label": "'.tr('Banca accredito').'", "name": "id_banca_controparte", "ajax-source": "banche", "select-options": '.json_encode(['id_anagrafica' => $record['idanagrafica']]).', "value": "$id_banca_controparte$", "icon-after": "add|'.$id_module_banche.'|idanagrafica='.$record['idanagrafica'].'", "extra": "'.(intval($block_edit) ? 'disabled' : '').'" ]}
                </div>
                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Banca addebito').'", "name": "id_banca_azienda", "ajax-source": "banche", "select-options": '.json_encode(['id_anagrafica' => $anagrafica_azienda->id]).', "value": "$id_banca_azienda$", "icon-after": "add|'.$id_module_banche.'|id_anagrafica='.$anagrafica_azienda->id.'", "extra": "'.(intval($block_edit) ? 'disabled' : '').'" ]}';
}
?>
                </div>
            </div>

            <!-- Split payment + Fattura per conto terzi (solo uscita) + Sconto in fattura (solo uscita) -->
            <div class="row">

                <div class="col-md-3">
                    {[ "type": "checkbox", "label": "<?php echo tr('Split payment'); ?>", "name": "split_payment", "value": "$split_payment$", "help": "<?php echo tr('Abilita lo split payment per questo documento. Le aliquote iva con natura N6.X (reverse charge) non saranno disponibili.'); ?>", "placeholder": "<?php echo tr('Split payment'); ?>" ]}
                </div>

                <?php
    // TODO: Fattura per conto del fornitore (es. cooperative agricole che emettono la fattura per conto dei propri soci produttori agricoli conferenti)
    if ($dir == 'entrata') {
        ?>
                <div class="col-md-3">
                    {[ "type": "checkbox", "label": "<?php echo tr('Fattura per conto terzi'); ?>", "name": "is_fattura_conto_terzi", "value": "$is_fattura_conto_terzi$", "help": "<?php echo tr('Nell\'XML della Fattura Elettronica sarà indicato il fornitore ('.stripslashes((string) $database->fetchOne('SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica = '.prepare(setting('Azienda predefinita')))['ragione_sociale']).') come cessionario e il cliente come cedente/prestatore.'); ?>", "placeholder": "<?php echo tr('Fattura per conto terzi'); ?>" ]}
                </div>

                <?php
                echo '
                <div class="col-md-3">
                    {[ "type": "number", "label": "'.tr('Sconto in fattura').'", "name": "sconto_finale", "value": "'.($fattura->sconto_finale_percentuale ?: $fattura->sconto_finale).'", "icon-after": "choice|untprc|'.(empty($fattura->sconto_finale) ? 'PRC' : 'UNT').'", "help": "'.tr('Sconto in fattura, utilizzabile per applicare sconti sul Netto a pagare del documento e le relative scadenze').'. '.tr('Per utilizzarlo in relazione a una riga della Fattura Elettronica, inserire il tipo di dato in \'\'Attributi avanzati\'\' -> \'\'Altri Dati Gestionali\'\' -> \'\'TipoDato\'\' e il testo di descrizione in \'\'Attributi avanzati\'\' -> \'\'Altri Dati Gestionali\'\' -> \'\'RiferimentoTesto\'\' della specifica riga').'. '.tr('Nota: lo sconto in fattura non influenza i movimenti contabili').'." ]}
                </div>';
    } else {
        echo '
                <div class="col-md-3"></div>
                <div class="col-md-3"></div>';
    }

if ($fattura->stato->id != $id_stato_bozza && $fattura->stato->id != $id_stato_annullata) {
    $scadenze = $fattura->scadenze;

    $ricalcola = true;
    foreach ($scadenze as $scadenza) {
        $ricalcola = empty(floatval($scadenza->pagato)) && $ricalcola;
    }

    echo '
                <div class="col-md-3">
                    <p class="pull-left"><strong>'.tr('Scadenze').'</strong></p>

                    <div class="btn-group pull-right">
                        '.Modules::link('Scadenzario', $scadenze[0]['id'], tr('<i class="fa fa-edit tip" title="'.tr('Modifica scadenze').'"></i>'), '', 'class="btn btn-xs btn-primary"');

    // Ricalcola scadenze disponibile solo per fatture di acquisto
    if ($fattura->isFE() && $ricalcola && !empty($module->where('name', 'Fatture di acquisto')->first()->id)) {
        echo '
                    <button type="button" class="btn btn-info btn-xs pull-right tip" title="'.tr('Ricalcola le scadenze').'. '.tr('Per ricalcolare correttamente le scadenze, imposta la fattura di acquisto nello stato \'\'Bozza\'\' e correggi il documento come desiderato, poi re-imposta lo stato \'\'Emessa\'\' e utilizza questa funzione').'." id="ricalcola_scadenze">
                        <i class="fa fa-calculator" aria-hidden="true"></i>
                    </button>';
    }
    echo '
                    </div>
                    <div class="clearfix"></div>';

    foreach ($scadenze as $scadenza) {
        $pagamento_iniziato = !empty(floatval($scadenza->pagato)) || $scadenza->da_pagare == 0;

        echo '
                    <p>'.dateFormat($scadenza['scadenza']).': ';

        if ($pagamento_iniziato) {
            echo '
                    <strike>';
        }

        echo (empty($scadenza->da_pagare) ? '<i class="fa fa-exclamation-triangle"></i>' : '').moneyFormat(abs($scadenza->da_pagare));

        if ($pagamento_iniziato) {
            echo '
                    </strike>';
        }

        if ($pagamento_iniziato && abs($scadenza->pagato) != abs($scadenza->da_pagare)) {
            echo ' ('.moneyFormat(abs($scadenza->da_pagare) - abs($scadenza->pagato)).')';
        }

        echo '
                    </p>';
    }

    echo '
                </div>';
}
?>
                <div class="col-md-3">
                    <?php echo !empty($record['id_ritenuta_contributi']) ? Modules::link('Ritenute previdenziali', $record['id_ritenuta_contributi'], null, null, 'class="pull-right"') : ''; ?>
                    {[ "type": "select", "label": "<?php echo tr('Ritenuta previdenziale'); ?>", "name": "id_ritenuta_contributi", "value": "$id_ritenuta_contributi$", "values": "query=SELECT *, CONCAT(descrizione,(IF(percentuale>0, CONCAT(\" - \", percentuale, \"% sul \", percentuale_imponibile, \"% imponibile\"), \"\"))) AS descrizione FROM co_ritenuta_contributi", "help": "<?php echo tr('Ritenuta previdenziale da applicare alle righe della fattura.'); ?>"  ]}
                </div>

                <?php
if ($dir == 'uscita') {
    echo '
                <div class="col-md-3">
                    {[ "type": "checkbox", "label": "'.tr('Ritenuta pagata dal fornitore').'", "name": "is_ritenuta_pagata", "value": "$is_ritenuta_pagata$" ]}
                </div>';
}
if ($dir == 'entrata') {
    echo '
                <div class="col-md-6">';

    if (!empty($record['id_dichiarazione_intento'])) {
        echo Plugins::link("Dichiarazioni d'Intento", $record['idanagrafica'], null, null, 'class="pull-right"');
    }

    echo '
                    {[ "type": "select", "label": "'.tr("Dichiarazione d'intento").'", "name": "id_dichiarazione_intento", "help": "'.tr('Elenco delle dichiarazioni d\'intento definite all\'interno dell\'anagrafica del cliente').'.", "ajax-source": "dichiarazioni_intento", "select-options": {"idanagrafica": '.$record['idanagrafica'].', "data": "'.$record['data'].'"},"value": "$id_dichiarazione_intento$", "icon-after": "add|'.$id_modulo_anagrafiche.'|id_plugin='.Plugin::where('name', 'Dichiarazioni d\'intento')->first()->id.'&id_parent='.$record['idanagrafica'].'", "extra": "'.((intval($block_edit)) ? 'disabled' : '').'"  ]}
                </div>';
}
echo '
            </div>';

if ($dir == 'entrata') {
    echo '
            <div class="row">
                <div class="col-md-3">
                    {[ "type": "checkbox", "label": "'.tr('Marca da bollo automatica').'", "name": "bollo_automatico", "value": "'.intval(!isset($record['bollo'])).'", "help": "'.tr('Se abilitata, questa impostazione addebita automaticamente l\'importo della marca da bollo quando la fattura soddisfa i requisiti per la sua applicazione. In particolare, la marca da bollo si applica se la fattura contiene righe con aliquote IVA con natura N2.1, N2.2, N3.5, N3.6, N4, e se il totale della fattura supera i _MONEY_', [
        '_MONEY_' => moneyFormat(setting("Soglia minima per l'applicazione della marca da bollo")),
    ]).'.<br> Se disabilitata questa impostazione permette di impostare manualmente la marca da bollo.", "placeholder": "'.tr('Bollo automatico').'" ]}
                </div>

                <div class="col-md-3 bollo">
                    {[ "type": "checkbox", "label": "'.tr('Addebita marca da bollo').'", "name": "addebita_bollo", "value": "$addebita_bollo$", "help":"Se abilitata addebita l\'importo della marca da bollo definito nell\'impostazione in fattura.<br>Se disabilitato, non imposta alcuna marca da bollo in fattura." ]}
                </div>

                <div class="col-md-3 bollo">
                    {[ "type": "number", "label": "'.tr('Importo marca da bollo').'", "name": "bollo", "value": "$bollo$"]}
                </div>
            </div>';

    $bollo = new Bollo($fattura);
}
?>
			<div class="row">
				<div class="col-md-6">
					{[ "type": "textarea", "label": "<?php echo tr('Note'); ?>", "name": "note", "help": "<?php echo tr('Note visibili anche in fattura.'); ?>", "value": "$note$", "charcounter": 1 ]}
                </div>
				<div class="col-md-6">
					{[ "type": "textarea", "label": "<?php echo tr('Note interne'); ?>", "name": "note_aggiuntive", "help": "<?php echo tr('Note interne.'); ?>", "value": "$note_aggiuntive$", "class": "unblockable" ]}
				</div>
			</div>
		</div>
	</div>

<?php
if ($record['descrizione_tipo'] == 'Fattura accompagnatoria di vendita') {
    echo '
    <div class="card card-info">
        <div class="card-header with-border">
            <h3 class="card-title"><i class="fa fa-edit"></i> '.tr('Dati Fattura accompagnatoria').'</h3>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Aspetto beni').'", "name": "idaspettobeni", "placeholder": "", "ajax-source": "aspetto-beni", "value": "$idaspettobeni$", "icon-after": "add|'.Module::where('name', 'Aspetto beni')->first()->id.'||'.(($fattura->stato->id != $id_stato_bozza) ? 'disabled' : '').'" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Causale trasporto').'", "name": "idcausalet", "placeholder": "", "ajax-source": "causali", "value": "$idcausalet$", "icon-after": "add|'.Module::where('name', 'Causali')->first()->id.'||'.(($fattura->stato->id != $id_stato_bozza) ? 'disabled' : '').'" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Porto').'", "name": "idporto", "placeholder": "", "values": "query=SELECT `dt_porto`.`id`, `dt_porto_lang`.`title` as descrizione FROM `dt_porto` LEFT JOIN `dt_porto_lang` ON (`dt_porto`.`id` = `dt_porto_lang`.`id_record` AND `dt_porto_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') ORDER BY `title` ASC", "value": "$idporto$" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "text", "label": "'.tr('Num. colli').'", "name": "n_colli", "value": "$n_colli$" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Tipo di spedizione').'", "name": "idspedizione", "values": "query=SELECT `dt_spedizione`.`id`, `dt_spedizione_lang`.`title` as descrizione, `esterno` FROM `dt_spedizione` LEFT JOIN `dt_spedizione_lang` ON (`dt_spedizione`.`id` = `dt_spedizione_lang`.`id_record` AND `dt_spedizione_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') ORDER BY `title` ASC", "value": "$idspedizione$" ]}
                </div>

                <div class="col-md-3">';
    if (!empty($record['idvettore'])) {
        echo Modules::link('Anagrafiche', $record['idvettore'], null, null, 'class="pull-right"');
    }
    $esterno = $dbo->selectOne('dt_spedizione', 'esterno', [
        'id' => $record['idspedizione'],
    ])['esterno']; ?>

                    {[ "type": "select", "label": "<?php echo tr('Vettore'); ?>", "name": "idvettore", "ajax-source": "vettori", "value": "$idvettore$", "disabled": <?php echo empty($esterno) || (!empty($esterno) && !empty($record['idvettore'])) ? 1 : 0; ?>, "required": <?php echo !empty($esterno) ?: 0; ?>, "icon-after": "add|<?php echo $id_modulo_anagrafiche; ?>|tipoanagrafica=Vettore&readonly_tipo=1|btn_idvettore|<?php echo ($esterno and (intval(!$record['flag_completato']) || empty($record['idvettore']))) ? '' : 'disabled'; ?>", "class": "<?php echo empty($record['idvettore']) ? 'unblockable' : ''; ?>" ]}
                </div>

<script>
    $("#idspedizione").change(function() {
        if($(this).val()){
            if (!$(this).selectData().esterno) {
                $("#idvettore").attr("required", false);
                input("idvettore").disable();
                $("label[for=idvettore]").text("<?php echo tr('Vettore'); ?>");
                $("#idvettore").selectReset("<?php echo tr("Seleziona un\'opzione"); ?>");
                $(".btn_idvettore").prop("disabled", true);
                $(".btn_idvettore").addClass("disabled");
            }else{
                $("#idvettore").attr("required", true);
                input("idvettore").enable();
                $("label[for=idvettore]").text("<?php echo tr('Vettore'); ?>*");
                $(".btn_idvettore").prop("disabled", false);
                $(".btn_idvettore").removeClass("disabled");

            }
        } else{
            $("#idvettore").attr("required", false);
            input("idvettore").disable();
            $("label[for=idvettore]").text("<?php echo tr('Vettore'); ?>");
            $("#idvettore").selectReset("<?php echo tr("Seleziona un\'opzione"); ?>");
            $(".btn_idvettore").prop("disabled", true);
            $(".btn_idvettore").addClass("disabled");
        }
    });

    $("#idcausalet").change(function() {
        if ($(this).val() == 3) {
            $("#tipo_resa").attr("disabled", false);
        }else{
            $("#tipo_resa").attr("disabled", true);
        }
    });
</script>
<?php

    $tipo_resa = [
        [
            'id' => 'EXW',
            'text' => 'EXW',
        ],
        [
            'id' => 'FCA',
            'text' => 'FCA',
        ],
        [
            'id' => 'FAS',
            'text' => 'FAS',
        ],
        [
            'id' => 'FOB',
            'text' => 'FOB',
        ],
        [
            'id' => 'CFR',
            'text' => 'CFR',
        ],
        [
            'id' => 'CIF',
            'text' => 'CIF',
        ],
        [
            'id' => 'CPT',
            'text' => 'CPT',
        ],
        [
            'id' => 'CIP',
            'text' => 'CIP',
        ],
        [
            'id' => 'DAF',
            'text' => 'DAF',
        ],
        [
            'id' => 'DES',
            'text' => 'DES',
        ],
        [
            'id' => 'DEQ',
            'text' => 'DEQ',
        ],
        [
            'id' => 'DDU',
            'text' => 'DDU',
        ],
        [
            'id' => 'DDP',
            'text' => 'DDP',
        ],
    ];

    echo '
                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Tipo Resa').'", "name": "tipo_resa", "value": "$tipo_resa$", "values": '.json_encode($tipo_resa).', "readonly": '.intval($record['causale_desc'] != 'Reso').' ]}
                </div>
            </div>';

    echo '
            <div class="row">
                <div class="col-md-3">
                    {[ "type": "number", "label": "'.tr('Peso').'", "name": "peso", "value": "$peso$", "readonly": "'.intval(empty($record['peso_manuale'])).'", "help": "'.tr('Il valore del campo Peso viene calcolato in automatico sulla base degli articoli inseriti nel documento, a meno dell\'impostazione di un valore manuale in questo punto').'", "decimals": "qta" ]}
                    <input type="hidden" id="peso_calcolato" name="peso_calcolato" value="'.$fattura->peso_calcolato.'">
                </div>

                <div class="col-md-3">
                    {[ "type": "checkbox", "label": "'.tr('Modifica peso').'", "name": "peso_manuale", "value":"$peso_manuale$", "help": "'.tr('Seleziona per modificare manualmente il campo Peso').'", "placeholder": "'.tr('Modifica peso').'" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "number", "label": "'.tr('Volume').'", "name": "volume", "value": "$volume$", "readonly": "'.intval(empty($record['volume_manuale'])).'", "help": "'.tr('Il valore del campo Volume viene calcolato in automatico sulla base degli articoli inseriti nel documento, a meno dell\'impostazione di un valore manuale in questo punto').'", "decimals": "qta" ]}
                    <input type="hidden" id="volume_calcolato" name="volume_calcolato" value="'.$fattura->volume_calcolato.'">
                </div>

                <div class="col-md-3">
                    {[ "type": "checkbox", "label": "'.tr('Modifica volume').'", "name": "volume_manuale", "value":"$volume_manuale$", "help": "'.tr('Seleziona per modificare manualmente il campo Volume').'", "placeholder": "'.tr('Modifica volume').'" ]}
                </div>
            </div>
        </div>
    </div>';
}

echo '
</form>';

// Dich. intento collegata
if ($dir == 'entrata' && !empty($fattura->dichiarazione)) {
    $ive_accettate = $dbo->table('co_iva')->where('codice_natura_fe', 'N3.5')->get();
    foreach ($ive_accettate as $iva_accettata) {
        $descrizione_iva_accettata .= '<li>'.Aliquota::find($iva_accettata->id)->getTranslation('title').'</li>';
    }

    if ($fattura->stato->id == $id_stato_bozza) {
        echo '
    <div class="alert alert-info">
        <i class="fa fa-info"></i> '.tr("La fattura è collegata ad una dichiarazione d'intento con diponibilità residura pari a _MONEY_.", ['_MONEY_' => moneyFormat($diff)]).'<br>'.tr('Per collegare una riga alla dichiarazione è sufficiente specificare come IVA <ul>_IVA_</ul>', ['_IVA_' => $descrizione_iva_accettata]).'</b>
    </div>';
    }
}

echo '
<!-- RIGHE -->
<div class="card card-primary">
	<div class="card-header">
		<h3 class="card-title">'.tr('Righe').'</h3>
	</div>

	<div class="card-body">
		<div class="row">
			<div class="col-md-12">';

if (!$block_edit) {
    if (empty($record['ref_documento'])) {
        if ($dir == 'entrata') {
            $where = '';
            // Lettura interventi non collegati a preventivi, ordini e contratti
            if (!setting('Permetti fatturazione delle attività collegate a contratti')) {
                $where = ' AND in_interventi.id_contratto IS NULL';
            }
            if (!setting('Permetti fatturazione delle attività collegate a ordini')) {
                $where .= ' AND in_interventi.id_ordine IS NULL';
            }
            if (!setting('Permetti fatturazione delle attività collegate a preventivi')) {
                $where .= ' AND in_interventi.id_preventivo IS NULL';
            }

            // Lettura interventi non rifiutati, non fatturati
            $int_query = 'SELECT COUNT(*) AS tot FROM `in_interventi` INNER JOIN `in_statiintervento` ON `in_interventi`.`idstatointervento`=`in_statiintervento`.`id` WHERE `idanagrafica`='.prepare($record['idanagrafica']).' AND `in_statiintervento`.`is_fatturabile`=1 AND `in_interventi`.`id` NOT IN (SELECT `idintervento` FROM `co_righe_documenti` WHERE `idintervento` IS NOT NULL) '.$where;
            $interventi = $dbo->fetchArray($int_query)[0]['tot'];

            // Se non trovo niente provo a vedere se ce ne sono per clienti terzi
            if (empty($interventi)) {
                // Lettura interventi non rifiutati, non fatturati
                $int_query = 'SELECT COUNT(*) AS tot FROM `in_interventi` INNER JOIN `in_statiintervento` ON `in_interventi`.`idstatointervento`=`in_statiintervento`.`id` WHERE `idclientefinale`='.prepare($record['idanagrafica']).' AND `in_statiintervento`.`is_fatturabile`=1 AND `in_interventi`.`id` NOT IN (SELECT `idintervento` FROM `co_righe_documenti` WHERE `idintervento` IS NOT NULL) '.$where;
                $interventi = $dbo->fetchArray($int_query)[0]['tot'];
            }

            // Lettura preventivi accettati, in attesa di conferma o in lavorazione
            $prev_query = 'SELECT 
                    COUNT(*) AS tot 
                FROM 
                    `co_preventivi`
                    LEFT JOIN `co_righe_preventivi` ON `co_preventivi`.id = `co_righe_preventivi`.idpreventivo
                    INNER JOIN `co_statipreventivi` ON `co_statipreventivi`.id = `co_preventivi`.idstato
                WHERE 
                    `idanagrafica`='.prepare($record['idanagrafica']).' AND `co_statipreventivi`.`is_fatturabile` = 1 AND `default_revision` = 1 AND (`co_righe_preventivi`.`qta` - `co_righe_preventivi`.`qta_evasa`) > 0';
            $preventivi = $dbo->fetchArray($prev_query)[0]['tot'];

            // Lettura contratti accettati, in attesa di conferma o in lavorazione
            $contr_query = 'SELECT COUNT(*) AS tot FROM `co_contratti` WHERE `idanagrafica`='.prepare($record['idanagrafica']).' AND `idstato` IN (SELECT `id` FROM `co_staticontratti` WHERE `is_fatturabile` = 1) AND `co_contratti`.`id` IN (SELECT `idcontratto` FROM `co_righe_contratti` WHERE `co_righe_contratti`.`idcontratto` = `co_contratti`.`id` AND (`qta` - `qta_evasa`) > 0)';
            $contratti = $dbo->fetchArray($contr_query)[0]['tot'];
        }

        $id_stato_evaso = StatoDDT::where('name', 'Evaso')->first()->id;
        $id_stato_parz_evaso = StatoDDT::where('name', 'Parzialmente evaso')->first()->id;
        $id_stato_parz_fatt = StatoDDT::where('name', 'Parzialmente fatturato')->first()->id;

        // Lettura ddt (entrata o uscita)
        $ddt_query = 'SELECT 
            COUNT(*) AS tot 
        FROM 
            `dt_ddt`
            INNER JOIN `dt_causalet` ON `dt_causalet`.`id` = `dt_ddt`.`idcausalet`
            INNER JOIN `dt_statiddt` ON `dt_statiddt`.`id` = `dt_ddt`.`idstatoddt`
            LEFT JOIN `dt_statiddt_lang` ON (`dt_statiddt`.`id` = `dt_statiddt_lang`.`id_record` AND `dt_statiddt_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
            LEFT JOIN `dt_tipiddt` ON `dt_tipiddt`.`id` = `dt_ddt`.`idtipoddt`
            INNER JOIN `dt_righe_ddt` ON `dt_righe_ddt`.`idddt` = `dt_ddt`.`id`
        WHERE 
            `idanagrafica`='.prepare($record['idanagrafica']).'
            AND `dt_statiddt`.`id` IN ('.prepare($id_stato_evaso).','.prepare($id_stato_parz_evaso).','.prepare($id_stato_parz_fatt).')
            AND `dt_tipiddt`.`dir` = '.prepare($dir).'
            AND `dt_causalet`.`is_importabile` = 1
            AND (`dt_righe_ddt`.`qta` - `dt_righe_ddt`.`qta_evasa`) > 0';
        $ddt = $dbo->fetchArray($ddt_query)[0]['tot'];

        // Lettura ordini (cliente o fornitore)
        $id_stato_accettato = StatoOrdine::where('name', 'Accettato')->first()->id;
        $id_stato_evaso = StatoOrdine::where('name', 'Evaso')->first()->id;
        $id_stato_parz_evaso = StatoOrdine::where('name', 'Parzialmente evaso')->first()->id;
        $id_stato_parz_fatt = StatoOrdine::where('name', 'Parzialmente fatturato')->first()->id;

        $ordini_query = 'SELECT 
                COUNT(*) AS tot 
            FROM 
                `or_ordini`
                INNER JOIN `or_righe_ordini` ON `or_ordini`.`id` = `or_righe_ordini`.`idordine`
                INNER JOIN `or_statiordine` ON `or_statiordine`.`id` = `or_ordini`.`idstatoordine`
                LEFT JOIN `or_statiordine_lang` ON (`or_statiordine`.`id` = `or_statiordine_lang`.`id_record` AND `or_statiordine_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
                INNER JOIN `or_tipiordine` ON `or_tipiordine`.`id` = `or_ordini`.`idtipoordine`
            WHERE 
                idanagrafica='.prepare($record['idanagrafica']).' 
                AND `or_statiordine`.`id` IN ('.prepare($id_stato_accettato).','.prepare($id_stato_evaso).','.prepare($id_stato_parz_evaso).','.prepare($id_stato_parz_fatt).') 
                AND `dir`='.prepare($dir).'
                AND (`or_righe_ordini`.`qta` - `or_righe_ordini`.`qta_evasa`) > 0';
        $ordini = $dbo->fetchArray($ordini_query)[0]['tot'];
    }

    // Form di inserimento riga documento
    echo '
                <form id="link_form" action="" method="post">
                    <input type="hidden" name="op" value="add_articolo">
                    <input type="hidden" name="backto" value="record-edit">

                    <div class="row">
                        <div class="col-md-3">
                            {[ "type": "text", "label": "'.tr('Aggiungi un articolo tramite barcode').'", "name": "barcode", "extra": "autocomplete=\"off\"", "icon-before": "<i class=\"fa fa-barcode\"></i>", "required": 0 ]}
                        </div>

                        <div class="col-md-4">
                            {[ "type": "select", "label": "'.tr('Articolo').'", "name": "id_articolo", "value": "", "ajax-source": "articoli",  "select-options": {"permetti_movimento_a_zero": '.($dir == 'entrata' ? 0 : 1).', "idsede_partenza": '.intval($fattura->idsede_partenza).', "idsede_destinazione": '.intval($fattura->idsede_destinazione).', "idanagrafica": '.$fattura->idanagrafica.', "dir": "'.$dir.'", "idagente": '.$fattura->idagente.'}, "icon-after": "add|'.Module::where('name', 'Articoli')->first()->id.'" ]}
                        </div>

                        <div class="col-md-3" style="margin-top: 25px">
                            <button title="'.tr('Aggiungi articolo alla vendita').'" class="btn btn-primary tip" type="button" onclick="salvaArticolo()">
                                <i class="fa fa-plus"></i> '.tr('Aggiungi').'
                            </button>
                            
                            <a class="btn btn-primary" onclick="gestioneRiga(this)" data-title="'.tr('Aggiungi riga').'">
                                <i class="fa fa-plus"></i> '.tr('Riga').'
                            </a>
                            
                            <div class="btn-group tip" data-card-widget="tooltip">
                                <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                                    <i class="fa fa-list"></i> '.tr('Altro').'
                                    <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-right">

                                    <a class="dropdown-item" style="cursor:pointer" onclick="gestioneDescrizione(this)" data-title="'.tr('Aggiungi descrizione').'">
                                        <i class="fa fa-plus"></i> '.tr('Descrizione').'
                                    </a>

                                    <a class="dropdown-item" style="cursor:pointer" onclick="gestioneSconto(this)" data-title="'.tr('Aggiungi sconto/maggiorazione').'">
                                        <i class="fa fa-plus"></i> '.tr('Sconto/maggiorazione').'
                                    </a>';

    if (empty($record['ref_documento'])) {
        if ($dir == 'entrata') {
            echo '

                                    <a class="'.(!empty($interventi) ? '' : ' disabled').' dropdown-item" style="cursor:pointer" data-href="'.$structure->fileurl('add_intervento.php').'?id_module='.$id_module.'&id_record='.$id_record.'" data-card-widget="modal" data-title="'.tr('Aggiungi Attività').'" onclick="saveForm()">
                                        <i class="fa fa-plus"></i> '.tr('Attività').'
                                    </a>

                                    <a class="'.(!empty($preventivi) ? '' : ' disabled').' dropdown-item" style="cursor:pointer" data-href="'.$structure->fileurl('add_preventivo.php').'?id_module='.$id_module.'&id_record='.$id_record.'" data-card-widget="modal" data-title="'.tr('Aggiungi Preventivo').'" onclick="saveForm()">
                                        <i class="fa fa-plus"></i> '.tr('Preventivo').'
                                    </a>

                                    <a class="'.(!empty($contratti) ? '' : ' disabled').' dropdown-item" style="cursor:pointer" data-href="'.$structure->fileurl('add_contratto.php').'?id_module='.$id_module.'&id_record='.$id_record.'" data-card-widget="modal" data-title="'.tr('Aggiungi Contratto').'" onclick="saveForm()">
                                        <i class="fa fa-plus"></i> '.tr('Contratto').'
                                    </a>';
        }
        echo '

                                    <a class="'.(!empty($ddt) ? '' : ' disabled').' dropdown-item" style="cursor:pointer" data-href="'.$structure->fileurl('add_ddt.php').'?id_module='.$id_module.'&id_record='.$id_record.'" data-card-widget="modal" data-title="'.tr('Aggiungi Ddt').'" onclick="saveForm()">
                                        <i class="fa fa-plus"></i> '.tr('Ddt').'
                                    </a>

                                    <a class="'.(!empty($ordini) ? '' : ' disabled').' dropdown-item" style="cursor:pointer" data-href="'.$structure->fileurl('add_ordine.php').'?id_module='.$id_module.'&id_record='.$id_record.'" data-card-widget="modal" data-title="'.tr('Aggiungi Ordine').'" onclick="saveForm()">
                                        <i class="fa fa-plus"></i> '.tr('Ordine').'
                                    </a>';
    }
    echo '
                                </ul>
                            </div>
                        </div>

                        <div class="col-md-2">
                            {[ "type": "select", "label": "'.tr('Ordinamento').'", "name": "ordinamento", "class": "no-search", "value": "'.($_SESSION['module_'.$id_module]['order_row_desc'] ? 'desc' : 'manuale').'", "values": "list=\"desc\": \"'.tr('Ultima riga inserita').'\", \"manuale\": \"'.tr('Manuale').'\"" ]}
                        </div>
                    </div>
                </form>';
}
?>
			</div>
		</div>
		<div class="clearfix"></div>
		<br>

        <div class="row">
			<div class="col-md-12" id="righe"></div>
		</div>
	</div>
</div>

<?php

if ($fattura->isFE()) {
    echo '
<div class="alert alert-info text-center" id="controlla_totali"></div>

<script>
    $(document).ready(function() {
        $.ajax({
            url: globals.rootdir + "/actions.php",
            type: "post",
            data: {
                id_module: globals.id_module,
                id_record: globals.id_record,
                op: "controlla_totali",
            },
            success: function(data){
                data = JSON.parse(data);

                const div = $("#controlla_totali");
                div.removeClass("alert-info");
                const calculated = parseFloat(data.calculated);
                const stored = parseFloat(data.stored);

                if (stored == null) {
                    div.addClass("alert-info").html("'.tr("Il file XML non contiene il nodo ''ImportoTotaleDocumento'': impossibile controllare corrispondenza dei totali").'.")
                } else if (stored.toLocale() != calculated.toLocale()){
                    div.addClass("alert-warning").html("'.tr('Il totale del file XML non corrisponde a quello calcolato dal gestionale: previsto _XML_, calcolato _CALC_ (differenza _DIFF_)', [
        '_XML_' => '" + stored.toLocale() + " " + globals.currency + "',
        '_CALC_' => '" + calculated.toLocale() + " " + globals.currency + "',
        '_DIFF_' => '" + (calculated - stored).toLocale() + " " + globals.currency + "',
    ]).'.")
                }

            }
        });
    })
</script>';
}
?>

{( "name": "filelist_and_upload", "id_module": "$id_module$", "id_record": "$id_record$", "abilita_genera": "<?php echo $abilita_genera; ?>" )}

<?php
if ($dir == 'entrata') {
    echo '
<div class="alert alert-info text-center">'.tr('Per allegare un documento alla fattura elettronica caricare il file PDF specificando come categoria "Allegati Fattura Elettronica"').'.</div>';
}

echo '
<script type="text/javascript">

    $("#idtipodocumento").change(function() {
         updateSelectOption("idtipodocumento", $(this).val());
         session_set("superselect,idtipodocumento",$(this).val(), 0);
    });

	$("#idanagrafica").change(function() {
        updateSelectOption("idanagrafica", $(this).val());
        session_set("superselect,idanagrafica", $(this).val(), 0);

        $("#idreferente").selectReset();
        $("#id_dichiarazione_intento").selectReset();
        $("#idpagamento").selectReset();
        $("#id_banca_azienda").selectReset();

        let data = $(this).selectData();
        if (data) {
            // Impostazione del tipo di pagamento da anagrafica
            if (data.id_pagamento) { 
                input("idpagamento").getElement()
                    .selectSetNew(data.id_pagamento, data.desc_pagamento, {"id_banca_vendite": data.id_banca_vendite, "id_banca_acquisti": data.id_banca_acquisti, "descrizione_banca_vendite": data.descrizione_banca_vendite, "descrizione_banca_acquisti": data.descrizione_banca_acquisti});
            }
        }';

if ($dir == 'entrata') {
    echo '$("#idsede_destinazione").selectReset();';
} else {
    echo '$("#idsede_partenza").selectReset();';
}

echo '
	});

    $("#ricalcola_scadenze").click(function() {
        swal({
            title: "'.tr('Desideri ricalcolare le scadenze?').'",
            type: "warning",
            showCancelButton: true,
            confirmButtonText: "'.tr('Sì').'"
        }).then(function (result) {
            redirect(globals.rootdir + "/editor.php", {
                id_module: globals.id_module,
                id_record: globals.id_record,
                op: "ricalcola_scadenze",
                backto: "record-edit",
            }, "post")
        })
    });
</script>';

?>

{( "name": "log_email", "id_module": "$id_module$", "id_record": "$id_record$" )}

<?php
// Eliminazione ddt solo se ho accesso alla sede aziendale
$field_name = ($dir == 'entrata') ? 'idsede_partenza' : 'idsede_destinazione';
if (in_array($record[$field_name], $user->sedi)) {
    $disabilita_eliminazione = in_array($fattura->codice_stato_fe, ['RC', 'MC', 'EC01', 'WAIT']);

    echo '
    <a id ="elimina" class="btn btn-danger ask '.($disabilita_eliminazione ? 'disabled' : '').'" data-backto="record-list" '.($disabilita_eliminazione ? 'disabled' : '').'>
        <i class="fa fa-trash"></i> '.tr('Elimina').'
    </a>';
}

echo '
<script>
async function saveForm() {
    // Salvataggio via AJAX
    await salvaForm("#edit-form");
}

function gestioneSconto(button) {
    gestioneRiga(button, "is_sconto=1");
}

function gestioneDescrizione(button) {
    gestioneRiga(button, "is_descrizione=1");
}

async function gestioneRiga(button, options) {
    // Salvataggio via AJAX
    await salvaForm("#edit-form", {}, button);

    // Lettura titolo e chiusura tooltip
    let title = $(button).attr("data-title");

    // Apertura modal
    options = options ? options : "is_riga=1";
    openModal(title, "'.$structure->fileurl('row-add.php').'?id_module='.$id_module.'&id_record='.$id_record.'&" + options);
}

/**
 * Funzione dedicata al caricamento dinamico via AJAX delle righe del documento.
 */
function caricaRighe(id_riga) {
    let container = $("#righe");

    localLoading(container, true);
    return $.get("'.$structure->fileurl('row-list.php').'?id_module='.$id_module.'&id_record='.$id_record.'", function(data) {
        container.html(data);
        localLoading(container, false);
    });
}

$(document).ready(function () {
    caricaRighe(null);

    $("#data_registrazione").on("dp.change", function (e) {
        let data_competenza = $("#data_competenza");

        if(data_competenza.data("DateTimePicker").date() < e.date){
            data_competenza.data("DateTimePicker").date(e.date);
        }
    });

    $("#data").on("dp.change", function (e) {
        let data_competenza = $("#data_competenza");
        data_competenza.data("DateTimePicker").minDate(e.date);

        if(data_competenza.data("DateTimePicker").date() < e.date){
            data_competenza.data("DateTimePicker").date(e.date);
        }
    });

    $("#idsede_destinazione").change(function(){
        updateSelectOption("idsede_destinazione", $(this).val());
        $("#idreferente").selectReset();
    });

    $("#id_articolo").on("change", function(e) {
        if ($(this).val()) {
            var data = $(this).selectData();

            if (data.barcode) {
                $("#barcode").val(data.barcode);
            } else {
                $("#barcode").val("");
            }
        }

        e.preventDefault();

        setTimeout(function(){
            $("#barcode").focus();
        }, 100);
    });

    $("#barcode").focus();
});

function cambiaStato() {
    let testo = $("#idstatodocumento option:selected").text();

    if (testo === "Pagato" || testo === "Parzialmente pagato") {
        if(confirm("'.tr('Sicuro di voler impostare manualmente la fattura come pagata senza aggiungere il movimento in prima nota?').'")) {
            return true;
        } else {
            $("#idstatodocumento").selectSet('.$record['idstatodocumento'].');
        }
    }
}';
if ($dir == 'entrata') {
    echo '
    function bolloAutomatico() {
        let bollo_automatico = input("bollo_automatico");
        let addebita_bollo = input("addebita_bollo");
        if(bollo_automatico.get()==0){
            $(".bollo").show();
            input("bollo").enable();
            input("addebita_bollo").enable();
        } else {
            $(".bollo").hide();
            input("bollo").disable();
            $("#bollo").val('.setting('Importo marca da bollo').');
            $("addebita_bollo").hide();
            input("addebita_bollo").disable(); 
        }
    }
    $(document).ready(function() {
        $("#idsede_partenza").trigger("change");

        if(!$("#volume_manuale").is(":checked")){
            input("volume").set($("#volume_calcolato").val());
        }
        $("#volume_manuale").click(function() {
            $("#volume").prop("readonly", !$("#volume_manuale").is(":checked"));
            if(!$("#volume_manuale").is(":checked")){
                input("volume").set($("#volume_calcolato").val());
            }
        });

        if(!$("#peso_manuale").is(":checked")){
            input("peso").set($("#peso_calcolato").val());
        }
        $("#peso_manuale").click(function() {
            $("#peso").prop("readonly", !$("#peso_manuale").is(":checked"));
            if(!$("#peso_manuale").is(":checked")){
                input("peso").set($("#peso_calcolato").val());
            }
        });

        bolloAutomatico();
        content_was_modified = false;

    });
    input("bollo_automatico").change(function () {
        bolloAutomatico();
    });';
}

echo '
async function salvaArticolo() {
    // Salvataggio via AJAX
    await salvaForm("#edit-form");

    $("#link_form").ajaxSubmit({
        url: globals.rootdir + "/actions.php",
        data: {
            id_module: globals.id_module,
            id_record: globals.id_record,
            ajax: true,
        },
        type: "post",
        beforeSubmit: function(arr, $form, options) {
            return $form.parsley().validate();
        },
        success: function(response){
            renderMessages();
            if(response.length > 0){
                response = JSON.parse(response);
                swal({
                    type: "error",
                    title: "'.tr('Errore').'",
                    text: response.error,
                });
            }

            $("#barcode").val("");
            $("#id_articolo").selectReset();
            content_was_modified = false;
            caricaRighe(null);
        }
    });
}

$("#link_form").bind("keypress", function(e) {
    if (e.keyCode == 13) {
        e.preventDefault();
        salvaArticolo();
        return false;
    }
});

input("ordinamento").on("change", function(){
    if (input(this).get() == "desc") {
        session_set("module_'.$id_module.',order_row_desc", 1, "").then(function () {
            caricaRighe(null);
        });
    } else {
        session_set("module_'.$id_module.',order_row_desc").then(function () {
            caricaRighe(null);
        });
    }
});

function risolviStato() {
    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "POST",
        dataType: "json",
        data: {
            id_module: globals.id_module,
            id_record: globals.id_record,
            op: "cambia_stato",
        },
        success: function (response) {
            location.reload();
        },
        error: function() {
            location.reload();
        }
    });
}

// Impostazione della sede di partenza
var sede_partenza = input("idsede_partenza");

sede_partenza.change(function() {
    updateSelectOption("idsede_partenza", $(this).val());
    session_set("superselect,idsede_partenza", $(this).val(), 0);
})
</script>';
