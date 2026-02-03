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

use Modules\Articoli\Articolo;
use Modules\ListiniCliente\Articolo as ArticoloListino;

include_once __DIR__.'/../../../core.php';
include_once __DIR__.'/../../../../core.php';

$prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');

if (empty(get('id'))) {
    $articolo = Articolo::find(get('id_articolo'));
    $data_scadenza = null;
    $id_articolo = get('id_articolo');
    $prezzo_unitario = $prezzi_ivati ? $articolo->prezzo_vendita_ivato : $articolo->prezzo_vendita;
    $sconto_percentuale = 0;
    $dettagli = [];
} else {
    $articolo_listino = ArticoloListino::find(get('id'));
    $data_scadenza = $articolo_listino->data_scadenza;
    $id_articolo = $articolo_listino->id_articolo;
    $prezzo_unitario = $prezzi_ivati ? $articolo_listino->prezzo_unitario_ivato : $articolo_listino->prezzo_unitario;
    $sconto_percentuale = $articolo_listino->sconto_percentuale;
    $dettagli = ArticoloListino::dettagli($articolo_listino->id)->get();
}

echo '
<form id="add_form" action="'.base_path_osm().'/editor.php?id_module='.$id_module.'&id_record='.get('id_record').'" method="post">
    <input type="hidden" name="op" value="manage_articolo">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="id_articolo" value="'.get('id_articolo').'">
    <input type="hidden" name="id" value="'.get('id').'">

    <div class="row">
        <div class="col-md-12">
            {[ "type":"select", "label":"'.tr('Articolo').'", "name":"id_articolo", "ajax-source": "articoli", "select-options": {"permetti_movimento_a_zero": 1}, "value": "'.$id_articolo.'", "disabled": "1" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            {[ "type":"date", "label":"'.tr('Data scadenza').'", "name":"data_scadenza", "value":"'.$data_scadenza.'", "help": "'.tr('Se non valorizzata viene utilizzata la data di scadenza predefinita').'" ]}
        </div>

        <div class="col-md-4">
            {[ "type":"number", "label":"'.tr('Prezzo unitario').'", "name":"prezzo_unitario_fisso", "icon-after": "'.currency().'", "value":"'.$prezzo_unitario.'" ]}
        </div>

        <div class="col-md-4">
            {[ "type":"number", "label":"'.tr('Sconto percentuale').'", "name":"sconto_percentuale", "icon-after": "%", "value":"'.$sconto_percentuale.'" ]}
        </div>
    </div>

    <div class="card" id="prezzi">
        <div class="card-header">
            <h3 class="card-title">
                '.tr('Prezzi per quantità').'
            </h3>

             <button type="button" class="btn btn-xs btn-info pull-right" onclick="aggiungiPrezzo(this)">
                <i class="fa fa-plus"></i> '.tr('Aggiungi range').'
            </button>
        </div>

        <div class="card-body">
            <p>'.tr("Inserire i prezzi da associare all'articolo in relazione alla quantità").'.</p>
            <p>'.tr('Per impostare un prezzo generale per quantità non incluse in questi limiti, utilizzare il campo sopra indicato').'.</p>

            <table class="table table-sm">
                <thead>
                    <tr>
                        <th class="text-center">'.tr('Quantità minima').'</th>
                        <th class="text-center">'.tr('Quantità massima').'</th>
                        <th class="text-center tip" title="'.($prezzi_ivati ? tr('Importo IVA inclusa') : '').'">
                            '.tr('Prezzo unitario').($prezzi_ivati ? '<i class="fa fa-question-circle-o"></i>' : '').'
                        </th>
                        <th class="text-center">'.tr('Sconto').'</th>
                        <th>#</th>
                    </tr>
                </thead>

                <tbody>';

foreach ($dettagli as $key => $dettaglio) {
    echo '
                    <tr>
                        <td>
                        <input type="hidden" name="dettaglio['.$key.']" value="'.$dettaglio->id.'">
                           {[ "type": "number", "name": "minimo['.$key.']", "min-value": 0, "value": "'.$dettaglio->minimo.'" ]}
                        </td>

                        <td>
                           {[ "type": "number", "name": "massimo['.$key.']", "min-value": 0, "value": "'.$dettaglio->massimo.'" ]}
                        </td>

                        <td>
                           {[ "type": "number", "name": "prezzo_unitario['.$key.']", "icon-after": "'.currency().'", "value": "'.($prezzi_ivati ? $dettaglio->prezzo_unitario_ivato : $dettaglio->prezzo_unitario).'" ]}
                        </td>
                        <td>
                           {[ "type": "number", "name": "sconto['.$key.']", "min-value": 0, "value": "'.$dettaglio->sconto_percentuale.'", "icon-after":"%" ]}
                        </td>
                        <td>
                            <button type="button" class="btn btn-xs btn-danger" onclick="rimuoviPrezzo(this)">
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
                    </tr>';
}

echo '
                </tbody>
            </table>
        </div>
    </div>

    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-success">
                <i class="fa fa-check"></i> '.tr('Salva').'
            </button>
		</div>
	</div>
</form>';
?>

<table class="hide">
    <tbody id="prezzi-template">
        <tr>
            <td>
               {[ "type": "number", "name": "minimo[-id-]", "min-value": 0 ]}
            </td>

            <td>
               {[ "type": "number", "name": "massimo[-id-]", "min-value": 0 ]}
            </td>

            <td>
               {[ "type": "number", "name": "prezzo_unitario[-id-]", "icon-after": "<?php echo currency(); ?>" ]}
            </td>

            <td>
               {[ "type": "number", "name": "sconto[-id-]", "min-value": 0, "icon-after": "%" ]}
            </td>

            <td>
                <button type="button" class="btn btn-xs btn-danger" onclick="rimuoviPrezzo(this)">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
    </tbody>
</table>

<script>
    $(document).ready(function(){
        init();
    });
    content_was_modified = false;

    var key = <?php echo count($dettagli); ?>;
    function aggiungiPrezzo(button) {
        cleanup_inputs();

        let text = replaceAll($("#prezzi-template").html(), "-id-", "" + key);
        key++;

        let body = $(button).closest(".card").find("table > tbody");
        let lastRow = body.find("tr").last();
        if (lastRow.length) {
            lastRow.after(text);
        } else {
            body.html(text);
        }

        restart_inputs();
    }

    function rimuoviPrezzo(button) {
        $(button).closest("tr").remove();
    }
</script>
