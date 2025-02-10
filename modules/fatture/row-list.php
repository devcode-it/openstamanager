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

use Modules\Interventi\Intervento;

include_once __DIR__.'/init.php';

use Models\Plugin;

$block_edit = !empty($note_accredito) || in_array($record['stato'], ['Emessa', 'Pagato', 'Parzialmente pagato']) || !$abilita_genera;
$order_row_desc = $_SESSION['module_'.$id_module]['order_row_desc'];
$righe = $order_row_desc ? $fattura->getRighe()->sortByDesc('created_at') : $fattura->getRighe();
$colspan = $dir == 'entrata' ? '8' : '7';

echo '
<div class="table-responsive row-list">
    <table class="table table-striped table-hover table-sm table-bordered">
        <thead>
            <tr>
                <th width="5" class="text-center">';
if (!$block_edit && sizeof($righe) > 0) {
    echo '
                    <input id="check_all" type="checkbox"/>';
}
echo '
                </th>
                <th width="35" class="text-center" >'.tr('#').'</th>
                <th class="text-left" style="width:30%;">'.tr('Descrizione').'</th>
                <th class="text-center" width="120">'.tr('Q.tà').'</th>';
if ($dir == 'entrata') {
    echo '<th class="text-center" width="150">'.tr('Costo unitario').'</th>';
}
echo '
                <th class="text-center" width="180">'.tr('Prezzo unitario').'</th>
                <th class="text-center" width="140">'.tr('Sconto unitario').'</th>
                <th class="text-center" width="120">'.tr('Iva unitaria').'</th>
                <th class="text-center" width="120">'.tr('Importo').'</th>
                <th width="120"></th>
            </tr>
        </thead>
        <tbody class="sortable" id="righe">';

