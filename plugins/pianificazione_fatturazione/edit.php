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
use Modules\Fatture\Fattura;

$contratto = Contratto::find($id_record);
if (empty($contratto)) {
    return;
}

// Elenco delle fatture di vendita collegate alla stessa anagrafica del contratto,
// utilizzabili per il collegamento manuale ad una rata di pianificazione
$fatture_collegabili = Fattura::vendita()
    ->where('id_anagrafica', $contratto->id_anagrafica)
    ->orderBy('data', 'DESC')
    ->get();

$is_pianificabile = $contratto->stato->is_pianificabile && !empty($contratto['data_accettazione']) && !empty($contratto['data_conclusione']); // Contratto permette la pianificazione
$is_pianificato = false;
$stati_pianificabili = Stato::where('is_pianificabile', 1)->get();
$elenco_stati = $stati_pianificabili->implode('name', ', ');

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
    <table class="table table-bordered table-striped table-hover table-sm">
        <thead>
            <tr>
                <th width="10%">'.tr('Scadenza').'</th>
                <th>'.tr('Documento').'</th>
                <th class="text-center" width="15%">'.tr('Importo').'</th>
                <th class="text-center" width="25%">#</th>
            </tr>
        </thead>
        <tbody>';

    // Query per la select di collegamento ad una fattura esistente della stessa anagrafica
    $id_lang = Models\Locale::getDefault()->id;
    $query_fatture = "SELECT `co_documenti`.`id`, CONCAT('".tr('Fattura num.')." ', IF(`numero_esterno` != '', `numero_esterno`, `numero`), ' ".tr('del')." ', DATE_FORMAT(`data`, '%d/%m/%Y'), ' (', `co_stati_documento_lang`.`title`, ')') AS descrizione FROM `co_documenti` LEFT JOIN `co_stati_documento_lang` ON (`co_documenti`.`id_stato` = `co_stati_documento_lang`.`id_record` AND `co_stati_documento_lang`.`id_lang` = ".prepare($id_lang).") INNER JOIN `co_tipi_documento` ON `co_documenti`.`id_tipo_documento` = `co_tipi_documento`.`id` WHERE `co_tipi_documento`.`dir` = 'entrata' AND `co_documenti`.`id_anagrafica` = ".prepare($contratto->id_anagrafica)." ORDER BY `data` DESC";

    $previous = null;
    foreach ($pianificazioni as $pianificazione) {
        echo '
            <tr>
                <td>';

        // Data scadenza
        if ($previous === null || !$pianificazione->data_scadenza->equalTo($previous)) {
            $previous = $pianificazione->data_scadenza;
            echo '
                    <b>'.ucfirst((string) $pianificazione->data_scadenza->isoFormat('MMMM YYYY')).'</b>';
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
            ])).' (<i class="'.$fattura->stato->icona.'"></i> '.$fattura->stato->getTranslation('title').')';

            // Scollegamento della fattura dalla scadenza (la fattura non viene eliminata)
            echo '
                    <button type="button" class="btn btn-warning btn-sm ask unblockable tip pull-right" title="'.tr('Scollega la fattura da questa scadenza').'" data-id_plugin="'.$id_plugin.'" data-id_record="'.$id_record.'" data-id_module="'.$id_module.'" data-op="unlink_fattura" data-rata="'.$pianificazione->id.'" data-msg="'.tr('Scollegare la fattura da questa scadenza?').'" data-button="'.tr('Scollega fattura').'" data-backto="record-edit">
                        <i class="fa fa-chain-broken"></i>
                    </button>
                    <div class="clearfix"></div>';
        } else {
            // Collegamento ad una fattura esistente della stessa anagrafica
            echo '
                    <i class="fa fa-hourglass-start"></i> '.tr('Non ancora fatturato').'
                    <button type="button" class="btn btn-success btn-sm unblockable tip pull-right" title="'.tr('Collega la fattura selezionata a questa scadenza').'" onclick="collega_fattura('.$pianificazione->id.')">
                        <i class="fa fa-link"></i>
                    </button>
                    <div class="clearfix"></div>
                    <div style="margin-top:5px;">
                        {[ "type": "select", "name": "fattura_esistente_'.$pianificazione->id.'", "id": "fattura_esistente_'.$pianificazione->id.'", "placeholder": "'.tr('Collega fattura esistente...').'", "values": "query='.$query_fatture.'", "class": "unblockable" ]}
                    </div>';
        }
        echo '
                </td>

                <td class="text-right">
                    '.moneyFormat($pianificazione->totale_imponibile).'
                </td>';

        // Creazione fattura ed eliminazione riga di pianificazione
        echo '
                <td class="text-center">
                    <button type="button" class="btn btn-primary btn-sm '.(!empty($fattura) ? 'disabled' : '').'" '.(!empty($fattura) ? 'disabled' : '').' onclick="crea_fattura('.$pianificazione->id.')">
                        <i class="fa fa-euro"></i> '.tr('Crea fattura').'
                    </button>
                    <button type="button" class="btn btn-danger btn-sm ask '.(!empty($fattura) ? 'disabled' : '').'" '.(!empty($fattura) ? 'disabled' : '').' title="'.tr('Elimina la riga di pianificazione').'" data-id_plugin="'.$id_plugin.'" data-id_record="'.$id_record.'" data-id_module="'.$id_module.'" data-op="delete_pianificazione" data-id="'.$pianificazione->id.'" data-msg="'.tr('Eliminare la riga di pianificazione?').'" data-button="'.tr('Elimina riga').'" data-backto="record-edit">
                        <i class="fa fa-trash"></i>
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
        <i class="fa fa-info-circle"></i> '.tr('Nessuna pianificazione della fatturazione impostata per questo contratto').'.
    </div>

    <button type="button" '.(!empty($is_pianificabile) ? '' : 'disabled').' title="'.tr('Aggiungi una nuova pianificazione').'" data-widget="tooltip" class="btn btn-primary pull-right tip" id="pianifica">
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

        function collega_fattura(rata){
            var id_fattura = input("fattura_esistente_" + rata).get();
            if (!id_fattura) {
                alert("'.tr('Seleziona una fattura da collegare').'");
                return;
            }

            $.ajax({
                url: globals.rootdir + "/actions.php",
                type: "POST",
                data: {
                    id_module: '.$id_module.',
                    id_plugin: '.$id_plugin.',
                    id_record: '.$id_record.',
                    op: "link_fattura",
                    rata: rata,
                    id_fattura: id_fattura
                },
                success: function (response) {
                    renderMessages();
                    location.reload();
                },
                error: function() {
                    renderMessages();
                }
            });
        }

        // Riabilita le select/pulsanti di collegamento fattura anche quando il
        // contratto è in stato bloccato (block_edit), dato che la fatturazione
        // deve poter essere gestita comunque.
        $(document).ready(function() {
            $("select.unblockable, button.unblockable", "#tab_'.$id_plugin.'").prop("disabled", false).removeAttr("readonly").removeClass("disabled");
        });
    </script>';
