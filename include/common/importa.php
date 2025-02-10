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

use Models\Module;
use Modules\Contratti\Stato as StatoContratto;
use Modules\DDT\Stato;
use Modules\Fatture\Stato as StatoFattura;
use Modules\Fatture\Tipo as Tipofattura;
use Modules\Ordini\Stato as StatoOrdine;
use Plugins\ListinoFornitori\DettaglioFornitore;

// Inizializzazione
$documento = $options['documento'];
$documento_finale = $options['documento_finale'];
if (empty($documento)) {
    return;
}

// Informazioni utili
$dir = $documento->direzione;
$original_module = Module::where('name', $documento->module)->first();

$name = !empty($documento_finale) ? $documento_finale->module : $options['module'];
$final_module = Module::where('name', $name)->first();
$id_segment = $_SESSION['module_'.$final_module->id]['id_segment'];

// IVA predefinita
$id_iva = $id_iva ?: setting('Iva predefinita');

$righe_totali = $documento->getRighe();

$id_module_interventi = Module::where('name', 'Interventi')->first()->id;
$id_module_ordini_f = Module::where('name', 'Ordini fornitore')->first()->id;
if ($final_module->id == $id_module_interventi) {
    $righe = $righe_totali->where('is_descrizione', '=', 0)
        ->where('qta_rimanente', '>', 0);
    $righe_evase = $righe_totali->where('is_descrizione', '=', 0)
        ->where('qta_rimanente', '=', 0);
} elseif ($final_module->id == $id_module_ordini_f) {
    $righe = $righe_totali;
    $righe_evase = collect();
} else {
    $righe = $righe_totali->where('qta_rimanente', '>', 0);
    $righe_evase = $righe_totali->where('qta_rimanente', '=', 0);
}

$link = !empty($documento_finale) ? base_path().'/editor.php?id_module='.$final_module->id.'&id_record='.$documento_finale->id : base_path().'/controller.php?id_module='.$final_module->id;

echo '
<form action="'.$link.'" method="post">
    <input type="hidden" name="op" value="'.$options['op'].'">
    <input type="hidden" name="backto" value="record-edit">

    <input type="hidden" name="id_documento" value="'.$documento->id.'">
    <input type="hidden" name="type" value="'.$options['type'].'">
    <input type="hidden" name="class" value="'.$documento::class.'">
    <input type="hidden" name="is_evasione" value="1">';