// Righe documento
$num = 0;
foreach ($righe as $riga) {
    $show_notifica = [];
    ++$num;
    $extra = '';
    $mancanti = 0;
    $delete = 'delete_riga';

    $row_disable = in_array($riga->id, [$fattura->rigaBollo->id, $fattura->id_riga_spese_incasso]);

    // Individuazione dei seriali
    if ($riga->isArticolo() && !empty($riga->abilita_serial)) {
        $serials = $riga->serials;
        $mancanti = abs($riga->qta) - count($serials);

        if ($mancanti > 0) {
            $extra = 'class="warning"';
        } else {
            $mancanti = 0;
        }
    }

    // Imposto sfondo rosso alle righe con quantità a 0
    if ($riga->qta == 0) {
        $extra = 'class="danger"';
    }

    $extra_riga = '';
    if (!$riga->isDescrizione()) {
        // Informazioni su CIG, CUP, ...
        if ($riga->hasOriginalComponent()) {
            $documento_originale = $riga->getOriginalComponent()->getDocument();

            $num_item = $documento_originale['num_item'];
            $codice_cig = $documento_originale['codice_cig'];
            $codice_commessa = $documento_originale['codice_commessa'];
            $codice_cup = $documento_originale['codice_cup'];
            $id_documento_fe = $documento_originale['id_documento_fe'];
        }

        $descrizione_conto = $dbo->fetchOne('SELECT descrizione FROM co_pianodeiconti3 WHERE id = '.prepare($riga->id_conto))['descrizione'];

        $extra_riga = replace('_DESCRIZIONE_CONTO__ID_DOCUMENTO__NUMERO_RIGA__CODICE_COMMESSA__CODICE_CIG__CODICE_CUP__RITENUTA_ACCONTO__RITENUTA_CONTRIBUTI__RIVALSA_', [
            '_RIVALSA_' => $riga->rivalsa_inps ? '<br>'.tr('Cassa previdenziale').': '.moneyFormat(abs($riga->rivalsa_inps)) : null,
            '_RITENUTA_ACCONTO_' => $riga->ritenuta_acconto ? '<br>Ritenuta acconto: '.moneyFormat(abs($riga->ritenuta_acconto)) : null,
            '_RITENUTA_CONTRIBUTI_' => $riga->ritenuta_contributi ? '<br>Ritenuta previdenziale: '.moneyFormat(abs($riga->ritenuta_contributi)) : null,
            '_DESCRIZIONE_CONTO_' => $descrizione_conto ?: '<span class="badge badge-danger" ><i class="fa fa-exclamation-triangle"></i>
            '.tr('Conto mancante').'</span>',
            '_ID_DOCUMENTO_' => $id_documento_fe ? ' - DOC: '.$id_documento_fe : null,
            '_NUMERO_RIGA_' => $num_item ? ', NRI: '.$num_item : null,
            '_CODICE_COMMESSA_' => $codice_commessa ? ', COM: '.$codice_commessa : null,
            '_CODICE_CIG_' => $codice_cig ? ', CIG: '.$codice_cig : null,
            '_CODICE_CUP_' => $codice_cup ? ', CUP: '.$codice_cup : null,
        ]);
    }

    echo '
        <tr data-id="'.$riga->id.'" data-type="'.$riga::class.'" '.$extra.'>
            <td class="text-center">';
    if (!$block_edit && !$row_disable) {
        echo '
                <input class="check" type="checkbox"/>';
    }
    echo '
            </td>
            
            <td class="text-center">
                '.$num.'
            </td>

            <td>';

    // Informazioni aggiuntive sulla destra
    echo '
                <small class="pull-right text-right text-muted">
                    '.$extra_riga;

    // Aggiunta dei riferimenti ai documenti
    if ($riga->hasOriginalComponent()) {
        echo '
                    <br>'.reference($riga->getOriginalComponent()->getDocument(), tr('Origine'));
    }
    // Fix per righe da altre componenti degli Interventi
    elseif (!empty($riga->idintervento)) {
        echo '
                    <br>'.reference(Intervento::find($riga->idintervento), tr('Origine'));
    }

    echo '
                </small>';

    if ($riga->isArticolo()) {
        echo Modules::link('Articoli', $riga->idarticolo, $riga->codice.' - '.$riga->descrizione);
    } else {
        echo nl2br($riga->descrizione);
    }

    if ($riga->isArticolo() && !empty($riga->articolo->deleted_at)) {
        echo '
        <br><b><small class="text-danger">'.tr('Articolo eliminato', []).'</small></b>';
    }

    if ($riga->isArticolo() && empty($riga->articolo->codice)) {
        echo '
        <br><b><small class="text-danger">'.tr('_DATO_ articolo mancante', [
            '_DATO_' => 'Codice',
        ]).'</small></b>';
    }

    if ($riga->isArticolo() && !empty($riga->abilita_serial)) {
        if (!empty($mancanti)) {
            echo '
                <br><b><small class="text-danger">'.tr('_NUM_ serial mancanti', [
                '_NUM_' => $mancanti,
            ]).'</small></b>';
        }
        if (!empty($serials)) {
            echo '
                <br>'.tr('SN').': '.implode(', ', $serials);
        }
    }

    if ($riga->isArticolo() && !empty($riga->articolo->barcode)) {
        echo '
        <br><small><i class="fa fa-barcode"></i> '.$riga->articolo->barcode.'</small>';
    }

    if (!empty($riga->note)) {
        if (strlen($riga->note) > 50) {
            $prima_parte = substr($riga->note, 0, (strpos($riga->note, ' ', 50) < 60) && (!str_starts_with($riga->note, ' ')) ? strpos($riga->note, ' ', 50) : 50);
            $seconda_parte = substr($riga->note, (strpos($riga->note, ' ', 50) < 60) && (!str_starts_with($riga->note, ' ')) ? strpos($riga->note, ' ', 50) : 50);
            $stringa_modificata = '<span class="right badge badge-default">'.$prima_parte.'</small>
                <span id="read-more-target-'.$riga->id.'" class="read-more-target"><span class="right badge badge-default">'.$seconda_parte.'</small></span><a href="#read-more-target-'.$riga->id.'" class="read-more-trigger">...</a>';
        } else {
            $stringa_modificata = '<span class="right badge badge-default">'.$riga->note.'</small>';
        }

        echo '
        <div class="block-item-text">
            <input type="checkbox" hidden class="read-more-state" id="read-more">
                <div class="read-more-wrap">
                    '.nl2br($stringa_modificata).'
                </div>
            </div>
        ';
    }
    echo '
            </td>';

    if ($riga->isDescrizione()) {
        echo '
            <td></td>';
        if ($dir == 'entrata') {
            echo '<td></td>';
        }
        echo '
            <td></td>
            <td></td>
            <td></td>
            <td></td>';
    } else {
        // Quantità e unità di misura
        echo '
            <td>
                {[ "type": "number", "name": "qta_'.$riga->id.'", "value": "'.$riga->qta.'", "min-value": "0", "onchange": "aggiornaInline($(this).closest(\'tr\').data(\'id\'))", "disabled": "'.($riga->isSconto() ? 1 : 0).'", "disabled": "'.($block_edit || $riga->isSconto() || $row_disable).'", "decimals": "qta" ]}
            </td>';

        if ($riga->isArticolo()) {
            $id_anagrafica = $fattura->idanagrafica;
            $show_notifica = getPrezzoConsigliato($id_anagrafica, $dir, $riga->idarticolo, $riga);
        }

        // Costi unitari
        if ($dir == 'entrata') {
            if ($riga->isSconto()) {
                echo '
            <td></td>';
            } else {
                echo '
            <td>
                {[ "type": "number", "name": "costo_'.$riga->id.'", "value": "'.$riga->costo_unitario.'", "onchange": "aggiornaInline($(this).closest(\'tr\').data(\'id\'))", "icon-after": "'.currency().'", "disabled": "'.($block_edit || $row_disable).'" ]}
            </td>';
            }
        }

        // Prezzi unitari
        if ($riga->isSconto()) {
            echo '
            <td></td>';
        } else {
            echo '
            <td class="text-center">
                '.($show_notifica['show_notifica_prezzo'] ? '<i class="fa fa-info-circle notifica-prezzi"></i>' : '').'
                {[ "type": "number", "name": "prezzo_'.$riga->id.'", "value": "'.$riga->prezzo_unitario_corrente.'", "onchange": "aggiornaInline($(this).closest(\'tr\').data(\'id\'))", "icon-before": "'.(abs($riga->provvigione_unitaria) > 0 ? '<span class=\'tip text-info\' title=\''.provvigioneInfo($riga).'\'><small><i class=\'fa fa-handshake-o\'></i></small></span>' : '').'", "icon-after": "'.currency().'", "disabled": "'.($block_edit || $row_disable).'" ]}';

            // Prezzo inferiore al minimo consigliato
            if ($riga->isArticolo()) {
                echo $riga->articolo->minimo_vendita > $riga->prezzo_unitario_corrente ? '<small><i class="fa fa-info-circle text-danger"></i> '.tr('Consigliato: ').numberFormat($riga->articolo->minimo_vendita, 2).'</small>' : '';
            }
            echo '</td>';
        }

        // Sconto unitario
        $tipo_sconto = '';
        if ($riga['sconto'] == 0) {
            $tipo_sconto = (setting('Tipo di sconto predefinito') == '%' ? 'PRC' : 'UNT');
        }
        echo '
            <td>
                '.($show_notifica['show_notifica_sconto'] ? '<i class="fa fa-info-circle notifica-prezzi"></i>' : '').'
                {[ "type": "number", "name": "sconto_'.$riga->id.'", "value": "'.($riga->sconto_percentuale ?: $riga->sconto_unitario_corrente).'", "onchange": "aggiornaInline($(this).closest(\'tr\').data(\'id\'))", "icon-after": "'.($riga->isSconto() ? currency() : 'choice|untprc|'.($tipo_sconto ?: $riga->tipo_sconto)).'", "disabled": "'.$block_edit.'" ]}
            </td>';

        // Iva
        echo '
            <td class="text-right">
                '.moneyFormat($riga->iva_unitaria_scontata).'
                <br><small class="'.(($riga->aliquota->deleted_at) ? 'text-red' : '').' text-muted">'.($riga->aliquota ? $riga->aliquota->getTranslation('title') : '').' ('.$riga->aliquota->esigibilita.') '.(($riga->aliquota->esente) ? ' ('.$riga->aliquota->codice_natura_fe.')' : null).'</small>
            </td>';

        // Importo
        echo '
            <td class="text-right">
                '.moneyFormat($riga->importo).'
            </td>';
    }

    // Possibilità di rimuovere una riga solo se la fattura non è pagata
    echo '
            <td class="text-center">

                <div class="input-group-btn">';
    if (hasArticoliFiglio($riga->idarticolo)) {
        echo '
                    <a class="btn btn-xs btn-info" title="'.tr('Distinta base').'" onclick="viewDistinta('.$riga->idarticolo.')">
                        <i class="fa fa-eye"></i>
                    </a>';
    }

    if ($riga->isArticolo() && !empty($riga->abilita_serial)) {
        echo '
                    <a class="btn btn-primary btn-xs" title="'.tr('Modifica seriali della riga').'" onclick="modificaSeriali(this)">
                        <i class="fa fa-barcode"></i>
                    </a>';
    }

    if ($record['stato'] != 'Pagato' && $record['stato'] != 'Emessa') {
        if (!$row_disable) {
            echo '
                    <a class="btn btn-xs btn-info" title="'.tr('Aggiungi informazioni FE per questa riga').'" onclick="apriInformazioniFE(this)">
                        <i class="fa fa-file-code-o"></i>
                    </a>

                    <a class="btn btn-xs btn-warning" title="'.tr('Modifica riga').'" onclick="modificaRiga(this)">
                        <i class="fa fa-edit"></i>
                    </a>

                    <a class="btn btn-xs btn-danger" title="'.tr('Rimuovi riga').'" onclick="rimuoviRiga([$(this).closest(\'tr\').data(\'id\')])">
                        <i class="fa fa-trash"></i>
                    </a>';
        }

        echo '
                    <a class="btn btn-xs btn-default handle '.($order_row_desc ? 'disabled' : '').'" title="'.tr('Modifica ordine delle righe').'">
                        <i class="fa fa-sort"></i>
                    </a>';
    }
    echo '
                </div>
            </td>
        </tr>';
}

