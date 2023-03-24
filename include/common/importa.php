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

use Plugins\ListinoFornitori\DettaglioFornitore;

// Inizializzazione
$documento = $options['documento'];
$documento_finale = $options['documento_finale'];
if (empty($documento)) {
    return;
}

// Informazioni utili
$dir = $documento->direzione;
$original_module = Modules::get($documento->module);

$name = !empty($documento_finale) ? $documento_finale->module : $options['module'];
$final_module = Modules::get($name);
$id_segment = $_SESSION['module_'.$final_module['id']]['id_segment'];

// IVA predefinita
$id_iva = $id_iva ?: setting('Iva predefinita');

$righe_totali = $documento->getRighe();
if ($final_module['name'] == 'Interventi') {
    $righe = $righe_totali->where('is_descrizione', '=', 0)
        ->where('qta_rimanente', '>', 0);
    $righe_evase = $righe_totali->where('is_descrizione', '=', 0)
        ->where('qta_rimanente', '=', 0);
} elseif ($final_module['name'] == 'Ordini fornitore') {
    $righe = $righe_totali;
    $righe_evase = collect();
} else {
    $righe = $righe_totali->where('qta_rimanente', '>', 0);
    $righe_evase = $righe_totali->where('qta_rimanente', '=', 0);
}

if ($righe->isEmpty()) {
    echo '
<p>'.tr('Non ci sono elementi da evadere').'...</p>';

    return;
}

$link = !empty($documento_finale) ? base_path().'/editor.php?id_module='.$final_module['id'].'&id_record='.$documento_finale->id : base_path().'/controller.php?id_module='.$final_module['id'];

echo '
<form action="'.$link.'" method="post">
    <input type="hidden" name="op" value="'.$options['op'].'">
    <input type="hidden" name="backto" value="record-edit">

    <input type="hidden" name="id_documento" value="'.$documento->id.'">
    <input type="hidden" name="type" value="'.$options['type'].'">
    <input type="hidden" name="class" value="'.get_class($documento).'">
    <input type="hidden" name="is_evasione" value="1">';

    if ($options['type'] == 'ordine') {
        echo '<input type="hidden" name="id_sede_partenza" value="'.$options['documento']['id_sede_partenza'].'">';
    }

