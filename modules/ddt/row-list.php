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

include_once __DIR__.'/init.php';

use Models\Plugin;

$block_edit = $record['flag_completato'];
$order_row_desc = $_SESSION['module_'.$id_module]['order_row_desc'];
$righe = $order_row_desc ? $ddt->getRighe()->sortByDesc('created_at') : $ddt->getRighe();
$colspan = $dir == 'entrata' ? '8' : '7';

$evasione_bar = [
    'Fatture di vendita' => 'success',
    'Interventi' => 'warning',
];

echo '
<div class="table-responsive row-list">
    <table class="table table-striped table-hover table-sm table-bordered">
        <thead>
            <tr>
                <th width="5" class="text-center">';
if (sizeof($righe) > 0) {
    echo '
                    <input id="check_all" type="checkbox"/>';
}
echo '
                </th>
                <th width="35" class="text-center">'.tr('#').'</th>
                <th>'.tr('Descrizione').'</th>
                <th width="100">'.tr('Documenti').'</th>
                <th class="text-center tip" width="150">'.tr('Q.tà').'</th>';
if ($dir == 'entrata') {
    echo '<th class="text-center" width="150">'.tr('Costo unitario').'</th>';
}
echo '
                <th class="text-center" width="180">'.tr('Prezzo unitario').'</th>
                <th class="text-center" width="140">'.tr('Sconto unitario').'</th>
                <th class="text-center" width="130">'.tr('Importo').'</th>
                <th width="80"></th>
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

    echo '
            <tr data-id="'.$riga->id.'" data-type="'.$riga::class.'">
                <td class="text-center">';
    echo '
                    <input class="check" type="checkbox"/>';
    echo '
                </td>

                <td class="text-center">
                    '.$num.'
                </td>

                <td>';
    if ($riga->isArticolo()) {
        echo Modules::link('Articoli', $riga->idarticolo, $riga->codice.' - '.$riga->descrizione);
    } else {
        echo nl2br((string) $riga->descrizione);
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

    if ($riga->isArticolo() && !empty($riga->barcode)) {
        echo '
                    <br><small><i class="fa fa-barcode"></i> '.$riga->barcode.'</small>';
    }

    if (!empty($riga->note)) {
        echo '
                    <br><span class="text-xs">'.nl2br((string) $riga->note).'</small>';
    }

    if (!empty($riga->data_inizio_competenza) || !empty($riga->data_fine_competenza)) {
        $has_alert = $riga->hasDifferentOriginalDateCompetenza();
        echo '
                    <br><span class="text-xs text-muted">'.tr('Competenza (_START_ - _END_)', [
            '_START_' => Translator::dateToLocale($riga->data_inizio_competenza),
            '_END_' => Translator::dateToLocale($riga->data_fine_competenza),
        ]).'
                        '.($has_alert ? '<i class="fa fa-warning text-danger"></i>' : '').'
                    </span>';
    }
    echo '
                </td>';

    $numero_riferimenti_riga = $riga->referenceTargets()->count();
    $numero_riferimenti_collegati = $riga->referenceSources()->count();
    $riferimenti_presenti = $numero_riferimenti_riga;
    $testo_aggiuntivo = $riferimenti_presenti ? '<span class="badge bg-info">'.$numero_riferimenti_riga.'</span>' : '';
    echo '
                <td>
                    <button type="button" class="btn btn-xs btn-default btn-block" onclick="apriRiferimenti(this)">
                        <i class="fa fa-chevron-right"></i> '.tr('Riferimenti').' '.$testo_aggiuntivo.'
                    </button>';

    // Aggiunta dei riferimenti ai documenti
    if ($riga->hasOriginalComponent()) {
        echo '
                    <button type="button" class="btn btn-xs btn-default btn-block">
                        <i class="fa fa-file-text-o"></i> '.reference($riga->getOriginalComponent()->getDocument(), tr('Origine')).'
                    </button>';
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
                <td></td>';
    } else {
        // Quantità e unità di misura
        echo '
                <td>
                    {[ "type": "number", "name": "qta_'.$riga->id.'", "value": "'.$riga->qta.'", "min-value": "0", "onchange": "aggiornaInline($(this).closest(\'tr\').data(\'id\'))", "icon-before": "'.$riga->um.'", "disabled": "'.($block_edit || $riga->isSconto()).'", "decimals": "qta" ]}

                    <span class="tip" title="'.tr('Quantità evasa').' / '.tr('totale').': '.tr('_QTA_ / _TOT_', ['_QTA_' => numberFormat($riga->qta_evasa, 'qta'), '_TOT_' => numberFormat($riga->qta, 'qta')]).'">
                        <div class="progress clickable" style="height:4px;" onclick="apriDocumenti(this)">';
        // Visualizzazione evasione righe per documento
        $color = '';
        $valore_evaso = 0;
        foreach ($elementi as $elemento) {
            $righe_evase = explode(', ', (string) $elemento['righe']);
            $righe_evase_array = array_reduce($righe_evase, function ($carry, $riga_evasa) {
                [$id, $qta] = explode(' - ', $riga_evasa);
                $carry[$id] = $qta;

                return $carry;
            }, []);
            foreach ($righe_evase_array as $id => $qta) {
                if ($id == $riga->id) {
                    $color = $evasione_bar[$elemento['modulo']];
                    $valore_evaso = $qta;
                    $perc_ev = $valore_evaso * 100 / ($riga->qta ?: 1);
                    if ($perc_ev > 0) {
                        echo '
                            <div class="progress-bar bg-'.$color.'" style="width:'.$perc_ev.'%"></div>';
                    }
                }
            }
        }
        echo '
                        </div>
                    </span>
                </td>';

        if ($riga->isArticolo()) {
            $id_anagrafica = $ddt->idanagrafica;
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
                    {[ "type": "number", "name": "costo_'.$riga->id.'", "value": "'.$riga->costo_unitario.'", "onchange": "aggiornaInline($(this).closest(\'tr\').data(\'id\'))", "icon-after": "'.currency().'", "disabled": "'.$block_edit.'" ]}
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
                    {[ "type": "number", "name": "prezzo_'.$riga->id.'", "value": "'.$riga->prezzo_unitario_corrente.'", "onchange": "aggiornaInline($(this).closest(\'tr\').data(\'id\'))", "icon-before": "'.(abs($riga->provvigione_unitaria) > 0 ? '<span class=\'tip text-info\' title=\''.provvigioneInfo($riga).'\'><small><i class=\'fa fa-handshake-o\'></i></small></span>' : '').'", "icon-after": "'.currency().'", "disabled": "'.$block_edit.'" ]}';

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

        // Importo
        echo '
                <td class="text-right">
                    '.moneyFormat($riga->importo);

        // Iva
        echo '
                    <br><small class="'.(($riga->aliquota->deleted_at) ? 'text-red' : '').' text-muted">'.$riga->aliquota->getTranslation('title').(($riga->aliquota->esente) ? ' ('.$riga->aliquota->codice_natura_fe.')' : null).'</small>
               </td>';
    }

    // Possibilità di rimuovere una riga solo se il ddt non è evaso
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

    if ($record['flag_completato'] == 0) {
        echo '
                        <a class="btn btn-xs btn-warning" title="'.tr('Modifica riga').'" onclick="modificaRiga(this)">
                            <i class="fa fa-edit"></i>
                        </a>

                        <a class="btn btn-xs btn-danger" title="'.tr('Rimuovi riga').'" onclick="rimuoviRiga([$(this).closest(\'tr\').data(\'id\')])">
                            <i class="fa fa-trash"></i>
                        </a>

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

// Calcoli
$imponibile = abs($ddt->imponibile);
$sconto = -$ddt->sconto;
$totale_imponibile = abs($ddt->totale_imponibile);
$iva = abs($ddt->iva);
$totale = abs($ddt->totale);
$sconto_finale = $ddt->getScontoFinale();
$netto_a_pagare = $ddt->netto;

// Totale totale imponibile
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
                <b><span class="tip" title="'.tr('Un importo negativo indica uno sconto, mentre uno positivo indica una maggiorazione').'"> <i class="fa fa-question-circle-o"></i> '.tr('Sconto/maggiorazione', [], ['upper' => true]).':</span></b>
            </td>
            <td class="text-right">
                '.moneyFormat($sconto, 2).'
            </td>
            <td></td>
        </tr>';

    // Totale totale imponibile
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

// Totale iva
echo '
        <tr>
            <td colspan="'.$colspan.'" class="text-right">
                <b>'.tr('Iva', [], ['upper' => true]).':</b>
            </td>
            <td class="text-right">
                '.moneyFormat($iva, 2).'
            </td>
            <td></td>
        </tr>';

// Totale ddt
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
if (!empty($ddt->provvigione)) {
    echo '
        <tr>
            <td colspan="'.$colspan.'" class="text-right">
            '.tr('Provvigioni', [], ['upper' => false]).':
            </td>
            <td class="text-right">
                '.moneyFormat($ddt->provvigione).'
            </td>
            <td></td>
        </tr>';

    echo '
    <tr>
        <td colspan="'.$colspan.'" class="text-right">
           '.tr('Netto da provvigioni', [], ['upper' => false]).':
        </td>
        <td class="text-right">
            '.moneyFormat($ddt->totale_imponibile - $ddt->provvigione).'
        </td>
        <td></td>
    </tr>';
}

echo '
    </table>';
if (sizeof($righe) > 0) {
    echo '
    <div class="btn-group">';
    if (!$block_edit) {
        echo '
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

        <button type="button" class="btn btn-xs btn-default disabled" id="modifica_iva_righe" onclick="modificaIvaRighe(getSelectData());">
            <i class="fa fa-percent"></i> '.tr('Modifica IVA').'
        </button>

        <button type="button" class="btn btn-xs btn-default disabled" id="stampa_barcode_righe" onclick="stampaBarcodeDDT(getSelectData());">
            <i class="fa fa-barcode"></i> '.tr('Stampa barcode').'
        </button>';
    }
    echo '
        <button type="button" class="btn btn-xs btn-default disabled" id="copia_righe" onclick="copiaRighe(getSelectData());" title="'.tr('Copia righe selezionate negli appunti').'">
            <i class="fa fa-clipboard"></i> '.tr('Copia').'
        </button>';

    // Il tasto incolla è disponibile solo se il documento non è bloccato
    if (!$block_edit) {
        echo '
        <button type="button" class="btn btn-xs btn-default" id="incolla_righe" onclick="incollaRighe();" title="'.tr('Incolla righe dagli appunti').'">
            <i class="fa fa-paste"></i> '.tr('Incolla').'
        </button>';
    }
    echo '
    </div>';
} else {
    // Anche quando non ci sono righe, il tasto incolla è disponibile solo se il documento non è bloccato
    if (!$block_edit) {
        echo '
    <div class="btn-group">
        <button type="button" class="btn btn-xs btn-default" id="incolla_righe" onclick="incollaRighe();" title="'.tr('Incolla righe dagli appunti').'">
            <i class="fa fa-paste"></i> '.tr('Incolla').'
        </button>
    </div>';
    }
}
echo '
</div>

<div class="container">
    <div class="row">
        <div class="col-md-2">
            <h5>'.tr('Quantità evasa in').':</h5>
        </div>
        <div class="col-md-2">
            <span class="pull-left icon" style="background-color:#28a745;"></span>
            <span class="text">&nbsp;'.tr('Fattura').'</span>
        </div>
        <div class="col-md-2">
            <span class="pull-left icon" style="background-color:#ffc107;"></span>
            <span class="text">&nbsp;'.tr('Attività').'</span>
        </div>
    </div>
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
                caricaRighe(null);
            },
            error: function() {
                renderMessages();
                caricaRighe(null);
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

function stampaBarcodeDDT(id) {
    if (id.length === 0) {
        swal({
            title: "'.tr('Nessuna riga selezionata').'",
            text: "'.tr('Seleziona almeno una riga articolo per stampare i barcode').'",
            type: "warning"
        });
        return;
    }

    swal({
        title: "'.tr('Stampare i barcode delle righe selezionate?').'",
        html: "'.tr('Verranno stampate le etichette barcode per ogni articolo selezionato, nella quantità presente nel DDT').'",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "'.tr('Stampa').'"
    }).then(function () {
        // Salva le righe selezionate in sessione e reindirizza alla stampa
        $.ajax({
            url: globals.rootdir + "/actions.php",
            type: "POST",
            data: {
                id_module: globals.id_module,
                id_record: globals.id_record,
                op: "print_barcode_righe",
                righe: id,
            },
            success: function (response) {
                // Apri la stampa in una nuova finestra
                window.open(response, "_blank");
            },
            error: function() {
                swal({
                    title: "'.tr('Errore').'",
                    text: "'.tr('Errore durante la preparazione della stampa').'",
                    type: "error"
                });
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

function apriRiferimenti(button) {
    let riga = $(button).closest("tr");
    let id = riga.data("id");
    let type = riga.data("type");

    openModal("'.tr('Riferimenti riga').'", globals.rootdir + "/actions.php?id_module=" + globals.id_module + "&id_record=" + globals.id_record + "&op=visualizza_righe_riferimenti&riga_id=" + id + "&riga_type=" + type)
}

function apriDocumenti(div) {
    let riga = $(div).closest("tr");
    let id = riga.data("id");
    let type = riga.data("type");

    openModal("'.tr('Documenti collegati').'", globals.rootdir + "/actions.php?id_module=" + globals.id_module + "&id_record=" + globals.id_record + "&op=visualizza_documenti_collegati&riga_id=" + id + "&riga_type=" + type)
}

function modificaIvaRighe(righe) {
    if (righe.length > 0) {
        openModal("'.tr('Modifica IVA').'", "'.$module->fileurl('modals/modifica_iva.php').'?id_module=" + globals.id_module + "&id_record=" + globals.id_record + "&righe=" + righe.join(','));
    }
}

function copiaRighe(righe) {
    if (righe.length === 0) return;
    $.ajax({
        url: globals.rootdir + "/actions.php", type: "POST", dataType: "json",
        data: { id_module: globals.id_module, id_record: globals.id_record, op: "get_righe_data", righe: righe },
        success: function (response) {
            if (response && response.data) {
                navigator.clipboard.writeText(JSON.stringify(response.data)).then(function() {
                    swal({ title: "'.tr('Righe copiate!').'", text: "'.tr('Le righe selezionate sono state copiate negli appunti').'", type: "success", timer: 2000, showConfirmButton: false });
                }).catch(function(err) { swal({ title: "'.tr('Errore').'", text: "'.tr('Impossibile copiare negli appunti').': " + err, type: "error" }); });
            }
        },
        error: function() { swal({ title: "'.tr('Errore').'", text: "'.tr('Errore durante il recupero dei dati delle righe').'", type: "error" }); }
    });
}

function incollaRighe() {
    navigator.clipboard.readText().then(function(text) {
        try {
            let righe_data = JSON.parse(text);
            swal({ title: "'.tr('Incollare le righe?').'", html: "'.tr('Sei sicuro di voler incollare').' " + righe_data.length + " '.tr('righe in questo documento?').'", type: "warning", showCancelButton: true, confirmButtonText: "'.tr('Sì').'" }).then(function () {
                $.ajax({
                    url: globals.rootdir + "/actions.php", type: "POST", dataType: "json",
                    data: { id_module: globals.id_module, id_record: globals.id_record, op: "paste_righe", righe_data: JSON.stringify(righe_data) },
                    success: function (response) {
                        renderMessages(); caricaRighe(null);
                        swal({ title: "'.tr('Righe incollate!').'", text: "'.tr('Le righe sono state incollate con successo').'", type: "success", timer: 2000, showConfirmButton: false });
                    },
                    error: function() { renderMessages(); swal({ title: "'.tr('Errore').'", text: "'.tr('Errore durante l\'incollaggio delle righe').'", type: "error" }); }
                });
            }).catch(swal.noop);
        } catch (e) { swal({ title: "'.tr('Errore').'", text: "'.tr('I dati negli appunti non sono validi').'", type: "error" }); }
    }).catch(function(err) { swal({ title: "'.tr('Errore').'", text: "'.tr('Impossibile leggere dagli appunti').': " + err, type: "error" }); });
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
        // Pulsanti sempre attivi anche se documento bloccato
        $("#copia_righe").removeClass("disabled");

        // Pulsanti attivi solo se documento non bloccato';
if (!$block_edit) {
    echo '
        $("#elimina_righe").removeClass("disabled");
        $("#duplica_righe").removeClass("disabled");
        $("#confronta_righe").removeClass("disabled");
        $("#aggiorna_righe").removeClass("disabled");
        $("#modifica_iva_righe").removeClass("disabled");
        $("#stampa_barcode_righe").removeClass("disabled");
        $("#elimina").addClass("disabled");';
}
echo '
    } else {
        // Pulsanti sempre disabilitati quando nessuna riga è selezionata
        $("#copia_righe").addClass("disabled");

        // Pulsanti disabilitati solo se documento non bloccato';
if (!$block_edit) {
    echo '
        $("#elimina_righe").addClass("disabled");
        $("#duplica_righe").addClass("disabled");
        $("#confronta_righe").addClass("disabled");
        $("#aggiorna_righe").addClass("disabled");
        $("#modifica_iva_righe").addClass("disabled");
        $("#stampa_barcode_righe").addClass("disabled");
        $("#elimina").removeClass("disabled");';
}
echo '
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

    // Controllo che gli elementi esistano prima di accedervi
    var qtaElement = $("input[name=\'qta_" + id + "\']");
    var scontoElement = $("input[name=\'sconto_" + id + "\']");
    var tipoScontoElement = $("select[name=\'tipo_sconto_" + id + "\']");
    var prezzoElement = $("input[name=\'prezzo_" + id + "\']");
    var costoElement = $("input[name=\'costo_" + id + "\']");

    if (qtaElement.length === 0) {
        console.error("Elemento qta_" + id + " non trovato");
        return;
    }

    var qta = qtaElement.length > 0 ? input("qta_"+ id).get() : 0;
    var sconto = scontoElement.length > 0 ? input("sconto_"+ id).get() : 0;
    var tipo_sconto = tipoScontoElement.length > 0 ? input("tipo_sconto_"+ id).get() : \'\';
    var prezzo = prezzoElement.length > 0 ? input("prezzo_"+ id).get() : 0;
    var costo = costoElement.length > 0 ? input("costo_"+ id).get() : 0;

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