echo '
        </tbody>';

// Individuazione dei totali
$imponibile = $fattura->imponibile;
$sconto = -$fattura->sconto;
$totale_imponibile = $fattura->totale_imponibile;
$iva = $fattura->iva;
$totale = $fattura->totale;
$sconto_finale = $fattura->getScontoFinale();
$netto_a_pagare = $fattura->netto;
$rivalsa_inps = $fattura->rivalsa_inps;
$ritenuta_acconto = $fattura->ritenuta_acconto;
$ritenuta_contributi = $fattura->totale_ritenuta_contributi;

// IMPONIBILE
echo '
        <tr>
            <td colspan="'.$colspan.'" class="text-right">
                <b>'.tr('Imponibile', [], ['upper' => true]).':</b>
            </td>
            <td class="text-right">
                '.moneyFormat($imponibile, 2).'
            </td>
            <td></td>
        </tr>';

// SCONTO
if (!empty($sconto)) {
    echo '
        <tr>
            <td colspan="'.$colspan.'" class="text-right">
                <b><span class="tip" title="'.tr('Un importo negativo indica uno sconto, mentre uno positivo indica una maggiorazione').'"><i class="fa fa-question-circle-o"></i> '.tr('Sconto/maggiorazione', [], ['upper' => true]).':</span></b>
            </td>
            <td class="text-right">
                '.moneyFormat($sconto, 2).'
            </td>
            <td></td>
        </tr>';

    // TOTALE IMPONIBILE
    echo '
        <tr>
            <td colspan="'.$colspan.'" class="text-right">
                <b>'.tr('Totale imponibile', [], ['upper' => true]).':</b>
            </td>
            <td class="text-right">
                '.moneyFormat($totale_imponibile, 2).'
            </td>
            <td></td>
        </tr>';
}

