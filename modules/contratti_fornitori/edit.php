<?php

/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

include_once __DIR__.'/../../core.php';

if (empty($record)) {
    echo '<div class="alert alert-danger">'.tr('Contratto fornitore non trovato.').'</div>';

    return;
}

$collegamenti_rinnovo = [];

if (!empty($record['id_contratto_precedente'])) {
    $precedente = $dbo->fetchOne(
        'SELECT `id`, `numero` FROM `ac_contratti_fornitori` WHERE `id` = '.prepare($record['id_contratto_precedente']).' LIMIT 1'
    );

    if (!empty($precedente)) {
        $collegamenti_rinnovo[] = '<div class="col-md-6"><span class="text-muted">'.tr('Contratto precedente').'</span><br><a class="btn btn-default btn-sm" href="'.base_path_osm().'/controller.php?id_module='.$id_module.'&id_record='.$precedente['id'].'"><i class="fa fa-arrow-left"></i> '.htmlentities((string) $precedente['numero']).'</a></div>';
    }
}

if (!empty($record['id_contratto_successivo'])) {
    $successivo = $dbo->fetchOne(
        'SELECT `id`, `numero` FROM `ac_contratti_fornitori` WHERE `id` = '.prepare($record['id_contratto_successivo']).' LIMIT 1'
    );

    if (!empty($successivo)) {
        $collegamenti_rinnovo[] = '<div class="col-md-6"><span class="text-muted">'.tr('Contratto successivo').'</span><br><a class="btn btn-default btn-sm" href="'.base_path_osm().'/controller.php?id_module='.$id_module.'&id_record='.$successivo['id'].'">'.htmlentities((string) $successivo['numero']).' <i class="fa fa-arrow-right"></i></a></div>';
    }
}

if (!empty($collegamenti_rinnovo)) {
    echo '<div class="card card-light"><div class="card-header"><h3 class="card-title"><i class="fa fa-link"></i> '.tr('Collegamenti rinnovo').'</h3></div><div class="card-body"><div class="row">'.implode('', $collegamenti_rinnovo).'</div></div></div>';
}

$stato_corrente = $dbo->fetchOne(
    'SELECT `id`, `nome` FROM `ac_stati_contratti_fornitori` WHERE `id` = '.prepare($record['id_stato']).' LIMIT 1'
);

$stati_disponibili = $dbo->fetchArray(
    'SELECT `id`, `nome` FROM `ac_stati_contratti_fornitori` WHERE (`enabled` = 1 OR `id` = '.prepare($record['id_stato']).') AND `nome` != \'In scadenza\' ORDER BY `ordine`, `nome`'
);

$nomi_stati = [];
foreach ($stati_disponibili as $stato_disponibile) {
    $nomi_stati[(int) $stato_disponibile['id']] = $stato_disponibile['nome'];
}

$contratto_in_scadenza = ($stato_corrente['nome'] ?? null) === 'Attivo'
    && !empty($record['data_scadenza'])
    && $record['data_scadenza'] >= date('Y-m-d')
    && $record['data_scadenza'] <= date('Y-m-d', strtotime('+60 days'));

if ($contratto_in_scadenza) {
    echo '<div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i> <strong>'.tr('Contratto in scadenza').':</strong> '.tr('Scadenza prevista il _DATE_.', [
        '_DATE_' => dateFormat($record['data_scadenza']),
    ]).'</div>';
}

$transizioni_standard = [
    'Bozza' => ['Attivo', 'Disdetto'],
    'Attivo' => ['Disdetto', 'Terminato'],
    'Disdetto' => ['Attivo', 'Terminato'],
    'Terminato' => [],
];

echo '
<div class="row"><div class="col-md-9"></div><div class="col-md-3">
{[ "type": "select", "label": "'.tr('Stato').'", "name": "id_stato", "required": 1, "values": "query=SELECT id, nome AS descrizione, colore AS _bgcolor_ FROM ac_stati_contratti_fornitori WHERE (enabled=1 OR id=$id_stato$) AND nome!=\"In scadenza\" ORDER BY ordine,nome", "value": "$id_stato$", "form": "edit-form" ]}
</div></div>
<form action="" method="post" id="edit-form">
<input type="hidden" name="op" value="update">
<input type="hidden" name="backto" value="record-edit">
<input type="hidden" name="id_record" value="'.$id_record.'">
<input type="hidden" name="force_state_transition" id="force_state_transition" value="0">

