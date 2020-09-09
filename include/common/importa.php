<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

// Inizializzazione
$documento = $options['documento'];
$documento_finale = $options['documento_finale'];
if (empty($documento) || (!empty($documento_finale) && $documento_finale->direzione != $documento->direzione)) {
    return;
}

// Informazioni utili
$dir = $documento->direzione;
$original_module = Modules::get($documento->module);

$name = !empty($documento_finale) ? $documento_finale->module : $options['module'];
$final_module = Modules::get($name);

// IVA predefinita
$id_iva = $id_iva ?: setting('Iva predefinita');

$righe = $documento->getRighe()->where('qta_rimanente', '>', 0);
if (empty($righe)) {
    echo '
<p>'.tr('Non ci sono elementi da evadere').'...</p>';

    return;
}

$link = !empty($documento_finale) ? ROOTDIR.'/editor.php?id_module='.$final_module['id'].'&id_record='.$documento_finale->id : ROOTDIR.'/controller.php?id_module='.$final_module['id'];

echo '
<form action="'.$link.'" method="post">
    <input type="hidden" name="op" value="'.$options['op'].'">
    <input type="hidden" name="backto" value="record-edit">

    <input type="hidden" name="id_documento" value="'.$documento->id.'">
    <input type="hidden" name="type" value="'.$options['type'].'">
    <input type="hidden" name="class" value="'.get_class($documento).'">
    <input type="hidden" name="is_evasione" value="1">';

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
        if ($options['op'] == 'nota_accredito' && !empty($segmenti)) {
            $segmento = $database->fetchOne("SELECT * FROM zz_segments WHERE predefined_accredito='1'");

            $id_segment = $segmento['id'];
        } else {
            $id_segment = $_SESSION['module_'.$final_module['id']]['id_segment'];
        }

        $stato_predefinito = $database->fetchOne("SELECT * FROM co_statidocumento WHERE descrizione = 'Bozza'");

        echo '
            <div class="col-md-6">
                {[ "type": "select", "label": "'.tr('Stato').'", "name": "id_stato", "required": 1, "values": "query=SELECT * FROM co_statidocumento WHERE descrizione IN (\'Emessa\', \'Bozza\')", "value": "'.$stato_predefinito['id'].'"]}
            </div>

            <div class="col-md-6">
                {[ "type": "select", "label": "'.tr('Ritenuta contributi').'", "name": "id_ritenuta_contributi", "value": "$id_ritenuta_contributi$", "values": "query=SELECT * FROM co_ritenuta_contributi" ]}
            </div>

            <div class="col-md-6">
                {[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "values": "query=SELECT id, name AS descrizione FROM zz_segments WHERE id_module='.prepare($final_module['id']).' ORDER BY name", "value": "'.$id_segment.'" ]}
            </div>';
    }

    // Opzioni aggiuntive per gli Interventi
    elseif ($final_module['name'] == 'Interventi') {
        echo '
            <div class="col-md-6">
                {[ "type": "select", "label": "'.tr('Stato').'", "name": "id_stato_intervento", "required": 1, "values": "query=SELECT idstatointervento AS id, descrizione, colore AS _bgcolor_ FROM in_statiintervento WHERE deleted_at IS NULL" ]}
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
    if ($options['op'] == 'add_ordine_cliente') {
        $tipo_anagrafica = tr('Fornitore');
        $ajax = 'fornitori';

        echo '
            <div class="col-md-6">
                {[ "type": "select", "label": "'.$tipo_anagrafica.'", "name": "idanagrafica", "required": 1, "ajax-source": "'.$ajax.'", "icon-after": "add|'.Modules::get('Anagrafiche')['id'].'|tipoanagrafica='.$tipo_anagrafica.'" ]}
            </div>';
    }

    echo '
            </div>
        </div>
    </div>';
}