// RIVALSA INPS
if (!empty($rivalsa_inps)) {
    echo '
        <tr>
            <td colspan="'.$colspan.'" class="text-right">';

    if ($dir == 'entrata') {
        $descrizione_rivalsa = $database->fetchOne('SELECT CONCAT_WS(\' - \', codice, descrizione) AS descrizione FROM fe_tipo_cassa WHERE codice = '.prepare(setting('Tipo Cassa Previdenziale')));
        echo '
				<span class="tip" title="'.$descrizione_rivalsa['descrizione'].'">
				    <i class="fa fa-question-circle-o"></i>
                </span> ';
    }

    echo '
                <b>'.tr('Cassa previdenziale', [], ['upper' => true]).' :</b>
            </td>
            <td class="text-right">
                '.moneyFormat($rivalsa_inps, 2).'
            </td>
            <td></td>
        </tr>
        <tr>
            <td colspan="'.$colspan.'" class="text-right">
                <b>'.tr('Totale imponibile', [], ['upper' => true]).' :</b>
            </td>
            <td class="text-right">
                '.moneyFormat($totale_imponibile + $rivalsa_inps, 2).'
            </td>
            <td></td>
        </tr>';
}

// IVA
if (!empty($iva)) {
    echo '
        <tr>
            <td colspan="'.$colspan.'" class="text-right">';

    if ($records[0]['split_payment']) {
        echo '<b>'.tr('Iva a carico del destinatario', [], ['upper' => true]).':</b>';
    } else {
        echo '<b>'.tr('Iva', [], ['upper' => true]).':</b>';
    }
    echo '
            </td>
            <td class="text-right">
                '.moneyFormat($iva, 2).'
            </td>
            <td></td>
        </tr>';
}