// Creazione fattura dal documento
if (!empty($options['create_document'])) {
    echo '
    <div class="card card-primary">
        <div class="card-header with-border">
            <h3 class="card-title">'.tr('Nuovo documento').'</h3>
        </div>
        <div class="card-body">

            <div class="row">
                <input type="hidden" name="create_document" value="on" />

                <div class="col-md-6">
                    {[ "type": "date", "label": "'.tr('Data del documento').'", "name": "data", "required": 1, "value": "-now-" ]}
                </div>';

    // Opzioni aggiuntive per le Fatture
    $id_module_fatt_vendita = Module::where('name', 'Fatture di vendita')->first()->id;
    $id_module_fatt_acquisto = Module::where('name', 'Fatture di acquisto')->first()->id;
    $id_module_ddt_vendita = Module::where('name', 'Ddt in uscita')->first()->id;
    $id_module_ddt_acquisto = Module::where('name', 'Ddt in entrata')->first()->id;
    if (in_array($final_module->id, [$id_module_fatt_vendita, $id_module_fatt_acquisto])) {
        $stato_predefinito = StatoFattura::where('name', 'Bozza')->first()->id;
        $fatt_differita_acquisto = Tipofattura::where('name', 'Fattura differita di acquisto')->first()->id;
        $fatt_differita_vendita = Tipofattura::where('name', 'Fattura differita di vendita')->first()->id;

        if (!empty($options['reversed'])) {
            $idtipodocumento = database()->fetchOne('SELECT `co_tipidocumento`.`id` FROM `co_tipidocumento` LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento_lang`.`id_record` = `co_tipidocumento`.`id` AND `co_tipidocumento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `title` = "Nota di credito" AND `dir` = \''.$dir.'\'')['id'];
        } elseif (in_array($original_module->id, [$id_module_ddt_vendita, $id_module_ddt_acquisto])) {
            $idtipodocumento = database()->fetchOne('SELECT `co_tipidocumento`.`id` FROM `co_tipidocumento` LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento_lang`.`id_record` = `co_tipidocumento`.`id` AND `co_tipidocumento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `co_tipidocumento`.`id` = '.($dir == 'uscita' ? $fatt_differita_acquisto : $fatt_differita_vendita).' AND `dir` = \''.$dir.'\'')['id'];
        } else {
            $idtipodocumento = database()->fetchOne('SELECT `co_tipidocumento`.`id` FROM `co_tipidocumento` LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento_lang`.`id_record` = `co_tipidocumento`.`id` AND `co_tipidocumento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `dir` = \''.$dir.'\' AND `predefined` = 1')['id'];
        }

        $id_bozza = StatoFattura::where('name', 'Bozza')->first()->id;
        $id_emessa = StatoFattura::where('name', 'Emessa')->first()->id;
        echo '
            <div class="col-md-6">
                {[ "type": "select", "label": "'.tr('Stato').'", "name": "id_stato", "required": 1, "values": "query=SELECT `co_statidocumento`.`id` as id, `co_statidocumento_lang`.`title` as descrizione FROM `co_statidocumento` LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento`.`id` = `co_statidocumento_lang`.`id_record` AND `co_statidocumento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `co_statidocumento`.`id` IN ('.$id_bozza.', '.$id_emessa.')", "value": "'.$stato_predefinito.'"]}
            </div>

            <div class="col-md-6">
                {[ "type": "select", "label": "'.tr('Tipo documento').'", "name": "idtipodocumento", "required": 1, "values": "query=SELECT `co_tipidocumento`.`id`, CONCAT(`codice_tipo_documento_fe`, \' - \', `title`) AS descrizione FROM `co_tipidocumento` LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento`.`id` = `co_tipidocumento_lang`.`id_record` AND `co_tipidocumento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `enabled` = 1 AND `dir` = '.prepare($dir).' ORDER BY `codice_tipo_documento_fe`", "value": "'.$idtipodocumento.'" ]}
            </div>
            
            <div class="col-md-6">
                {[ "type": "select", "label": "'.tr('Ritenuta previdenziale').'", "name": "id_ritenuta_contributi", "value": "$id_ritenuta_contributi$", "values": "query=SELECT * FROM co_ritenuta_contributi" ]}
            </div>';
    }

    // Opzioni aggiuntive per gli Interventi
    elseif ($final_module->getTranslation('title') == 'Attività') {
        echo '
            <div class="col-md-6">
                {[ "type": "select", "label": "'.tr('Stato').'", "name": "id_stato_intervento", "required": 1, "values": "query=SELECT `in_statiintervento`.`id`, `in_statiintervento_lang`.`title` as `descrizione`, `colore` AS _bgcolor_ FROM `in_statiintervento` LEFT JOIN `in_statiintervento_lang` ON (`in_statiintervento`.`id` = `in_statiintervento_lang`.`id_record` AND `in_statiintervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `deleted_at` IS NULL AND `is_completato` = 0 ORDER BY `title`" ]}
            </div>

            <div class="col-md-6">
                {[ "type": "select", "label": "'.tr('Tipo').'", "name": "id_tipo_intervento", "required": 1, "ajax-source": "tipiintervento" ]}
            </div>';
    }

    // Opzioni aggiuntive per i Contratti
    elseif ($final_module->getTranslation('title') == 'Contratti') {
        $stato_predefinito = StatoContratto::where('name', 'Bozza')->first()->id;

        echo '
            <div class="col-md-6">
                {[ "type": "select", "label": "'.tr('Stato').'", "name": "id_stato", "required": 1, "values": "query=SELECT `co_staticontratti`.`id`, `co_staticontratti_lang`.`title` AS descrizione FROM `co_staticontratti` LEFT JOIN `co_staticontratti_lang` ON (`co_staticontratti`.`id` = `co_staticontratti_lang`.`id_record` AND `co_staticontratti_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')", "value": "'.$stato_predefinito.'" ]}
            </div>';
    }

    // Opzioni aggiuntive per i DDT
    elseif (in_array($final_module->getTranslation('title'), ['Ddt in uscita', 'Ddt in entrata'])) {
        $stato_predefinito = Stato::where('name', 'Bozza')->first()->id;

        echo '
            <div class="col-md-6">
                {[ "type": "select", "label": "'.tr('Stato').'", "name": "id_stato", "required": 1, "values": "query=SELECT `dt_statiddt`.*, `dt_statiddt_lang`.`title` AS descrizione FROM `dt_statiddt` LEFT JOIN `dt_statiddt_lang` ON (`dt_statiddt`.`id` = `dt_statiddt_lang`.`id_record` AND `dt_statiddt_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')", "value": "'.$stato_predefinito.'" ]}
            </div>

            <div class="col-md-6">
                {[ "type": "select", "label": "'.tr('Causale trasporto').'", "name": "id_causale_trasporto", "required": 1, "ajax-source": "causali", "icon-after": "add|'.Module::where('name', 'Causali')->first()->id.'", "help": "'.tr('Definisce la causale del trasporto').'" ]}
            </div>';
    }

    // Opzioni aggiuntive per gli Ordini
    elseif (in_array($final_module->getTranslation('title'), ['Ordini cliente', 'Ordini fornitore'])) {
        $stato_predefinito = StatoOrdine::where('name', 'Bozza')->first()->id;

        echo '
            <div class="col-md-6">
                {[ "type": "select", "label": "'.tr('Stato').'", "name": "id_stato", "required": 1, "values": "query=SELECT * ,`or_statiordine`.`id`, `or_statiordine_lang`.`title` AS descrizione FROM `or_statiordine` LEFT JOIN `or_statiordine_lang` ON (`or_statiordine`.`id` = `or_statiordine_lang`.`id_record` AND `or_statiordine_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `title` IN(\'Bozza\', \'Accettato\', \'In attesa di conferma\', \'Annullato\')", "value": "'.$stato_predefinito.'" ]}
            </div>';
    }

    // Selezione fornitore per Ordine fornitore
    if ($options['op'] == 'add_ordine_cliente' || $options['op'] == 'add_intervento' || $options['op'] == 'add_ordine_fornitore') {
        $tipo_anagrafica = $options['op'] == 'add_intervento' ? tr('Cliente') : tr('Fornitore');
        $ajax = $options['op'] == 'add_intervento' ? 'clienti' : 'fornitori';

        echo '
            <div class="col-md-6">
                {[ "type": "select", "label": "'.$tipo_anagrafica.'", "name": "idanagrafica", "required": 1, "ajax-source": "'.$ajax.'", "icon-after": "add|'.Module::where('name', 'Anagrafiche')->first()->id.'|tipoanagrafica='.$tipo_anagrafica.'" ]}
            </div>';
    }

    echo '
                <div class="col-md-6">
                    {[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "ajax-source": "segmenti", "select-options": '.json_encode(['id_module' => $final_module->id, 'is_sezionale' => 1]).', "value": "'.$database->selectOne('co_tipidocumento', 'id_segment', ['id' => $idtipodocumento])['id_segment'].'" ]}
                </div>
            </div>
        </div>
    </div>';
}