// Conto, rivalsa INPS, ritenuta d'acconto e ritenuta contributi
if (in_array($final_module['name'], ['Fatture di vendita', 'Fatture di acquisto']) && !in_array($original_module['name'], ['Fatture di vendita', 'Fatture di acquisto'])) {
    $id_rivalsa_inps = setting('Percentuale rivalsa');
    if ($dir == 'uscita') {
        $id_ritenuta_acconto = $documento->anagrafica->id_ritenuta_acconto_acquisti;
    } else {
        $id_ritenuta_acconto = $documento->anagrafica->id_ritenuta_acconto_vendite ?: setting("Percentuale ritenuta d'acconto");
    }
    $calcolo_ritenuta_acconto = setting("Metodologia calcolo ritenuta d'acconto predefinito");

    $show_rivalsa = !empty($id_rivalsa_inps);
    $show_ritenuta_acconto = setting("Percentuale ritenuta d'acconto") != '' || !empty($id_ritenuta_acconto);
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

    if ($show_rivalsa || $show_ritenuta_acconto) {
        echo '
            <div class="row">';

        // Rivalsa INPS
        if ($show_rivalsa) {
            echo '
                <div class="col-md-4">
                    {[ "type": "select", "label": "'.tr('Rivalsa').'", "name": "id_rivalsa_inps", "value": "'.$id_rivalsa_inps.'", "values": "query=SELECT * FROM co_rivalse", "help": "'.($options['dir'] == 'entrata' ? setting('Tipo Cassa Previdenziale') : null).'" ]}
                </div>';
        }

        // Ritenuta d'acconto
        if ($show_ritenuta_acconto) {
            echo '
                <div class="col-md-4">
                    {[ "type": "select", "label": "'.tr("Ritenuta d'acconto").'", "name": "id_ritenuta_acconto", "value": "'.$id_ritenuta_acconto.'", "values": "query=SELECT * FROM co_ritenutaacconto" ]}
                </div>';

            // Calcola ritenuta d'acconto su
            echo '
                <div class="col-md-4">
                    {[ "type": "select", "label": "'.tr("Calcola ritenuta d'acconto su").'", "name": "calcolo_ritenuta_acconto", "value": "'.$calcolo_ritenuta_acconto.'", "values": "list=\"IMP\":\"Imponibile\", \"IMP+RIV\":\"Imponibile + rivalsa\"", "required": "1" ]}
                </div>';
        }

        echo '
            </div>';
    }

    $width = $show_ritenuta_contributi ? 6 : 12;

    echo '
            <div class="row">';

    // Ritenuta contributi
    if ($show_ritenuta_contributi) {
        echo '
                <div class="col-md-'.$width.'">
                    {[ "type": "checkbox", "label": "'.tr('Ritenuta contributi').'", "name": "ritenuta_contributi", "value": "1" ]}
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

// Righe del documento
echo '
    <div class="box box-success">
        <div class="box-header with-border">
            <h3 class="box-title">'.tr('Righe da importare').'</h3>
        </div>

        <table class="box-body table table-striped table-hover table-condensed">
            <tr>
                <th>'.tr('Descrizione').'</th>
                <th width="10%">'.tr('Q.tà').'</th>
                <th width="15%">'.tr('Q.tà da evadere').'</th>
                <th width="20%">'.tr('Subtot.').'</th>';

if (!empty($options['serials'])) {
    echo '
                <th width="20%">'.tr('Seriali').'</th>';
}

echo '
            </tr>';

foreach ($righe as $i => $riga) {
    // Descrizione
    echo '
            <tr>
                <td>
                    <input type="hidden" id="prezzo_unitario_'.$i.'" name="subtot['.$riga['id'].']" value="'.$riga['prezzo_unitario'].'" />
                    <input type="hidden" id="sconto_unitario_'.$i.'" name="sconto['.$riga['id'].']" value="'.$riga['sconto_unitario'].'" />
                    <input type="hidden" id="iva_unitaria_'.$i.'" name="iva['.$riga['id'].']" value="'.$riga['iva_unitaria'].'" />
                    <input type="hidden" id="qta_max_'.$i.'" value="'.($riga['qta_rimanente']).'" />';

    // Checkbox - da evadere?
    echo '
                    <input type="checkbox" checked="checked" id="checked_'.$i.'" name="evadere['.$riga['id'].']" value="on" onclick="ricalcola_subtotale_riga('.$i.');" />';

    $descrizione = ($riga->isArticolo() ? $riga->articolo->codice.' - ' : '').$riga['descrizione'];

    echo '&nbsp;'.nl2br($descrizione);

    echo '
                </td>';

    // Q.tà rimanente
    echo '
                <td class="text-center">
                    '.numberFormat($riga['qta_rimanente']).'
                </td>';

    // Q.tà da evadere
    echo '
                <td>
                    {[ "type": "number", "name": "qta_da_evadere['.$riga['id'].']", "id": "qta_'.$i.'", "required": 1, "value": "'.$riga['qta_rimanente'].'", "decimals": "qta", "min-value": "0", "extra": "'.(($riga['is_descrizione']) ? 'readonly' : '').' onkeyup=\"ricalcola_subtotale_riga('.$i.');\"" ]}
                </td>';

    echo '
                <td>
                    <big id="subtotale_'.$i.'">'.moneyFormat($riga->totale).'</big><br/>

                    <small style="color:#777;" id="subtotaledettagli_'.$i.'">'.numberFormat($riga->totale_imponibile).' + '.numberFormat($riga->iva).'</small>
                </td>';

    // Seriali
    if (!empty($options['serials'])) {
        echo '
                <td>';

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

        if (empty($riga['abilita_serial']) || empty($serials)) {
            echo '-';
        }

        echo '
                </td>';
    }

    echo '
            </tr>';
}

// Totale
echo '
            <tr>
                <td colspan="'.(!empty($options['serials']) ? 4 : 3).'" class="text-right">
                    <b>'.tr('Totale').':</b>
                </td>
                <td class="text-right" colspan="2">
                    <big id="totale"></big>
                </td>
            </tr>
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

?>

<script type="text/javascript">
    function ricalcola_subtotale_riga(r) {
        var prezzo_unitario = $("#prezzo_unitario_" + r).val();
        var sconto = $("#sconto_unitario_" + r).val();
        var iva = $("#iva_unitaria_" + r).val();

        var qta_max_input = $("#qta_max_" + r);
        var qta_max = qta_max_input.val() ? qta_max_input.val() : 0;

        prezzo_unitario = parseFloat(prezzo_unitario);
        sconto = parseFloat(sconto);
        iva = parseFloat(iva);
        qta_max = parseFloat(qta_max);

        var prezzo_scontato = prezzo_unitario - sconto;

        var qta = $("#qta_" + r).val().toEnglish();

        // Se inserisco una quantità da evadere maggiore di quella rimanente, la imposto al massimo possibile
        if (qta > qta_max) {
            qta = qta_max;

            $('#qta_' + r).val(qta);
        }

        // Se tolgo la spunta della casella dell'evasione devo azzerare i conteggi
        if (isNaN(qta) || !$('#checked_' + r).is(':checked')) {
            qta = 0;
        }

        var serial_select = $("#serial_" + r);
        serial_select.selectClear();
        serial_select.select2("destroy");
        serial_select.data('maximum', qta);
        start_superselect();

        var subtotale = (prezzo_scontato * qta + iva * qta).toLocale();

        $("#subtotale_" + r).html(subtotale + " " + globals.currency);
        $("#subtotaledettagli_" + r).html((prezzo_scontato * qta).toLocale() + " + " + (iva * qta).toLocale());

        ricalcola_totale();
    }

    function ricalcola_totale() {
        var totale = 0.00;
        var totale_qta = 0;

        $('input[id*=qta_]').each(function() {
            var qta = $(this).val().toEnglish();
            var r = $(this).attr("id").replace("qta_", "");

            if (!$("#checked_" + r).is(":checked") || isNaN(qta)) {
                qta = 0;
            }

            var prezzo_unitario = $("#prezzo_unitario_" + r).val();
            var sconto = $("#sconto_unitario_" + r).val();
            var iva = $("#iva_unitaria_" + r).val();

            prezzo_unitario = parseFloat(prezzo_unitario);
            sconto = parseFloat(sconto);
            iva = parseFloat(iva);

            var prezzo_scontato = prezzo_unitario - sconto;

            if(prezzo_scontato) {
                totale += prezzo_scontato * qta + iva * qta;
            }

            totale_qta += qta;
        });

        $('#totale').html((totale.toLocale()) + " " + globals.currency);

        <?php

        if (empty($options['allow-empty'])) {
            echo '
        if (totale_qta > 0)
            $("#submit_btn").show();
        else
            $("#submit_btn").hide();';
        }

        ?>
    }

    ricalcola_totale();
</script>
