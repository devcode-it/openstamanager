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

$block_edit = $record['is_completato'];
$righe = $contratto->getRighe();

echo '
<div class="table-responsive">
    <table class="table table-striped table-hover table-condensed table-bordered">
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
                <th class="text-center tip" width="150" title="'.tr('da evadere').' / '.tr('totale').'">'.tr('Q.tà').' <i class="fa fa-question-circle-o"></i></th>
                <th class="text-center" width="150">'.tr('Prezzo unitario').'</th>
                <th class="text-center" width="150">'.tr('Iva unitaria').'</th>
                <th class="text-center" width="150">'.tr('Importo').'</th>
                <th width="100"></th>
            </tr>
        </thead>

        <tbody class="sortable" id="righe">';

// Righe documento
$num = 0;
foreach ($righe as $riga) {
    ++$num;

    echo '
            <tr data-id="'.$riga->id.'" data-type="'.get_class($riga).'">
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

                <td>';

    // Aggiunta dei riferimenti ai documenti
    if ($riga->hasOriginalComponent()) {
        echo '
                    <small class="pull-right text-right text-muted">'.reference($riga->getOriginalComponent()->getDocument(), tr('Origine')).'</small>';
    }

    // Descrizione
    $descrizione = nl2br($riga->descrizione);
    if ($riga->isArticolo()) {
        $descrizione = Modules::link('Articoli', $riga->idarticolo, $riga->codice.' - '.$descrizione);
    }

    echo '
                    '.$descrizione.'
                </td>';

    if ($riga->isDescrizione()) {
        echo '
                <td></td>
                <td></td>
                <td></td>
                <td></td>';
    } else {
        // Quantità e unità di misura
        echo '
                <td class="text-center">
                    '.numberFormat($riga->qta_rimanente, 'qta').' / '.numberFormat($riga->qta, 'qta').' '.$riga->um.'
                </td>';

        // Prezzi unitari
        echo '
                <td class="text-right">
                    '.moneyFormat($riga->prezzo_unitario_corrente);

        if ($dir == 'entrata' && $riga->costo_unitario != 0) {
            echo '
                    <br><small class="text-muted">
                        '.tr('Acquisto').': '.moneyFormat($riga->costo_unitario).'
                    </small>';
        }

        if (abs($riga->sconto_unitario) > 0) {
            $text = discountInfo($riga);

            echo '
                    <br><small class="label label-danger">'.$text.'</small>';
        }

        echo '
                </td>';

        // Iva
        echo '
                <td class="text-right">
                    '.moneyFormat($riga->iva_unitaria_scontata).'
                    <br><small class="'.(($riga->aliquota->deleted_at) ? 'text-red' : '').' text-muted">'.$riga->aliquota->descrizione.(($riga->aliquota->esente) ? ' ('.$riga->aliquota->codice_natura_fe.')' : null).'</small>
                </td>';

        // Importo
        echo '
                <td class="text-right">
                    '.moneyFormat($riga->importo).'
                </td>';
    }

    // Possibilità di rimuovere una riga solo se il preventivo non è stato pagato
    echo '
                <td class="text-center">';

    if (empty($record['is_completato'])) {
        echo '
                    <div class="btn-group">
                        <a class="btn btn-xs btn-warning" title="'.tr('Modifica riga').'" onclick="modificaRiga(this)">
                            <i class="fa fa-edit"></i>
                        </a>

                        <a class="btn btn-xs btn-danger" title="'.tr('Rimuovi riga').'" onclick="rimuoviRiga([$(this).closest(\'tr\').data(\'id\')])">
                            <i class="fa fa-trash"></i>
                        </a>

                        <a class="btn btn-xs btn-default handle" title="'.tr('Modifica ordine delle righe').'">
                            <i class="fa fa-sort"></i>
                        </a>
                    </div>';
    }

    echo '
                </td>
            </tr>';
}

echo '
        </tbody>';

// Calcoli
$imponibile = abs($contratto->imponibile);
$sconto = $contratto->sconto;
$totale_imponibile = abs($contratto->totale_imponibile);
$iva = abs($contratto->iva);
$totale = abs($contratto->totale);
$sconto_finale = $contratto->getScontoFinale();
$netto_a_pagare = $contratto->netto;

// Totale totale imponibile
echo '
        <tr>
            <td colspan="6" class="text-right">
                <b>'.tr('Imponibile', [], ['upper' => true]).':</b>
            </td>
            <td class="text-right">
                '.moneyFormat($contratto->imponibile, 2).'
            </td>
            <td></td>
        </tr>';

// SCONTO
if (!empty($sconto)) {
    echo '
        <tr>
            <td colspan="6" class="text-right">
                <b><span class="tip" title="'.tr('Un importo positivo indica uno sconto, mentre uno negativo indica una maggiorazione').'"> <i class="fa fa-question-circle-o"></i> '.tr('Sconto/maggiorazione', [], ['upper' => true]).':</span></b>
            </td>
            <td class="text-right">
                '.moneyFormat($contratto->sconto, 2).'
            </td>
            <td></td>
        </tr>';

    // Totale totale imponibile
    echo '
        <tr>
            <td colspan="6" class="text-right">
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
            <td colspan="6" class="text-right">
                <b>'.tr('Iva', [], ['upper' => true]).':</b>
            </td>
            <td class="text-right">
                '.moneyFormat($contratto->iva, 2).'
            </td>
            <td></td>
        </tr>';

// Totale contratto
echo '
        <tr>
            <td colspan="6" class="text-right">
                <b>'.tr('Totale', [], ['upper' => true]).':</b>
            </td>
            <td class="text-right">
                '.moneyFormat($contratto->totale, 2).'
            </td>
            <td></td>
        </tr>';

// SCONTO IN FATTURA
if (!empty($sconto_finale)) {
    echo '
        <tr>
            <td colspan="6" class="text-right">
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
            <td colspan="6" class="text-right">
                <b>'.tr('Netto a pagare', [], ['upper' => true]).':</b>
            </td>
            <td class="text-right">
                '.moneyFormat($netto_a_pagare, 2).'
            </td>
            <td></td>
        </tr>';
}

// Provvigione
if(!empty($contratto->provvigione)) {
    echo '
        <tr>
            <td colspan="6" class="text-right">
                '.tr('Provvigioni').':
            </td>
            <td class="text-right">
                '.moneyFormat($contratto->provvigione).'
            </td>
            <td></td>
        </tr>';
}

echo '
    </table>';
if (!$block_edit && sizeof($righe) > 0) {
    echo '
    <div class="btn-group">
        <button type="button" class="btn btn-xs btn-default disabled" id="elimina_righe" onclick="duplicaRiga(getSelectData());">
            <i class="fa fa-copy"></i>
        </button>

        <button type="button" class="btn btn-xs btn-default disabled" id="duplica_righe" onclick="rimuoviRiga(getSelectData());">
            <i class="fa fa-trash"></i>
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
                location.reload();
            },
            error: function() {
                location.reload();
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
                location.reload();
            },
            error: function() {
                location.reload();
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
    } else {
        $("#elimina_righe").addClass("disabled");
        $("#duplica_righe").addClass("disabled");
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
</script>';