// Conto, rivalsa INPS, ritenuta d'acconto e ritenuta previdenziale
if (in_array($final_module->getTranslation('title'), ['Fatture di vendita', 'Fatture di acquisto']) && !in_array($original_module->getTranslation('title'), ['Fatture di vendita', 'Fatture di acquisto'])) {
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
    <div class="card card-primary">
        <div class="card-header with-border">
            <h3 class="card-title">'.tr('Opzioni generali delle righe').'</h3>
        </div>
        <div class="card-body">';

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
            </div>';

    $block_input = false;
    if ($original_module->name == 'Interventi') {
        $block_input = true;

        $rs = $dbo->fetchOne('SELECT
                `in_interventi`.`id`,
                CONCAT(\'Attività numero \', `in_interventi`.`codice`, \' del \', DATE_FORMAT(IFNULL((SELECT MIN(`orario_inizio`) FROM `in_interventi_tecnici` WHERE `in_interventi_tecnici`.`idintervento`=`in_interventi`.`id`), `in_interventi`.`data_richiesta`), \'%d/%m/%Y\'), " [", `in_statiintervento_lang`.`title` , "]") AS descrizione,
                CONCAT(\'Attività numero \', `in_interventi`.`codice`, \' del \', DATE_FORMAT(IFNULL((SELECT MIN(`orario_inizio`) FROM `in_interventi_tecnici` WHERE `in_interventi_tecnici`.`idintervento`=`in_interventi`.`id`), `in_interventi`.`data_richiesta`), \'%d/%m/%Y\')) AS info,
                CONCAT(\'\n\', `in_interventi`.`descrizione`) AS descrizione_intervento,
                IF(`idclientefinale`='.prepare($idanagrafica).', \'Interventi conto terzi\', \'Interventi diretti\') AS `optgroup`
            FROM
                `in_interventi` 
                INNER JOIN `in_statiintervento` ON `in_interventi`.`idstatointervento`=`in_statiintervento`.`id`
                LEFT JOIN `in_statiintervento_lang` ON (`in_statiintervento`.`id` = `in_statiintervento_lang`.`id_record` AND `in_statiintervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
            WHERE
                `in_interventi`.`id` = '.prepare($documento->id));

        $descrizione_intervento = str_replace("'", ' ', strip_tags((string) $original_module->replacePlaceholders($documento->id, setting('Descrizione personalizzata in fatturazione')))) ?: $rs['info'];

        // Intervento
        echo '
            <div class="row">
                <div class="col-md-6">
                    {[ "type": "textarea", "label": "'.tr('Descrizione').'", "name": "descrizione_intervento", "required": "1", "value": "'.$descrizione_intervento.'" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "checkbox", "label": "'.tr('Copia descrizione').'", "name": "copia_descrizione", "placeholder": "'.tr('Copia anche la descrizione dell\'intervento').'." ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "checkbox", "label": "'.tr('Importa sessioni').'", "name": "importa_sessioni", "value": "1", "disabled": "'.($block_input ? '1' : '0').'" ]}
                </div>
            </div>';
    }
    echo '
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
    <div class="card card-primary">
        <div class="card-header with-border">
            <h3 class="card-title">'.tr('Righe da importare').'</h3>
        </div>

        <table class="card-body table table-striped table-hover table-sm">
            <thead>
                <tr>
                    <th width="2%"><input id="import_all" type="checkbox" class="'.($block_input ? 'disabled' : '').'" checked/></th>
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
    if ($final_module->getTranslation('title') == 'Ordini fornitore') {
        $qta_rimanente = $riga['qta'];
    } else {
        $qta_rimanente = $riga['qta_rimanente'];
    }

    $attr = 'checked="checked"';
    if ($original_module->getTranslation('title') == 'Preventivi') {
        if (empty($riga['confermato']) && $riga['is_descrizione'] == 0) {
            $attr = '';
        }
    }

    // Descrizione
    echo '
                <tr data-local_id="'.$i.'">
                    <td style="vertical-align:middle">
                        <input class="check '.($block_input ? 'disabled' : '').'" type="checkbox" '.$attr.' id="checked_'.$i.'" name="evadere['.$riga['id'].']" value="on" onclick="ricalcolaTotaleRiga('.$i.');" />
                    </td>
                    <td style="vertical-align:middle">
                        <span class="hidden" id="id_articolo_'.$i.'">'.$riga['idarticolo'].'</span>

                        <input type="hidden" class="righe" name="righe" value="'.$i.'"/>
                        <input type="hidden" id="prezzo_unitario_'.$i.'" name="subtot['.$riga['id'].']" value="'.$riga['prezzo_unitario'].'" />
                        <input type="hidden" id="sconto_unitario_'.$i.'" name="sconto['.$riga['id'].']" value="'.$riga['sconto_unitario'].'" />
                        <input type="hidden" id="max_qta_'.$i.'" value="'.($options['superamento_soglia_qta'] ? '' : $riga['qta_rimanente']).'" />';

    $descrizione = ($riga->isArticolo() ? $riga->articolo->codice.' - ' : '').$riga['descrizione'];

    echo '&nbsp;'.nl2br($descrizione);

    if ($riga->isArticolo()) {
        $dettaglio_fornitore = DettaglioFornitore::where('id_articolo', $riga->idarticolo)
            ->where('id_fornitore', $documento->idanagrafica)
            ->first();

        if (!empty($dettaglio_fornitore->codice_fornitore)) {
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
                        {[ "type": "number", "name": "qta_da_evadere['.$riga['id'].']", "id": "qta_'.$i.'", "required": 1, "value": "'.$qta_rimanente.'", "decimals": "qta", "min-value": "0", "extra": "'.(($riga['is_descrizione']) ? 'readonly' : '').' onkeyup=\"ricalcolaTotaleRiga('.$i.');\"", "disabled": "'.($block_input ? '1' : '0').'" ]}
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
                <td colspan="4" class="text-right">
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
    <div class="card card-primary collapsable collapsed-card">
        <div class="card-header with-border">
            <h3 class="card-title">'.tr('Righe evase completamente').'</h3>
            <div class="card-tools pull-right">
                <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fa fa-plus"></i></button>
            </div>
        </div>

        <table class="card-body table table-striped table-hover table-sm">
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
    <div class="card alert-warning hidden" id="articoli_sottoscorta">
        <table class="table table-sm">
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

    $descrizione_riga = $articolo->codice.' - '.$articolo->getTranslation('title');
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