// TOTALE
echo '
        <tr>
            <td colspan="'.$colspan.'" class="text-right">
                <b>'.tr('Totale documento', [], ['upper' => true]).':</b>
            </td>
            <td class="text-right">
                '.moneyFormat($totale, 2).'
            </td>
            <td></td>
        </tr>';

// RITENUTA D'ACCONTO
if (!empty($ritenuta_acconto)) {
    echo '
        <tr>
            <td colspan="'.$colspan.'" class="text-right">
                <b>'.tr("Ritenuta d'acconto", [], ['upper' => true]).':</b>
            </td>
            <td class="text-right">
                '.moneyFormat($ritenuta_acconto, 2).'
            </td>
            <td></td>
        </tr>';
}

// RITENUTA PREVIDENZIALE
if (!empty($ritenuta_contributi)) {
    echo '
        <tr>
            <td colspan="'.$colspan.'" class="text-right">
                <b>'.tr('Ritenuta previdenziale', [], ['upper' => true]).':</b>
            </td>
            <td class="text-right">
                '.moneyFormat($ritenuta_contributi, 2).'
            </td>
            <td></td>
        </tr>';
}

// SCONTO IN FATTURA
if (!empty($sconto_finale)) {
    echo '
        <tr>
            <td colspan="'.$colspan.'" class="text-right">
                <b>'.tr('Sconto in fattura', [], ['upper' => true]).':</b>
            </td>
            <td class="text-right">
                '.moneyFormat($sconto_finale, 2).'
            </td>
            <td></td>
        </tr>';
}

// NETTO A PAGARE
if ($totale != $netto_a_pagare) {
    echo '
        <tr>
            <td colspan="'.$colspan.'" class="text-right">
                <b>'.tr('Netto a pagare', [], ['upper' => true]).':</b>
            </td>
            <td class="text-right">
                '.moneyFormat($netto_a_pagare, 2).'
            </td>
            <td></td>
        </tr>';
}

// Provvigione
if (!empty($fattura->provvigione)) {
    echo '
        <tr>
            <td colspan="'.$colspan.'" class="text-right">
                '.tr('Provvigioni').':
            </td>
            <td class="text-right">
                '.moneyFormat($fattura->provvigione).'
            </td>
            <td></td>
        </tr>';

    echo '
        <tr>
            <td colspan="'.$colspan.'" class="text-right">
                '.tr('Netto da provvigioni').':
            </td>
            <td class="text-right">
                '.moneyFormat($fattura->totale_imponibile - $fattura->provvigione).'
            </td>
            <td></td>
        </tr>';
}