<div class="card card-primary"><div class="card-header"><h3 class="card-title">'.tr('Contratto fornitore').'</h3></div><div class="card-body">
<div class="row"><div class="col-md-2">{[ "type":"text","label":"'.tr('Numero').'","name":"numero","required":1,"value":"$numero$" ]}</div><div class="col-md-7">{[ "type":"text","label":"'.tr('Descrizione contratto').'","name":"nome","required":1,"value":"$nome$" ]}</div><div class="col-md-3">{[ "type":"select","label":"'.tr('Sezionale').'","name":"id_segment","ajax-source":"segmenti","select-options":'.json_encode(['id_module' => $id_module, 'is_sezionale' => 1]).',"value":"$id_segment$","disabled":1 ]}</div></div>
<div class="row"><div class="col-md-5">{[ "type":"select","label":"'.tr('Fornitore').'","name":"id_fornitore","required":1,"ajax-source":"fornitori","value":"$id_fornitore$" ]}</div><div class="col-md-3">{[ "type":"select","label":"'.tr('Referente fornitore').'","name":"id_referente","ajax-source":"referenti","select-options":{"idanagrafica":"$id_fornitore$"},"value":"$id_referente$" ]}</div><div class="col-md-4">{[ "type":"select","label":"'.tr('Referente interno').'","name":"idagente","ajax-source":"agenti","value":"$idagente$" ]}</div></div>
<div class="row"><div class="col-md-4">{[ "type":"select","label":"'.tr('Categoria').'","name":"id_categoria","values":"query=SELECT id,nome AS descrizione FROM ac_categorie_contratti_fornitori WHERE enabled=1 ORDER BY nome","value":"$id_categoria$" ]}</div><div class="col-md-4">{[ "type":"text","label":"'.tr('Numero contratto fornitore').'","name":"numero_fornitore","value":"$numero_fornitore$" ]}</div><div class="col-md-4">{[ "type":"date","label":"'.tr('Data stipula').'","name":"data_stipula","value":"$data_stipula$" ]}</div></div>
</div></div>

<div class="card card-info"><div class="card-header"><h3 class="card-title"><i class="fa fa-calendar"></i> '.tr('Durata, rinnovo e disdetta').'</h3></div><div class="card-body">
<div class="mb-3"><strong><i class="fa fa-calendar-check-o"></i> '.tr('Durata del contratto').'</strong><div class="text-muted small">'.tr('Imposta la decorrenza e la modalità di calcolo della scadenza.').'</div></div>
<div class="row"><div class="col-md-3">{[ "type":"date","label":"'.tr('Data inizio').'","name":"data_inizio","value":"$data_inizio$","help":"'.tr('Data dalla quale il contratto produce effetti.').'" ]}</div><div class="col-md-3">{[ "type":"number","label":"'.tr('Validità contratto').'","name":"validita","decimals":0,"value":"$validita$","icon-after":"choice|period|$tipo_validita$","help":"'.tr('Durata usata per calcolare automaticamente la scadenza. Selezionare Manuale per inserire direttamente la data finale.').'" ]}</div><div class="col-md-3">{[ "type":"date","label":"'.tr('Data scadenza').'","name":"data_scadenza","value":"$data_scadenza$","help":"'.tr('Data finale del contratto. È calcolata automaticamente, salvo validità manuale.').'" ]}</div><div class="col-md-3">{[ "type":"number","label":"'.tr('Preavviso disdetta').'","name":"giorni_preavviso","decimals":0,"value":"$giorni_preavviso$","icon-after":"'.tr('giorni').'","help":"'.tr('Giorni di anticipo richiesti per comunicare la disdetta.').'" ]}</div></div>
<hr class="my-3">
<div class="row"><div class="col-md-4"><div class="border rounded p-3 h-100"><div class="mb-3"><strong><i class="fa fa-bell"></i> '.tr('Disdetta').'</strong><div class="text-muted small">'.tr('Il termine viene calcolato sottraendo il preavviso dalla data di scadenza.').'</div></div>{[ "type":"date","label":"'.tr('Termine ultimo per la disdetta').'","name":"data_limite_disdetta","value":"$data_limite_disdetta$","disabled":1,"help":"'.tr('Data entro cui inviare la comunicazione di disdetta.').'" ]}</div></div><div class="col-md-8"><div class="border rounded p-3 h-100"><div class="mb-3"><strong><i class="fa fa-repeat"></i> '.tr('Rinnovo').'</strong><div class="text-muted small">'.tr('Configura l’eventuale rinnovo automatico e le relative condizioni.').'</div></div><div class="row"><div class="col-md-4">{[ "type":"checkbox","label":"'.tr('Rinnovo automatico').'","name":"rinnovo_automatico","value":"$rinnovo_automatico$","help":"'.tr('Indica che il contratto si rinnova automaticamente alla scadenza.').'" ]}</div><div class="col-md-3 cf-renewal-field">{[ "type":"number","label":"'.tr('Durata rinnovo').'","name":"mesi_rinnovo","decimals":0,"value":"$mesi_rinnovo$","icon-after":"'.tr('mesi').'","help":"'.tr('Durata del nuovo periodo contrattuale.').'" ]}</div><div class="col-md-5 cf-renewal-field">{[ "type":"text","label":"'.tr('Condizioni di rinnovo').'","name":"condizioni_rinnovo","value":"$condizioni_rinnovo$","help":"'.tr('Eventuali variazioni di prezzo, durata o condizioni.').'" ]}</div></div></div></div></div>
</div></div>