// Creazione fattura dal documento
if (!empty($options['create_document'])) {
    echo '
    <div class="box box-warning">
        <div class="box-header with-border">
            <h3 class="box-title">'.tr('Nuovo documento').'</h3>
        </div>
        <div class="box-body">

            <div class="row">
                <input type="hidden" name="create_document" value="on" />

                <div class="col-md-6">
                    {[ "type": "date", "label": "'.tr('Data del documento').'", "name": "data", "required": 1, "value": "-now-" ]}
                </div>';

    // Opzioni aggiuntive per le Fatture
    if (in_array($final_module['name'], ['Fatture di vendita', 'Fatture di acquisto'])) {
        $stato_predefinito = $database->fetchOne("SELECT id FROM co_statidocumento WHERE descrizione = 'Bozza'");

        if (!empty($options['reversed'])) {
            $idtipodocumento = $dbo->selectOne('co_tipidocumento', ['id'], [
                'dir' => $dir,
                'descrizione' => 'Nota di credito',
            ])['id'];
        } elseif (in_array($original_module['name'], ['Ddt di vendita', 'Ddt di acquisto'])) {
            $idtipodocumento = $dbo->selectOne('co_tipidocumento', ['id'], [
                'dir' => $dir,
                'descrizione' => ($dir == 'uscita' ? 'Fattura differita di acquisto' : 'Fattura differita di vendita'),
            ])['id'];
        } else {
            $idtipodocumento = $dbo->selectOne('co_tipidocumento', ['id'], [
                'predefined' => 1,
                'dir' => $dir,
            ])['id'];
        }

        echo '
            <div class="col-md-6">
                {[ "type": "select", "label": "'.tr('Stato').'", "name": "id_stato", "required": 1, "values": "query=SELECT * FROM co_statidocumento WHERE descrizione IN (\'Emessa\', \'Bozza\')", "value": "'.$stato_predefinito['id'].'"]}
            </div>

            <div class="col-md-6">
                {[ "type": "select", "label": "'.tr('Tipo documento').'", "name": "idtipodocumento", "required": 1, "values": "query=SELECT id, CONCAT(codice_tipo_documento_fe, \' - \', descrizione) AS descrizione FROM co_tipidocumento WHERE enabled = 1 AND dir = '.prepare($dir).' ORDER BY codice_tipo_documento_fe", "value": "'.$idtipodocumento.'" ]}
            </div>

            <div class="col-md-6">
                {[ "type": "select", "label": "'.tr('Ritenuta previdenziale').'", "name": "id_ritenuta_contributi", "value": "$id_ritenuta_contributi$", "values": "query=SELECT * FROM co_ritenuta_contributi" ]}
            </div>';
    }

    // Opzioni aggiuntive per gli Interventi
    elseif ($final_module['name'] == 'Interventi') {
        echo '
            <div class="col-md-6">
                {[ "type": "select", "label": "'.tr('Stato').'", "name": "id_stato_intervento", "required": 1, "values": "query=SELECT idstatointervento AS id, descrizione, colore AS _bgcolor_ FROM in_statiintervento WHERE deleted_at IS NULL ORDER BY descrizione" ]}
            </div>

            <div class="col-md-6">
                {[ "type": "select", "label": "'.tr('Tipo').'", "name": "id_tipo_intervento", "required": 1, "values": "query=SELECT idtipointervento AS id, descrizione FROM in_tipiintervento" ]}
            </div>';
    }

    // Opzioni aggiuntive per i Contratti
    elseif ($final_module['name'] == 'Contratti') {
        $stato_predefinito = $database->fetchOne("SELECT * FROM co_staticontratti WHERE descrizione = 'Bozza'");

        echo '
            <div class="col-md-6">
                {[ "type": "select", "label": "'.tr('Stato').'", "name": "id_stato", "required": 1, "values": "query=SELECT id, descrizione FROM co_staticontratti", "value": "'.$stato_predefinito['id'].'" ]}
            </div>';
    }

    // Opzioni aggiuntive per i DDT
    elseif (in_array($final_module['name'], ['Ddt di vendita', 'Ddt di acquisto'])) {
        $stato_predefinito = $database->fetchOne("SELECT * FROM dt_statiddt WHERE descrizione = 'Bozza'");

        echo '
            <div class="col-md-6">
                {[ "type": "select", "label": "'.tr('Stato').'", "name": "id_stato", "required": 1, "values": "query=SELECT * FROM dt_statiddt", "value": "'.$stato_predefinito['id'].'" ]}
            </div>

            <div class="col-md-6">
                {[ "type": "select", "label": "'.tr('Causale trasporto').'", "name": "id_causale_trasporto", "required": 1, "ajax-source": "causali", "icon-after": "add|'.Modules::get('Causali')['id'].'", "help": "'.tr('Definisce la causale del trasporto').'" ]}
            </div>';
    }

    // Opzioni aggiuntive per gli Ordini
    elseif (in_array($final_module['name'], ['Ordini cliente', 'Ordini fornitore'])) {
        $stato_predefinito = $database->fetchOne("SELECT * FROM or_statiordine WHERE descrizione = 'Bozza'");

        echo '
            <div class="col-md-6">
                {[ "type": "select", "label": "'.tr('Stato').'", "name": "id_stato", "required": 1, "values": "query=SELECT * FROM or_statiordine WHERE descrizione IN(\'Bozza\', \'Accettato\', \'In attesa di conferma\', \'Annullato\')", "value": "'.$stato_predefinito['id'].'" ]}
            </div>';
    }

    // Selezione fornitore per Ordine fornitore
    if ($options['op'] == 'add_ordine_cliente' || $options['op'] == 'add_intervento' || $options['op'] == 'add_ordine_fornitore') {
        $tipo_anagrafica = $options['op'] == 'add_intervento' ? tr('Cliente') : tr('Fornitore');
        $ajax = $options['op'] == 'add_intervento' ? 'clienti' : 'fornitori';

        echo '
            <div class="col-md-6">
                {[ "type": "select", "label": "'.$tipo_anagrafica.'", "name": "idanagrafica", "required": 1, "ajax-source": "'.$ajax.'", "icon-after": "add|'.Modules::get('Anagrafiche')['id'].'|tipoanagrafica='.$tipo_anagrafica.'" ]}
            </div>';
    }

    if ($options['type'] == 'preventivo' && ($options['op'] == 'add_ordine_cliente' || $options['op'] == 'add_ordine_fornitore' || $options['op'] == 'add_preventivo')) {
        echo '
            <div class="col-md-6">
			    {[ "type": "select", "label": "'.tr('Sede di partenza').'", "name": "id_sede_partenza", "required": 1, "ajax-source": "sedi-partenza", "select-options": '.json_encode(['id_module' => $id_module, 'is_sezionale' => 1]).', "value": "0" ]}
            </div>';
    }

    echo '
                <div class="col-md-6">
                    {[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "ajax-source": "segmenti", "select-options": '.json_encode(['id_module' => $final_module['id'], 'is_sezionale' => 1]).', "value": "'.$id_segment.'" ]}
                </div>
            </div>
        </div>
    </div>';
}