echo '
    </table>';
if (!$block_edit && sizeof($righe) > 0) {
    echo '
    <div class="btn-group">
        <button type="button" class="btn btn-xs btn-default disabled" id="duplica_righe" onclick="duplicaRiga(getSelectData());">
            <i class="fa fa-copy"></i>
        </button>

        <button type="button" class="btn btn-xs btn-default disabled" id="elimina_righe" onclick="rimuoviRiga(getSelectData());">
            <i class="fa fa-trash"></i>
        </button>';
    if ($dir == 'entrata') {
        echo '
        <button type="button" class="btn btn-xs btn-default disabled" id="confronta_righe" onclick="confrontaRighe(getSelectData());">
            '.tr('Confronta prezzi').'
        </button>';
    }
    echo '
        <button type="button" class="btn btn-xs btn-default disabled" id="aggiorna_righe" onclick="aggiornaRighe(getSelectData());">
            '.tr('Aggiorna prezzi').'
        </button>
    </div>';
}
echo '
</div>

<script>
async function modificaRiga(button) {
    let riga = $(button).closest("tr");
    let id = riga.data("id");
    let type = riga.data("type");

    // Salvataggio via AJAX
    await salvaForm("#edit-form", {}, button);

    // Chiusura tooltip
    if ($(button).hasClass("tooltipstered"))
        $(button).tooltipster("close");

    // Apertura modal
    content_was_modified = false;
    openModal("'.tr('Modifica riga').'", "'.$module->fileurl('row-edit.php').'?id_module=" + globals.id_module + "&id_record=" + globals.id_record + "&riga_id=" + id + "&riga_type=" + type);
}