<div class="card card-secondary"><div class="card-header"><h3 class="card-title">'.tr('Informazioni economiche indicative').'</h3></div><div class="card-body"><div class="row"><div class="col-md-3">{[ "type":"number","label":"'.tr('Importo indicativo').'","name":"importo","decimals":2,"value":"$importo$","icon-after":"€" ]}</div><div class="col-md-3">{[ "type":"select","label":"'.tr('Periodicità').'","name":"periodicita","values":"list=\"mensile\":\"Mensile\",\"bimestrale\":\"Bimestrale\",\"trimestrale\":\"Trimestrale\",\"semestrale\":\"Semestrale\",\"annuale\":\"Annuale\",\"una_tantum\":\"Una tantum\",\"variabile\":\"Variabile\"","value":"$periodicita$" ]}</div><div class="col-md-6">{[ "type":"text","label":"'.tr('Note economiche').'","name":"note_economiche","value":"$note_economiche$" ]}</div></div></div></div>
<div class="card card-light"><div class="card-header"><h3 class="card-title">'.tr('Note').'</h3></div><div class="card-body">{[ "type":"textarea","label":"'.tr('Note operative').'","name":"note","value":"$note$","rows":6 ]}</div></div>
</form>
{( "name": "filelist_and_upload", "id": "'.random_int(1, 999).'", "id_record": "'.$id_record.'", "id_module": "'.$id_module.'", "id_plugin": "" )}
<div class="row"><div class="col-md-12"><a class="btn btn-danger ask" data-backto="record-list" data-op="delete" data-msg="'.tr('Eliminare questo contratto fornitore?').'"><i class="fa fa-trash"></i> '.tr('Elimina').'</a></div></div>';
?>
<script>
$(document).ready(function () {
    var form = $('#edit-form');
    var stateField = $('select[name="id_stato"][form="edit-form"]').first();
    var forceField = $('#force_state_transition');
    var originalStateId = <?= json_encode((int) $record['id_stato']) ?>;
    var originalStateName = <?= json_encode((string) ($stato_corrente['nome'] ?? '')) ?>;
    var stateNames = <?= json_encode($nomi_stati, JSON_UNESCAPED_UNICODE) ?>;
    var standardTransitions = <?= json_encode($transizioni_standard, JSON_UNESCAPED_UNICODE) ?>;
    var confirmed = false;
    var stateTouched = false;

    stateField.on('change.contrattiFornitoriState', function () {
        stateTouched = true;
        forceField.val('0');
    });

    function updateRenewalFields() {
        var checkbox = $('[name="rinnovo_automatico"]');
        var enabled = checkbox.is(':checked') || checkbox.val() === '1';

        $('.cf-renewal-field').toggleClass('text-muted', !enabled);
        $('.cf-renewal-field input').prop('readonly', !enabled);
        $('.cf-renewal-field').css('opacity', enabled ? '1' : '0.65');
    }

    $(document).on('change', '[name="rinnovo_automatico"]', updateRenewalFields);
    setTimeout(updateRenewalFields, 250);

    form.on('submit.contrattiFornitoriState', function (event) {
        if (confirmed || forceField.val() === '1') {
            return true;
        }

        var newStateId = parseInt(stateField.val(), 10);

        if (!stateTouched || !newStateId || newStateId === originalStateId) {
            return true;
        }

        var newStateName = stateNames[newStateId] || '';
        var allowed = standardTransitions[originalStateName] || [];

        if (allowed.indexOf(newStateName) !== -1) {
            return true;
        }

        event.preventDefault();

        swal({
            title: <?= json_encode(tr('Confermare il cambio di stato?')) ?>,
            text: <?= json_encode(tr('Il passaggio selezionato non segue il normale flusso del contratto.')) ?> + '\n\n' + originalStateName + ' → ' + newStateName + '\n\n' + <?= json_encode(tr('Continuare comunque?')) ?>,
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f39c12',
            confirmButtonText: <?= json_encode(tr('Conferma e salva')) ?>,
            cancelButtonText: <?= json_encode(tr('Annulla')) ?>,
            closeOnConfirm: true
        }, function (isConfirmed) {
            if (!isConfirmed) {
                return;
            }

            confirmed = true;
            forceField.val('1');
            form.off('submit.contrattiFornitoriState');
            form.trigger('submit');
        });

        return false;
    });
});
</script>