// Conto, rivalsa INPS, ritenuta d'acconto e ritenuta previdenziale
if (in_array($final_module['name'], ['Fatture di vendita', 'Fatture di acquisto']) && !in_array($original_module['name'], ['Fatture di vendita', 'Fatture di acquisto'])) {
    $id_rivalsa_inps = setting('Cassa previdenziale predefinita');
    if ($dir == 'uscita') {
        $id_ritenuta_acconto = $documento->anagrafica->id_ritenuta_acconto_acquisti;
    } else {
        $id_ritenuta_acconto = $documento->anagrafica->id_ritenuta_acconto_vendite ?: setting("Ritenuta d'acconto predefinita");
    }
    $calcolo_ritenuta_acconto = setting("Metodologia calcolo ritenuta d'acconto predefinito");

    $show_ritenuta_contributi = !empty($documento_finale['id_ritenuta_contributi']);

    $id_conto = $documento_finale['idconto'];
    if (empty($id_conto)) {
        $id_conto = $dir == 'entrata' ? setting('Conto predefinito fatture di vendita') : setting('Conto predefinito fatture di acquisto');
    }

    echo '
    <div class="box box-info">
        <div class="box-header with-border">
            <h3 class="box-title">'.tr('Opzioni generali delle righe').'</h3>
        </div>
        <div class="box-body">';

    echo '
            <div class="row">';

    // Rivalsa INPS
    echo '
                <div class="col-md-4">
                    {[ "type": "select", "label": "'.tr('Rivalsa').'", "name": "id_rivalsa_inps", "value": "'.$id_rivalsa_inps.'", "values": "query=SELECT * FROM co_rivalse", "help": "'.($options['dir'] == 'entrata' ? setting('Tipo Cassa Previdenziale') : null).'" ]}
                </div>';

    // Ritenuta d'acconto
    echo '
                <div class="col-md-4">
                    {[ "type": "select", "label": "'.tr("Ritenuta d'acconto").'", "name": "id_ritenuta_acconto", "value": "'.$id_ritenuta_acconto.'", "values": "query=SELECT * FROM co_ritenutaacconto" ]}
                </div>';

    // Calcola ritenuta d'acconto su
    echo '
                <div class="col-md-4">
                    {[ "type": "select", "label": "'.tr("Calcola ritenuta d'acconto su").'", "name": "calcolo_ritenuta_acconto", "value": "'.$calcolo_ritenuta_acconto.'", "values": "list=\"IMP\":\"Imponibile\", \"IMP+RIV\":\"Imponibile + rivalsa\"", "required": "1" ]}
                </div>';

    echo '
            </div>';

    $width = $show_ritenuta_contributi ? 6 : 12;

    echo '
            <div class="row">';

    // Ritenuta previdenziale
    if ($show_ritenuta_contributi) {
        echo '
                <div class="col-md-'.$width.'">
                    {[ "type": "checkbox", "label": "'.tr('Ritenuta previdenziale').'", "name": "ritenuta_contributi", "value": "1" ]}
                </div>';
    }

    // Conto
    echo '
                <div class="col-md-'.$width.'">
                    {[ "type": "select", "label": "'.tr('Conto').'", "name": "id_conto", "required": 1, "value": "'.$id_conto.'", "ajax-source": "'.($dir == 'entrata' ? 'conti-vendite' : 'conti-acquisti').'" ]}
                </div>
            </div>
        </div>
    </div>';
}

$has_serial = 0;
if (!empty($options['serials'])) {
    foreach ($righe as $riga) {
        if (!empty($riga['abilita_serial'])) {
            $serials = $riga->serials ?: 0;

            if (!empty($serials)) {
                $has_serial = 1;
            }
        }
    }
}

// Righe del documento
echo '
    <div class="box box-success">
        <div class="box-header with-border">
            <h3 class="box-title">'.tr('Righe da importare').'</h3>
        </div>

        <table class="box-body table table-striped table-hover table-condensed">
            <thead>
                <tr>
                    <th width="2%"><input id="import_all" type="checkbox" checked/></th>
                    <th>'.tr('Descrizione').'</th>
                    <th width="10%" class="text-center">'.tr('Q.tà').'</th>
                    <th width="15%">'.tr('Q.tà da evadere').'</th>
                    <th width="20%" class="text-center">'.tr('Subtot.').'</th>';

if (!empty($has_serial)) {
    echo '
                    <th width="20%">'.tr('Seriali').'</th>';
}

echo '
                </tr>
            </thead>
            <tbody id="righe_documento_importato">';

foreach ($righe as $i => $riga) {
    if ($final_module['name'] == 'Ordini fornitore') {
        $qta_rimanente = $riga['qta'];
    } else {
        $qta_rimanente = $riga['qta_rimanente'];
    }

    $attr = 'checked="checked"';
    if ($original_module['name'] == 'Preventivi') {
        if (empty($riga['confermato']) && $riga['is_descrizione'] == 0) {
            $attr = '';
        }
    }

    // Descrizione
    echo '
                <tr data-local_id="'.$i.'">
                    <td style="vertical-align:middle">
                        <input class="check" type="checkbox" '.$attr.' id="checked_'.$i.'" name="evadere['.$riga['id'].']" value="on" onclick="ricalcolaTotaleRiga('.$i.');" />
                    </td>
                    <td style="vertical-align:middle">
                        <span class="hidden" id="id_articolo_'.$i.'">'.$riga['idarticolo'].'</span>

                        <input type="hidden" class="righe" name="righe" value="'.$i.'"/>
                        <input type="hidden" id="prezzo_unitario_'.$i.'" name="subtot['.$riga['id'].']" value="'.$riga['prezzo_unitario'].'" />
                        <input type="hidden" id="sconto_unitario_'.$i.'" name="sconto['.$riga['id'].']" value="'.$riga['sconto_unitario'].'" />
                        <input type="hidden" id="max_qta_'.$i.'" value="'.($options['superamento_soglia_qta'] ? '' : $riga['qta_rimanente']).'" />';

    $descrizione = ($riga->isArticolo() ? $riga->articolo->codice.' - ' : '').$riga['descrizione'];

    echo '&nbsp;'.nl2br($descrizione);

    if( $riga->isArticolo() ){
        $dettaglio_fornitore = DettaglioFornitore::where('id_articolo', $riga->idarticolo)
            ->where('id_fornitore', $documento->idanagrafica)
            ->first();

        if( !empty($dettaglio_fornitore->codice_fornitore) ){
            echo '
            <br><small class="text-muted">'.tr('Codice fornitore ').': '.$dettaglio_fornitore->codice_fornitore.'</small>';
        }
    }

    if ($riga->isArticolo() && !empty($riga->abilita_serial)) {
        $serials = $riga->serials;
        $mancanti = abs($riga->qta) - count($serials);

        if (!empty($mancanti)) {
            echo '
                <br><b><small class="text-danger">'.tr('_NUM_ serial mancanti', [
                        '_NUM_' => $mancanti,
                    ]).'</small></b>';
        }
    }


    echo '
                    </td>';

    // Q.tà rimanente
    echo '
                    <td class="text-center" style="vertical-align:middle">
                        '.numberFormat($qta_rimanente).'
                    </td>';

    // Q.tà da evadere
    echo '
                    <td style="vertical-align:middle">
                        {[ "type": "number", "name": "qta_da_evadere['.$riga['id'].']", "id": "qta_'.$i.'", "required": 1, "value": "'.$qta_rimanente.'", "decimals": "qta", "min-value": "0", "extra": "'.(($riga['is_descrizione']) ? 'readonly' : '').' onkeyup=\"ricalcolaTotaleRiga('.$i.');\"" ]}
                    </td>';

    echo '
                    <td style="vertical-align:middle" class="text-right">
                        <span id="subtotale_'.$i.'"></span>
                    </td>';

    // Seriali
    if (!empty($has_serial)) {
        echo '
                    <td style="vertical-align:middle">';

        if (!empty($riga['abilita_serial'])) {
            $serials = $riga->serials;

            $list = [];
            foreach ($serials as $serial) {
                $list[] = [
                    'id' => $serial,
                    'text' => $serial,
                ];
            }

            if (!empty($serials)) {
                echo '
                        {[ "type": "select", "name": "serial['.$riga['id'].'][]", "id": "serial_'.$i.'", "multiple": 1, "values": '.json_encode($list).', "value": "'.implode(',', $serials).'", "extra": "data-maximum=\"'.intval($riga['qta_rimanente']).'\"" ]}';
            }
        }

        echo '
                    </td>';
    }

    echo '
             </tr>';
}

