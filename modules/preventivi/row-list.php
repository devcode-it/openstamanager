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

$block_edit = $record['is_completato'];
$order_row_desc = $_SESSION['module_'.$id_module]['order_row_desc'];
$righe = $order_row_desc ? $preventivo->getRighe()->sortByDesc('created_at') : $preventivo->getRighe();
$colspan = '9';

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
                <th>'.tr('Descrizione').'</th>
                <th width="100">'.tr('Documenti').'</th>
                <th width="105">'.tr('Prev. evasione').'</th>
                <th class="text-center tip" width="160">'.tr('Q.tà').'</th>
                <th class="text-center" width="150">'.tr('Costo unitario').'</th>
                <th class="text-center" width="180">'.tr('Prezzo unitario').'</th>
                <th class="text-center" width="140">'.tr('Sconto unitario').'</th>
                <th class="text-center" width="130">'.tr('Importo').'</th>
                <th width="100"></th>
            </tr>
        </thead>
        <tbody class="sortable" id="righe">';

// Righe documento
$today = new Carbon\Carbon();
$today = $today->startOfDay();
$num = 0;
$has_gruppo = false;
$subtotale_gruppo = 0;
$iva_gruppo = 0;
$color_gruppo = '#BDDEE1;';

$evasione_bar = [
    'Ddt in entrata' => 'info',
    'Ddt in uscita' => 'info',
    'Fatture di vendita' => 'success',
    'Interventi' => 'warning',
    'Ordini cliente' => 'primary',
    'Ordini fornitore' => 'primary',
    'Contratti' => 'orange',
];

