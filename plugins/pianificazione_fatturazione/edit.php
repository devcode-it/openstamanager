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

include_once __DIR__.'/../../core.php';

use Modules\Contratti\Contratto;
use Modules\Contratti\Stato;

$contratto = Contratto::find($id_record);
if (empty($contratto)) {
    return;
}

$is_pianificabile = $contratto->stato->is_pianificabile && !empty($contratto['data_accettazione']); // Contratto permette la pianificazione
$is_pianificato = false;
$stati_pianificabili = Stato::where('is_pianificabile', 1)->get();
$elenco_stati = $stati_pianificabili->implode('descrizione', ', ');

echo '
<p>'.tr('Qui puoi pianificare la suddivisione del budget del contratto in rate uguali fatturabili in modo separato').'. '.tr('Questa procedura può essere effettuata solo una volta, e sovrascriverà in modo irreversibile tutte le righe del contratto').'.</p>
<p>'.tr('Per poter procedere, il contratto deve avere <b>data accettazione</b> e <b>data conclusione</b> definita ed essere in uno dei seguenti stati: _LINK_', [
    '_LINK_' => '<b>'.$elenco_stati.'</b>',
]).'.</p>

<div class="alert alert-warning">
    <i class="fa fa-warning"></i> '.tr("Tutte le righe del contratto vengono convertite in righe generiche, rendendo impossibile risalire ad eventuali articoli utilizzati all'interno del contratto e pertanto non movimentando il magazzino").'.
</div>';

$pianificazioni = $contratto->pianificazioni;
if (!$pianificazioni->isEmpty()) {
    echo '
    <hr>
    <table class="table table-bordered table-striped table-hover table-condensed">
        <thead>
            <tr>
                <th width="10%">'.tr('Scadenza').'</th>
                <th>'.tr('Documento').'</th>
                <th class="text-center" width="15%">'.tr('Importo').'</th>
                <th class="text-center" width="12%">#</th>
            </tr>
        </thead>
        <tbody>';

    $previous = null;
    foreach ($pianificazioni as $pianificazione) {
        echo '
            <tr>
                <td>';

        // Data scadenza
        if (!$pianificazione->data_scadenza->equalTo($previous)) {
            $previous = $pianificazione->data_scadenza;
            echo '
                    <b>'.ucfirst($pianificazione->data_scadenza->formatLocalized('%B %Y')).'</b>';
        }

        echo '
                </td>';

        // Documento collegato
        echo '
                <td>';
        $fattura = $pianificazione->fattura;
        if (!empty($fattura)) {
            $is_pianificato = true;
            echo '
                    '.Modules::link('Fatture di vendita', $fattura->id, tr('Fattura num. _NUM_ del _DATE_', [
                    '_NUM_' => $fattura->numero_esterno,
                    '_DATE_' => dateFormat($fattura->data),
                ])).' (<i class="'.$fattura->stato->icona.'"></i> '.$fattura->stato->descrizione.')';
        } else {
            echo '
                    <i class="fa fa-hourglass-start"></i> '.tr('Non ancora fatturato');
        }
        echo '
                </td>

                <td class="text-right">
                    '.moneyFormat($pianificazione->totale_imponibile).'
                </td>';

        // Creazione fattura
        echo '
                <td class="text-center">
                    <button type="button" class="btn btn-primary btn-sm '.(!empty($fattura) ? 'disabled' : '').'" '.(!empty($fattura) ? 'disabled' : '').' onclick="crea_fattura('.$pianificazione->id.')">
                        <i class="fa fa-euro"></i> '.tr('Crea fattura').'
                    </button>
                </td>
            </tr>';
    }

    echo '
        </tbody>
    </table>';

    echo '<button type="button" '.(($is_pianificato) ? 'disabled' : '').' title="'.tr('Annulla le pianificazioni').'"  data-id_plugin="'.$id_plugin.'" data-id_record="'.$id_record.'" data-id_module="'.$id_module.'" data-op="reset" data-msg="'.tr('Eliminare la pianificazione?').'"  data-button="'.tr('Elimina pianificazione').'" class="ask btn btn-danger pull-right tip"  data-backto="record-edit" >
    <i class="fa fa-ban"></i> '.tr('Annulla pianificazioni').'
    </button>';

    echo '<div class="clearfix"></div>';
} else {
    echo '
    <div class="alert alert-info">
        <i class="fa fa-info-circle"></i> '.tr('Pianificazione della fatturazione non impostata per questo contratto').'.
    </div>

    <button type="button" '.(!empty($is_pianificabile) ? '' : 'disabled').' title="'.tr('Aggiungi una nuova pianificazione').'" data-toggle="tooltip" class="btn btn-primary pull-right tip" id="pianifica">
        <i class="fa fa-plus"></i> '.tr('Pianifica').'
    </button>
    <div class="clearfix"></div>';
}

    echo '
    <script type="text/javascript">
        $("#pianifica").click(function() {
            openModal("Nuova pianificazione", "'.$structure->fileurl('add_pianificazione.php').'?id_module='.$id_module.'&id_plugin='.$id_plugin.'&id_record='.$id_record.'");
        });

        function crea_fattura(rata){
            openModal("Crea fattura", "'.$structure->fileurl('crea_fattura.php').'?id_module='.$id_module.'&id_plugin='.$id_plugin.'&id_record='.$id_record.'&rata=" + rata);
        }
    </script>';
