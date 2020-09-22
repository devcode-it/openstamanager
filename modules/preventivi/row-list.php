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

include_once __DIR__.'/init.php';

echo '
<div class="table-responsive">
    <table class="table table-striped table-hover table-condensed table-bordered">
        <thead>
            <tr>
                <th width="35" class="text-center" >'.tr('#').'</th>
                <th>'.tr('Descrizione').'</th>
                <th class="text-center tip" width="150" title="'.tr('da evadere').' / '.tr('totale').'">'.tr('Q.tà').' <i class="fa fa-question-circle-o"></i></th>
                <th class="text-center" width="150">'.tr('Prezzo unitario').'</th>
                <th class="text-center" width="150">'.tr('Iva unitaria').'</th>
                <th class="text-center" width="150">'.tr('Importo').'</th>
                <th width="100"></th>
            </tr>
        </thead>
        <tbody class="sortable">';

// Righe documento
$righe = $preventivo->getRighe();
$num = 0;
foreach ($righe as $riga) {
    ++$num;

    echo '
            <tr data-id="'.$riga->id.'" data-type="'.get_class($riga).'">
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
                    '.moneyFormat($riga->iva_unitaria).'
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

                        <a class="btn btn-xs btn-danger" title="'.tr('Rimuovi riga').'" onclick="rimuoviRiga(this)">
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
$imponibile = abs($preventivo->imponibile);
$sconto = $preventivo->sconto;
$totale_imponibile = abs($preventivo->totale_imponibile);
$iva = abs($preventivo->iva);
$totale = abs($preventivo->totale);

// Totale imponibile scontato
echo '
        <tr>
            <td colspan="5" class="text-right">
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
            <td colspan="5" class="text-right">
                <b><span class="tip" title="'.tr('Un importo positivo indica uno sconto, mentre uno negativo indica una maggiorazione').'"> <i class="fa fa-question-circle-o"></i> '.tr('Sconto/maggiorazione', [], ['upper' => true]).':</span></b>
            </td>
            <td class="text-right">
                '.moneyFormat($preventivo->sconto, 2).'
            </td>
            <td></td>
        </tr>';

    // Totale imponibile scontato
    echo '
        <tr>
            <td colspan="5" class="text-right">
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
            <td colspan="5" class="text-right">
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
            <td colspan="5" class="text-right">
                <b>'.tr('Totale', [], ['upper' => true]).':</b>
            </td>
            <td class="text-right">
                '.moneyFormat($preventivo->totale, 2).'
            </td>
            <td></td>
        </tr>';

// Margine
$margine = $preventivo->margine;
$margine_class = ($margine <= 0 and $preventivo->totale > 0) ? 'danger' : 'success';
$margine_icon = ($margine <= 0 and $preventivo->totale > 0) ? 'warning' : 'check';

echo '
        <tr>
            <td colspan="5" class="text-right">
                '.tr('Costi').':
            </td>
            <td class="text-right">
                '.moneyFormat($preventivo->spesa).'
            </td>
            <td></td>
        </tr>

        <tr>
            <td colspan="5" class="text-right">
                '.tr('Margine (_PRC_%)', [
                    '_PRC_' => numberFormat($preventivo->margine_percentuale),
            ]).':
            </td>
            <td class="text-right" class="'.$margine_class.'">
                <i class="fa fa-'.$margine_icon.' text-'.$margine_class.'"></i> '.moneyFormat($preventivo->margine).'
            </td>
            <td></td>
        </tr>';

echo '
    </table>
</div>';

echo '
<script>
async function modificaRiga(button) {
    let riga = $(button).closest("tr");
    let id = riga.data("id");
    let type = riga.data("type");

    // Salvataggio via AJAX
    let valid = await salvaForm(button, $("#edit-form"));

    if (valid) {
        // Chiusura tooltip
        if ($(button).hasClass("tooltipstered"))
            $(button).tooltipster("close");

        // Apertura modal
        openModal("'.tr('Modifica riga').'", "'.$module->fileurl('row-edit.php').'?id_module=" + globals.id_module + "&id_record=" + globals.id_record + "&riga_id=" + id + "&riga_type=" + type);
    }
}

function rimuoviRiga(button) {
    swal({
        title: "'.tr('Rimuovere questa riga?').'",
        html: "'.tr('Sei sicuro di volere rimuovere questa riga dal documento?').' '.tr("L'operazione è irreversibile").'.",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "'.tr('Sì').'"
    }).then(function () {
        let riga = $(button).closest("tr");
        let id = riga.data("id");
        let type = riga.data("type");

        $.ajax({
            url: globals.rootdir + "/actions.php",
            type: "POST",
            dataType: "json",
            data: {
                id_module: globals.id_module,
                id_record: globals.id_record,
                op: "delete_riga",
                riga_type: type,
                riga_id: id,
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
	$(".sortable").each(function() {
        $(this).sortable({
            axis: "y",
            handle: ".handle",
			cursor: "move",
			dropOnEmpty: true,
			scroll: true,
			update: function(event, ui) {
                let order = $(".table tr[data-id]").toArray().map(a => $(a).data("id"))

				$.post(globals.rootdir + "/actions.php", {
					id: ui.item.data("id"),
					id_module: '.$id_module.',
					id_record: '.$id_record.',
					op: "update_position",
                    order: order.join(","),
				});
			}
		});
	});
});
</script>';
