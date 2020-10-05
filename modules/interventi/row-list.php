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

$show_prezzi = Auth::user()['gruppo'] != 'Tecnici' || (Auth::user()['gruppo'] == 'Tecnici' && setting('Mostra i prezzi al tecnico'));

$righe = $intervento->getRighe();
if (!$righe->isEmpty()) {
    echo '
<div class="table-responsive">
    <table class="table table-striped table-hover table-condensed table-bordered">
        <thead>
            <tr>
                <th>'.tr('Descrizione').'</th>
                <th class="text-center" width="8%">'.tr('Q.tà').'</th>';

    if ($show_prezzi) {
        echo '
                <th class="text-center" width="15%">'.tr('Prezzo di acquisto').'</th>
                <th class="text-center" width="15%">'.tr('Prezzo di vendita').'</th>
                <th class="text-center" width="10%">'.tr('Iva unitaria').'</th>
                <th class="text-center" width="15%">'.tr('Importo').'</th>';
    }

    if (!$record['flag_completato']) {
        echo '
                <th class="text-center" width="120" class="text-center">'.tr('#').'</th>';
    }
    echo '
            </tr>
        </thead>

        <tbody>';

    foreach ($righe as $riga) {
        $extra = '';
        $mancanti = $riga->isArticolo() ? $riga->missing_serials_number : 0;
        if ($mancanti > 0) {
            $extra = 'class="warning"';
        }
        $descrizione = (!empty($riga->articolo) ? $riga->codice.' - ' : '').$riga['descrizione'];

        echo '
            <tr data-id="'.$riga->id.'" data-type="'.get_class($riga).'" '.$extra.'>
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

        echo '
                </td>';

        // Quantità
        echo '
                <td class="text-right">
                    '.Translator::numberToLocale($riga->qta, 'qta').' '.$riga->um.'
                </td>';

        if ($show_prezzi) {
            // Costo unitario
            echo '
                <td class="text-right">
                    '.moneyFormat($riga->costo_unitario).'
                </td>';

            // Prezzo unitario
            echo '
                <td class="text-right">
                    '.moneyFormat($riga->prezzo_unitario);

            if (abs($riga->sconto_unitario) > 0) {
                $text = discountInfo($riga);

                echo '
                    <br><small class="label label-danger">'.$text.'</small>';
            }

            echo '
                </td>';

            echo '
                <td class="text-right">
                    '.moneyFormat($riga->iva_unitaria).'
                    <br><small class="'.(($riga->aliquota->deleted_at) ? 'text-red' : '').' text-muted">'.$riga->aliquota->descrizione.(($riga->aliquota->esente) ? ' ('.$riga->aliquota->codice_natura_fe.')' : null).'</small>
                </td>';

            // Prezzo di vendita
            echo '
                <td class="text-right">
                    '.moneyFormat($riga->importo).'
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

                    <a class="btn btn-xs btn-danger" title="'.tr('Rimuovi riga').'" onclick="rimuoviRiga(this)">
                        <i class="fa fa-trash"></i>
                    </a>
                </div>';

            echo '
                </td>';
        }
        echo '
            </tr>';
    }

    echo '
        </tbody>
    </table>
</div>';
} else {
    echo '
<p>'.tr('Nessuna riga presente').'.</p>';
}

echo '
<script type="text/javascript">
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
        openModal("'.tr('Modifica sessione').'", "'.$module->fileurl('row-edit.php').'?id_module=" + globals.id_module + "&id_record=" + globals.id_record + "&riga_id=" + id + "&riga_type=" + type);
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

function modificaSeriali(button) {
    let riga = $(button).closest("tr");
    let id = riga.data("id");
    let type = riga.data("type");

    openModal("'.tr('Aggiorna SN').'", globals.rootdir + "/modules/fatture/add_serial.php?id_module=" + globals.id_module + "&id_record=" + globals.id_record + "&riga_id=" + id + "&riga_type=" + type);
}
</script>';