// Estraggo le righe spuntate
function getSelectData() {
    let data=new Array();
    $(\'#righe\').find(\'.check:checked\').each(function (){ 
        data.push($(this).closest(\'tr\').data(\'id\'));
    });

    return data;
}

function confrontaRighe(id) {
    openModal("'.tr('Confronta prezzi').'", "'.$module->fileurl('modals/confronta_righe.php').'?id_module=" + globals.id_module + "&id_record=" + globals.id_record + "&righe=" + id);
}

function aggiornaRighe(id) {
    swal({
        title: "'.tr('Aggiornare prezzi di queste righe?').'",
        html: "'.tr('Confermando verranno aggiornati i prezzi delle righe secondo i listini ed i prezzi predefiniti collegati all\'articolo e ai piani sconto collegati all\'anagrafica.').'",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "'.tr('Sì').'"
    }).then(function () {
        $.ajax({
            url: globals.rootdir + "/actions.php",
            type: "POST",
            data: {
                id_module: globals.id_module,
                id_record: globals.id_record,
                op: "update-price",
                righe: id,
            },
            success: function (response) {
                renderMessages();
                caricaRighe(null);
            },
            error: function() {
                renderMessages();
                caricaRighe(null);
            }
        });
    }).catch(swal.noop);
}

function rimuoviRiga(id) {
    swal({
        title: "'.tr('Rimuovere queste righe?').'",
        html: "'.tr('Sei sicuro di volere rimuovere queste righe dal documento?').' '.tr("L'operazione è irreversibile").'.",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "'.tr('Sì').'"
    }).then(function () {
        $.ajax({
            url: globals.rootdir + "/actions.php",
            type: "POST",
            dataType: "json",
            data: {
                id_module: globals.id_module,
                id_record: globals.id_record,
                op: "delete_riga",
                righe: id,
            },
            success: function (response) {
                renderMessages();
                caricaRighe(null);';
if (!in_array($fattura->codice_stato_fe, ['RC', 'MC', 'EC01', 'WAIT'])) {
    echo '
                $("#elimina").removeClass("disabled");';
}
echo '
            },
            error: function() {
                renderMessages();
                caricaRighe(null);';
if (!in_array($fattura->codice_stato_fe, ['RC', 'MC', 'EC01', 'WAIT'])) {
    echo '
                $("#elimina").removeClass("disabled");';
}
echo '
            }
        });
    }).catch(swal.noop);
}

function duplicaRiga(id) {
    swal({
        title: "'.tr('Duplicare queste righe?').'",
        html: "'.tr('Sei sicuro di volere queste righe del documento?').'",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "'.tr('Sì').'"
    }).then(function () {
        $.ajax({
            url: globals.rootdir + "/actions.php",
            type: "POST",
            dataType: "json",
            data: {
                id_module: globals.id_module,
                id_record: globals.id_record,
                op: "copy_riga",
                righe: id,
            },
            success: function (response) {
                renderMessages();
                caricaRighe(null);
            },
            error: function() {
                renderMessages();
                caricaRighe(null);
            }
        });
    }).catch(swal.noop);
}

function modificaSeriali(button) {
    let riga = $(button).closest("tr");
    let id = riga.data("id");
    let type = riga.data("type");

    openModal("'.tr('Aggiorna SN').'", globals.rootdir + "/modules/fatture/add_serial.php?id_module=" + globals.id_module + "&id_record=" + globals.id_record + "&riga_id=" + id + "&riga_type=" + type);
}

function apriInformazioniFE(button) {
    let riga = $(button).closest("tr");
    let id = riga.data("id");
    let type = riga.data("type");

    openModal("'.tr('Dati Fattura Elettronica').'", "'.$module->fileurl('fe/row-fe.php').'?id_module=" + globals.id_module + "&id_record=" + globals.id_record + "&riga_id=" + id + "&riga_type=" + type)
}

$(document).ready(function() {
	sortable(".sortable", {
        axis: "y",
        handle: ".handle",
        cursor: "move",
        dropOnEmpty: true,
        scroll: true,
    })[0].addEventListener("sortupdate", function(e) {
        let order = $(".table tr[data-id]").toArray().map(a => $(a).data("id"))

        $.post(globals.rootdir + "/actions.php", {
            id_module: globals.id_module,
            id_record: globals.id_record,
            op: "update_position",
            order: order.join(","),
        });
    });
});

$(".check").on("change", function() {
    let checked = 0;
    $(".check").each(function() {
        if ($(this).is(":checked")) {
            checked = 1;
        }
    });

    if (checked) {
        $("#elimina_righe").removeClass("disabled");
        $("#duplica_righe").removeClass("disabled");
        $("#confronta_righe").removeClass("disabled");
        $("#aggiorna_righe").removeClass("disabled");
        $("#elimina").addClass("disabled");
    } else {
        $("#elimina_righe").addClass("disabled");
        $("#duplica_righe").addClass("disabled");
        $("#confronta_righe").addClass("disabled");
        $("#aggiorna_righe").addClass("disabled");
        $("#elimina").removeClass("disabled");
    }
});

$("#check_all").click(function(){    
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

$(".tipo_icon_after").on("change", function() {
    aggiornaInline($(this).closest("tr").data("id"));
});

function aggiornaInline(id) {
    content_was_modified = false;
    var qta = input("qta_"+ id).get();
    var sconto = input("sconto_"+ id).get();
    var tipo_sconto = input("tipo_sconto_"+ id).get();
    var prezzo = input("prezzo_"+ id).get();
    var costo = input("costo_"+ id).get();

    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "POST",
        data: {
            id_module: globals.id_module,
            id_record: globals.id_record,
            op: "update_inline",
            riga_id: id,
            qta: qta,
            sconto: sconto,
            tipo_sconto: tipo_sconto,
            prezzo: prezzo,
            costo: costo
        },
        success: function (response) {
            caricaRighe(id);
            renderMessages();
        },
        error: function() {
            caricaRighe(null);
        }
    });
}
init();';

if (Plugin::where('name', 'Distinta base')->first()) {
    echo '
    async function viewDistinta(id_articolo) {
        openModal("'.tr('Distinta base').'", "'.Plugin::where('name', 'Distinta base')->first()->fileurl('view.php').'?id_module=" + globals.id_module + "&id_record=" + globals.id_record + "&id_articolo=" + id_articolo);
    }';
}
echo '
</script>';
