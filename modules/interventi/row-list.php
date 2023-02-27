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

$block_edit = $record['flag_completato'];
$righe = $intervento->getRighe();
$colspan = ($block_edit ? '5' : '6');

$show_prezzi = Auth::user()['gruppo'] != 'Tecnici' || (Auth::user()['gruppo'] == 'Tecnici' && setting('Mostra i prezzi al tecnico'));

echo '
<div class="table-responsive row-list">
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
                <th>'.tr('Descrizione').'</th>
                <th class="text-center" width="150">'.tr('Q.tà').'</th>';

    if ($show_prezzi) {
        echo '
                <th class="text-center" width="140">'.tr('Prezzo di acquisto').'</th>
                <th class="text-center" width="140">'.tr('Prezzo di vendita').'</th>';
            if (!$block_edit) {
                echo '<th class="text-center" width="150">'.tr('Sconto unitario').'</th>';
            }
            echo '
                <th class="text-center" width="140">'.tr('Importo').'</th>';
    }

    if (!$record['flag_completato']) {
        echo '
                <th class="text-center" width="60" class="text-center">'.tr('&nbsp;').'</th>';
    }
    echo '
            </tr>
        </thead>

        <tbody class="sortable" id="righe">';

    foreach ($righe as $riga) {
        $extra = '';
        $mancanti = $riga->isArticolo() ? $riga->missing_serials_number : 0;
        if ($mancanti > 0) {
            $extra = 'class="warning"';
        }
        $descrizione = (!empty($riga->articolo) ? $riga->codice.' - ' : '').$riga['descrizione'];

        echo '
            <tr data-id="'.$riga->id.'" data-type="'.get_class($riga).'" '.$extra.'>
                <td class="text-center">';
                if (!$block_edit) {
                    echo '
                    <input class="check" type="checkbox"/>';
                }
                echo '
                </td>
                <td>';

        // Informazioni aggiuntive sulla destra
        echo '
                <small class="pull-right text-right text-muted">';

        // Aggiunta dei riferimenti ai documenti
        if ($riga->hasOriginalComponent()) {
            echo '
                    '.reference($riga->getOriginalComponent()->getDocument(), tr('Origine'));
        }

        echo '
                </small>';

        echo '
                    '.Modules::link($riga->isArticolo() ? Modules::get('Articoli')['id'] : null, $riga->isArticolo() ? $riga['idarticolo'] : null, $descrizione);

        if ($riga->isArticolo()) {
            if (!empty($mancanti)) {
                echo '
                    <br><b><small class="text-danger">'.tr('_NUM_ serial mancanti', [
                        '_NUM_' => $mancanti,
                    ]).'</small></b>';
            }

            $serials = $riga->serials;
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
                echo '
                    <br><small class="label label-default">'.nl2br($riga->note).'</small>';
        }
        echo '
                </td>';

            // Quantità e unità di misura
            echo '
                <td class="text-center">
                    {[ "type": "number", "name": "qta_'.$riga->id.'", "value": "'.$riga->qta.'", "min-value": "0", "onchange": "aggiornaInline($(this).closest(\'tr\').data(\'id\'))", "icon-after": "'.($riga->um ?: '&nbsp;').'", "disabled": "'.($riga->isSconto() ? 1 : 0).'", "disabled": "'.$block_edit.'" ]}
                </td>';

        if ($show_prezzi) {
            // Costo unitario
            echo '
                <td class="text-right">
                    '.moneyFormat($riga->costo_unitario).'
                </td>';

            // Prezzo unitario
            echo '
                <td class="text-right">';
                    // Provvigione riga 
                    if (abs($riga->provvigione_unitaria) > 0) {
                        $text = provvigioneInfo($riga);
                        echo '<span class="pull-left text-info" title="'.$text.'"><i class="fa fa-handshake-o"></i></span>';
                    } 
                    echo moneyFormat($riga->prezzo_unitario);

            if (abs($riga->sconto_unitario) > 0) {
                $text = discountInfo($riga);

                echo '
                    <br><small class="label label-danger">'.$text.'</small>';
            }

            echo '
                </td>';

           // Sconto unitario
        if (!$block_edit) {
            echo '
                <td class="text-center">
                    {[ "type": "number", "name": "sconto_'.$riga->id.'", "value": "'.($riga->sconto_percentuale ?: $riga->sconto_unitario_corrente).'", "min-value": "0", "onchange": "aggiornaInline($(this).closest(\'tr\').data(\'id\'))", "icon-after": "choice|untprc|'.$riga->tipo_sconto.'" ]}
                </td>';
        }

            // Prezzo di vendita
            echo '
                <td class="text-right">
                    '.moneyFormat($riga->importo);

                    // Iva
                    echo '
                    <br><small class="'.(($riga->aliquota->deleted_at) ? 'text-red' : '').' text-muted">'.$riga->aliquota->descrizione.(($riga->aliquota->esente) ? ' ('.$riga->aliquota->codice_natura_fe.')' : null).'</small>
                </td>';
        }

        // Pulsante per riportare nel magazzino centrale.
        // Visibile solo se l'intervento non è stato nè fatturato nè completato.
        if (!$record['flag_completato']) {
            echo '
                <td class="text-center">
                <div class="input-group-btn">';

            if ($riga->isArticolo() && !empty($riga->abilita_serial)) {
                echo '
                    <a class="btn btn-primary btn-xs" title="'.tr('Modifica seriali della riga').'" onclick="modificaSeriali(this)">
                        <i class="fa fa-barcode"></i>
                    </a>';
            }

            echo '
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

            echo '
                </td>';
        }
        echo '
            </tr>';
    }

    echo '
        </tbody>';

        if ($show_prezzi) {
            
            // IMPONIBILE
            echo '
            <tr>
                <td colspan="'.$colspan.'" class="text-right">
                    <b>'.tr('Imponibile', [], ['upper' => true]).':</b>
                </td>
                <td class="text-right">
                    '.moneyFormat($righe->sum('imponibile'), 2).'
                </td>
                <td></td>
            </tr>';

        // SCONTO
        if (!empty($intervento->sconto)) {
            echo '
            <tr>
                <td colspan="'.$colspan.'" class="text-right">
                    <b><span class="tip" title="'.tr('Un importo positivo indica uno sconto, mentre uno negativo indica una maggiorazione').'"> <i class="fa fa-question-circle-o"></i> '.tr('Sconto/maggiorazione', [], ['upper' => true]).':</span></b>
                </td>
                <td class="text-right">
                    '.moneyFormat($intervento->sconto, 2).'
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
                    '.moneyFormat($righe->sum('totale_imponibile'), 2).'
                </td>
                <td></td>
            </tr>';
        }

        // Provvigione
        if(!empty($intervento->provvigione)) {
            echo '
            <tr>
                <td colspan="'.$colspan.'" class="text-right">
                    '.tr('Provvigioni').':
                </td>
                <td class="text-right">
                    '.moneyFormat($intervento->provvigione).'
                </td>
                <td></td>
            </tr>';
        }

        }

    echo'
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
    </div>';
}
echo '
</div>
    
<script type="text/javascript">
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
    openModal("'.tr('Modifica sessione').'", "'.$module->fileurl('row-edit.php').'?id_module=" + globals.id_module + "&id_record=" + globals.id_record + "&riga_id=" + id + "&riga_type=" + type);
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

function modificaSeriali(button) {
    let riga = $(button).closest("tr");
    let id = riga.data("id");
    let type = riga.data("type");

    openModal("'.tr('Aggiorna SN').'", globals.rootdir + "/modules/fatture/add_serial.php?id_module=" + globals.id_module + "&id_record=" + globals.id_record + "&riga_id=" + id + "&riga_type=" + type);
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

$(".tipo_icon_after").on("change", function() {
    aggiornaInline($(this).closest("tr").data("id"));
});

function aggiornaInline(id) {
    content_was_modified = false;
    var qta = input("qta_"+ id).get();
    var sconto = input("sconto_"+ id).get();
    var tipo_sconto = input("tipo_sconto_"+ id).get();

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
init();
</script>';