// Totale
echo '
            </tbody>

            <tr>
                <td colspan="3" class="text-right">
                    <b>'.tr('Totale').':</b>
                </td>
                <td class="text-right">
                    <span id="totale"></span>
                </td>
            </tr>
        </table>
    </div>';

// Elenco righe evase completametne
if (!$righe_evase->isEmpty()) {
    echo '
    <div class="box box-info collapsable collapsed-box">
        <div class="box-header with-border">
            <h3 class="box-title">'.tr('Righe evase completamente').'</h3>
            <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i></button>
            </div>
        </div>

        <table class="box-body table table-striped table-hover table-condensed">
            <thead>
                <tr>
                    <th>'.tr('Descrizione').'</th>
                    <th width="10%" class="text-center">'.tr('Q.tà').'</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($righe_evase as $riga) {
        echo '
                <tr>
                    <td>'.$riga->descrizione.'</td>
                    <td class="text-center">'.numberFormat($riga->qta, 'qta').' '.$riga->um.'</td>
                </tr>';
    }

    echo '
            </tbody>
        </table>
    </div>';
}

// Gestione articolo sottoscorta
echo '
    <div class="alert alert-warning hidden" id="articoli_sottoscorta">
        <table class="table table-condensed">
            <thead>
                <tr>
                    <th>'.tr('Articolo').'</th>
                    <th class="text-center tip" width="150" title="'.tr('Quantità richiesta').'">'.tr('Q.tà').'</th>
                    <th class="text-center tip" width="150" title="'.tr('Quantità disponibile nel magazzino del gestionale').'">'.tr('Q.tà magazzino').'</th>
                    <th class="text-center" width="150">'.tr('Scarto').'</th>
                </tr>
            </thead>

            <tbody></tbody>
        </table>
    </div>';