foreach ($righe as $key => $riga) {
    $show_notifica = [];
    // Gestione gruppo
    $style_titolo = '';
    $colspan_titolo = '';
    if ($riga->is_titolo) {
        $subtotale_gruppo = 0;
        $iva_gruppo = 0;
        $has_gruppo = true;
        $style_titolo = 'style="background-color:'.$color_gruppo.'"';
        $colspan_titolo = 'colspan="7"';
    }
    $subtotale_gruppo += $riga->totale_imponibile;
    $iva_gruppo += $riga->iva;
    ++$num;

    echo '
            <tr data-id="'.$riga->id.'" data-type="'.$riga::class.'" '.$style_titolo.'>
                <td class="text-center">';
    if (!$block_edit) {
        echo '
                    <input class="check" type="checkbox"/>';
    }
    echo '
                </td>

                <td class="text-center">
                    '.$num.'
                </td>

                <td '.$colspan_titolo.'>';

    // Descrizione
    $descrizione = nl2br($riga->descrizione);
    if ($riga->isArticolo()) {
        $descrizione = Modules::link('Articoli', $riga->idarticolo, $riga->codice.' - '.$descrizione);
    }
    echo '
                    '.$descrizione;

    if ($riga->isArticolo() && !empty($riga->articolo->barcode)) {
        echo '
        <br><small><i class="fa fa-barcode"></i> '.$riga->articolo->barcode.'</small>';
    }

    if (!empty($riga->note)) {
        echo '
                    <br><span class="right badge badge-default">'.nl2br($riga->note).'</small>';
    }
    echo '
                </td>
                
                <td>';
    // Aggiunta dei riferimenti ai documenti
    if ($riga->hasOriginalComponent()) {
        echo '
                    <button type="button" class="btn btn-xs btn-default btn-block">
                        <i class="fa fa-file-text-o"></i> '.reference($riga->getOriginalComponent()->getDocument(), tr('Origine')).'
                    </button>';
    }
    echo '
                </td>';

    // Data prevista evasione
    $info_evasione = '';
    if (!empty($riga->data_evasione)) {
        $evasione = new Carbon\Carbon($riga->data_evasione);
        if ($today->diffInDays($evasione, false) < 0 && $riga->qta_evasa < $riga->qta) {
            $evasione_icon = 'fa fa-warning text-danger';
            $evasione_help = tr('Da consegnare _NUM_ giorni fa',
                [
                    '_NUM_' => $today->diffInDays($evasione),
                ]
            );
        } elseif ($today->diffInDays($evasione, false) == 0 && $riga->qta_evasa < $riga->qta) {
            $evasione_icon = 'fa fa-clock-o text-warning';
            $evasione_help = tr('Da consegnare oggi');
        } else {
            $evasione_icon = 'fa fa-check text-success';
            $evasione_help = tr('Da consegnare fra _NUM_ giorni',
                [
                    '_NUM_' => $today->diffInDays($evasione),
                ]
            );
        }

        if (!empty($riga->ora_evasione)) {
            $ora_evasione = '<br>'.Translator::timeToLocale($riga->ora_evasione).'';
        } else {
            $ora_evasione = '';
        }

        $info_evasione = '<span class="tip" title="'.$evasione_help.'"><i class="'.$evasione_icon.'"></i> '.Translator::dateToLocale($riga->data_evasione).$ora_evasione.'</span>';
    }

    if (!$riga->is_titolo) {
        if ($riga->isDescrizione()) {
            echo '
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>';
        } else {
            // Info evasione
            echo '
            <td class="text-center">
                '.$info_evasione.'
            </td>';

            // Quantità e unità di misura
            echo '
                    <td class="text-center">
                        {[ "type": "number", "name": "qta_'.$riga->id.'", "value": "'.$riga->qta.'", "min-value": "0", "onchange": "aggiornaInline($(this).closest(\'tr\').data(\'id\'))", "icon-after": "<span class=\'tip\' title=\''.($riga->confermato ? tr('Articolo confermato') : tr('Articolo non confermato')).'\'><i class=\''.($riga->confermato ? 'fa fa-check text-success' : 'fa fa-clock-o text-warning').'\'></i></span>", "icon-before": "'.$riga->um.'", "disabled": "'.($block_edit || $riga->isSconto()).'", "decimals": "qta" ]}

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
                $id_anagrafica = $preventivo->idanagrafica;
                $dir = 'entrata';
                $show_notifica = getPrezzoConsigliato($id_anagrafica, $dir, $riga->idarticolo, $riga);
            }

            if ($riga->isSconto()) {
                echo '
                    <td></td>
                    <td></td>';
            } else {
                // Costi unitari
                echo '
                    <td>
                        {[ "type": "number", "name": "costo_'.$riga->id.'", "value": "'.$riga->costo_unitario.'", "onchange": "aggiornaInline($(this).closest(\'tr\').data(\'id\'))", "icon-after": "'.currency().'", "disabled": "'.$block_edit.'" ]}
                    </td>';

                // Prezzi unitari
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
                    <td class="text-center">
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
    }

    // Possibilità di rimuovere una riga solo se il preventivo non è stato pagato
    echo '
                <td class="text-center">
                    <div class="btn-group">';
    if (hasArticoliFiglio($riga->idarticolo)) {
        echo '
                        <a class="btn btn-xs btn-info" title="'.tr('Distinta base').'" onclick="viewDistinta('.$riga->idarticolo.')">
                            <i class="fa fa-eye"></i>
                        </a>';
    }

    if (empty($record['is_completato'])) {
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

    $next = $righe->flatten()[$num];
    if ($has_gruppo && ($next->is_titolo || $next == null)) {
        echo '
            <tr>
                <td style="background-color:'.$color_gruppo.'" colspan="'.$colspan.'" class="text-right">
                    <b>'.tr('Subtotale', [], ['upper' => true]).':</b>
                </td>
                <td style="background-color:'.$color_gruppo.'" class="text-right">
                    '.moneyFormat($subtotale_gruppo, 2).'
                </td>
                <td style="background-color:'.$color_gruppo.'"></td>
            </tr>

            <tr>
                <td style="background-color:'.$color_gruppo.'" colspan="'.$colspan.'" class="text-right">
                    <b>'.tr('Iva', [], ['upper' => true]).':</b>
                </td>
                <td style="background-color:'.$color_gruppo.'" class="text-right">
                    '.moneyFormat($iva_gruppo, 2).'
                </td>
                <td style="background-color:'.$color_gruppo.'"></td>
            </tr>

            <tr>
                <td style="background-color:'.$color_gruppo.'" colspan="'.$colspan.'" class="text-right">
                    <b>'.tr('Subtotale ivato', [], ['upper' => true]).':</b>
                </td>
                <td style="background-color:'.$color_gruppo.'" class="text-right">
                    '.moneyFormat($subtotale_gruppo + $iva_gruppo, 2).'
                </td>
                <td style="background-color:'.$color_gruppo.'"></td>
            </tr>';
    }
}

echo '
        </tbody>';

// Calcoli
$imponibile = abs($preventivo->imponibile);
$sconto = -$preventivo->sconto;
$totale_imponibile = abs($preventivo->totale_imponibile);
$iva = abs($preventivo->iva);
$totale = abs($preventivo->totale);
$sconto_finale = $preventivo->getScontoFinale();
$netto_a_pagare = $preventivo->netto;

// Totale imponibile scontato
echo '
        <tr>
            <td colspan="'.$colspan.'" class="text-right">
                <b>'.tr('Imponibile', [], ['upper' => true]).':</b>
            </td>
            <td class="text-right">
                '.moneyFormat($preventivo->imponibile, 2).'
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

    // Totale imponibile scontato
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
                '.moneyFormat($preventivo->iva, 2).'
            </td>
            <td></td>
        </tr>';

// Totale
echo '
        <tr>
            <td colspan="'.$colspan.'" class="text-right">
                <b>'.tr('Totale documento', [], ['upper' => true]).':</b>
            </td>
            <td class="text-right">
                '.moneyFormat($preventivo->totale, 2).'
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

// Margine
$margine = $preventivo->margine;
$margine_class = ($margine <= 0 && $preventivo->totale > 0) ? 'danger' : 'success';
$margine_icon = ($margine <= 0 && $preventivo->totale > 0) ? 'warning' : 'check';

echo '
        <tr>
            <td colspan="'.$colspan.'" class="text-right">
                '.tr('Costi').':
            </td>
            <td class="text-right">
                '.moneyFormat($preventivo->spesa).'
            </td>
            <td></td>
        </tr>';

// Provvigione
if (!empty($preventivo->provvigione)) {
    echo '
        <tr>
            <td colspan="'.$colspan.'" class="text-right">
                '.tr('Provvigioni').':
            </td>
            <td class="text-right">
                '.moneyFormat($preventivo->provvigione).'
            </td>
            <td></td>
        </tr>';
}

echo '
        <tr>
            <td colspan="'.$colspan.'" class="text-right">
                '.tr('Margine (_PRC_%)', [
    '_PRC_' => numberFormat($preventivo->margine_percentuale),
]).':
            </td>
            <td class="text-right '.$margine_class.'" rowspan="2" style="vertical-align:middle;">
                <i class="fa fa-'.$margine_icon.' text-'.$margine_class.'"></i> '.moneyFormat($preventivo->margine).'
            </td>
            <td rowspan="2"></td>
        </tr>
        
        <tr>
            <td colspan="'.$colspan.'" class="text-right">
                '.tr('Ricarico (_PRC_)', [
    '_PRC_' => ($preventivo->ricarico_percentuale != 0 ? numberFormat($preventivo->ricarico_percentuale).'%' : 'N.D.'),
]).':
            </td>
        </tr>';

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
        </button>

        <button type="button" class="btn btn-xs btn-default disabled" id="confronta_righe" onclick="confrontaRighe(getSelectData());">
            '.tr('Confronta prezzi').'
        </button>

        <button type="button" class="btn btn-xs btn-default disabled" id="aggiorna_righe" onclick="aggiornaRighe(getSelectData());">
            '.tr('Aggiorna prezzi').'
        </button>
    </div>';
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
            <span class="pull-left icon" style="background-color:#17a2b8;"></span>
            <span class="text">&nbsp;'.tr('DDT').'</span>
        </div>
        <div class="col-md-2">
            <span class="pull-left icon" style="background-color:#ffc107;"></span>
            <span class="text">&nbsp;'.tr('Attività').'</span>
        </div>
        <div class="col-md-2">
            <span class="pull-left icon" style="background-color:#007bff;;"></span>
            <span class="text">&nbsp;'.tr('Ordine').'</span>
        </div>
        <div class="col-md-2">
            <span class="pull-left icon" style="background-color:#fd7e14;"></span>
            <span class="text">&nbsp;'.tr('Contratto').'</span>
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
            dataType: "json",
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
function apriDocumenti(div) {
    let riga = $(div).closest("tr");
    let id = riga.data("id");
    let type = riga.data("type");

    openModal("'.tr('Documenti collegati').'", globals.rootdir + "/actions.php?id_module=" + globals.id_module + "&id_record=" + globals.id_record + "&op=visualizza_documenti_collegati&riga_id=" + id + "&riga_type=" + type)
}
</script>';