echo '

    <!-- PULSANTI -->
    <div class="row">
        <div class="col-md-12 text-right">
            <button type="submit" id="submit_btn" class="btn btn-primary pull-right">
                <i class="fa fa-plus"></i> '.$options['button'].'
            </button>
        </div>
    </div>
</form>';

echo '
<script>$(document).ready(init)</script>';

// Individuazione scorte
$articoli = $documento->articoli->groupBy('idarticolo');
$scorte = [];
foreach ($articoli as $elenco) {
    $qta = $elenco->sum('qta');
    $articolo = $elenco->first()->articolo;

    $descrizione_riga = $articolo->codice.' - '.$articolo->descrizione;
    $text = $articolo ? Modules::link('Articoli', $articolo->id, $descrizione_riga) : $descrizione_riga;

    $scorte[$articolo->id] = [
        'qta' => $articolo->qta,
        'descrizione' => $text,
        'servizio' => $articolo->servizio,
    ];
}

echo '
<script type="text/javascript">
    var scorte = '.json_encode($scorte).';
    var permetti_documento_vuoto = '.intval(!empty($options['allow-empty'])).';
    var abilita_scorte = '.intval(!$documento::$movimenta_magazzino && !empty($options['tipo_documento_finale']) && $options['tipo_documento_finale']::$movimenta_magazzino).';

    function controllaMagazzino() {
        if(!abilita_scorte) return;

        let righe = $("#righe_documento_importato tr");

        // Lettura delle righe selezionate per l\'improtazione
        let richieste = {};
        for(const r of righe) {
            let riga = $(r);
            let id = $(riga).data("local_id");
            let id_articolo = riga.find("[id^=id_articolo_]").text();

            if (!$("#checked_" + id).is(":checked") || !id_articolo) {
                continue;
            }

            let qta = parseFloat(riga.find("input[id^=qta_]").val());
            richieste[id_articolo] = richieste[id_articolo] ? richieste[id_articolo] + qta : qta;
        }

        let sottoscorta = $("#articoli_sottoscorta");
        let body = sottoscorta.find("tbody");
        body.html("");

        for(const id_articolo in richieste) {
            let qta_scorta = parseFloat(scorte[id_articolo]["qta"]);
            let qta_richiesta = parseFloat(richieste[id_articolo]);
            if ((qta_richiesta > qta_scorta) && (scorte[id_articolo]["servizio"] !== 1)) {
                body.append(`<tr>
            <td>` + scorte[id_articolo]["descrizione"] + `</td>
            <td class="text-right">` + qta_richiesta.toLocale() + `</td>
            <td class="text-right">` + qta_scorta.toLocale() + `</td>
            <td class="text-right">` + (qta_richiesta - qta_scorta).toLocale() + `</td>
        </tr>`);
            }
        }

        if (body.html()) {
            sottoscorta.removeClass("hidden");
        } else {
            sottoscorta.addClass("hidden");
        }
    }

    $("input[name=righe]").each(function() {
        ricalcolaTotaleRiga($(this).val(), first = true);
    });

    function ricalcolaTotaleRiga(r, first) {
        let prezzo_unitario = $("#prezzo_unitario_" + r).val();
        let sconto = $("#sconto_unitario_" + r).val();

        let max_qta_input = $("#max_qta_" + r);
        let qta_max = max_qta_input.val();

        prezzo_unitario = parseFloat(prezzo_unitario);
        sconto = parseFloat(sconto);
        qta_max = parseFloat(qta_max);

        let prezzo_scontato = prezzo_unitario - sconto;

        let qta = ($("#qta_" + r).val()).toEnglish();

        // Se inserisco una quantità da evadere maggiore di quella rimanente, la imposto al massimo possibile
        if (qta > qta_max) {
            qta = qta_max;

            $("#qta_" + r).val(qta);
        }

        // Se tolgo la spunta della casella dell\'evasione devo azzerare i conteggi
        if (isNaN(qta) || !$("#checked_" + r).is(":checked")) {
            qta = 0;
        }

        if (!first) {
            let serial_select = $("#serial_" + r);
            serial_select.selectClear();
            serial_select.data("maximum", qta);
            initSelectInput("#serial_" + r);
        }

        let subtotale = (prezzo_scontato * qta).toLocale();

        $("#subtotale_" + r).html(subtotale + " " + globals.currency);


        ricalcolaTotale();
    }

    function ricalcolaTotale() {
        let totale = 0.00;
        let totale_qta = 0;

        $("input[id*=qta_]").each(function() {
            let qta = $(this).val().toEnglish();
            let r = $(this).attr("id").replace("qta_", "");

            if (!$("#checked_" + r).is(":checked") || isNaN(qta)) {
                qta = 0;
            }

            let prezzo_unitario = $("#prezzo_unitario_" + r).val();
            let sconto = $("#sconto_unitario_" + r).val();

            prezzo_unitario = parseFloat(prezzo_unitario);
            sconto = parseFloat(sconto);

            let prezzo_scontato = prezzo_unitario - sconto;

            if(prezzo_scontato) {
                totale += prezzo_scontato * qta;
            }

            totale_qta += qta;
        });

        $("#totale").html((totale.toLocale()) + " " + globals.currency);

        if (!permetti_documento_vuoto) {
            if (totale_qta > 0) {
                $("#submit_btn").show();
            } else {
                $("#submit_btn").hide();
            }
        }

        controllaMagazzino();
    }

    $(document).ready(function(){
        if(input("id_ritenuta_acconto").get()) {
            $("#calcolo_ritenuta_acconto").prop("required", true);
        } else{
            $("#calcolo_ritenuta_acconto").prop("required", false);
            input("calcolo_ritenuta_acconto").set("");
        }

        $("#id_ritenuta_acconto").on("change", function(){
            if(input("id_ritenuta_acconto").get()) {
                $("#calcolo_ritenuta_acconto").prop("required", true);
            } else{
                $("#calcolo_ritenuta_acconto").prop("required", false);
                input("calcolo_ritenuta_acconto").set("");
            }
        });
        ricalcolaTotale();
    });

    $("#import_all").click(function(){
        if( $(this).is(":checked") ){
            $(".check").each(function(){
                if( !$(this).is(":checked") ){
                    $(this).trigger("click");
                }
            });
        }else{
            $(".check").each(function(){
                if( $(this).is(":checked") ){
                    $(this).trigger("click");
                }
            });
        }
    });
</script>';
