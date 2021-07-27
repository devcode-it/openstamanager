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

use Carbon\Carbon;
use Plugins\ListinoClienti\DettaglioPrezzo;

include_once __DIR__.'/../../core.php';

$id_articolo = $id_record;
echo '
<div class="box">
    <div class="box-header">
        <h3 class="box-title">'.tr('Informazioni specifiche per cliente').'</h3>
    </div>

    <div class="box-body">
        <div class="row">
            <div class="col-md-9">
                {[ "type": "select", "label": "'.tr('Cliente').'", "name": "id_cliente_informazioni",  "required":"1", "ajax-source": "clienti" ]}
            </div>

            <div class="col-md-3">
                <button type="button" class="btn btn-info btn-block" style="margin-top:25px;" onclick="aggiungiPrezzi(this)">
                    <i class="fa fa-money"></i> '.tr('Prezzi').'
                </button>
            </div>
        </div>
    </div>
</div>

<h4>'.tr('Elenco clienti').'</h4>';

$clienti = DettaglioPrezzo::where('id_articolo', $id_articolo)
    ->where('dir', 'entrata')
    ->get()
    ->groupBy('id_anagrafica');
if (!$clienti->isEmpty()) {
    echo '
<table class="table table-condensed table-bordered">
    <thead>
        <tr>
            <th>'.tr('Cliente').'</th>
            <th class="text-center" width="210">'.tr('Q.tà minima').'</th>
            <th class="text-center" width="210">'.tr('Q.tà massima').'</th>
            <th class="text-center" width="150">'.tr('Prezzo unitario').'</th>
            <th class="text-center" width="150">'.tr('Sconto').'</th>
            <th class="text-center" width="150">#</th>
        </tr>
    </thead>

    <tbody>';

    foreach ($clienti as $id_cliente => $prezzi) {
        $anagrafica = $prezzi->first()->anagrafica;

        echo '
        <tr data-id_anagrafica="'.$id_cliente.'" data-direzione="entrata">
            <td colspan="5">
                '.Modules::link('Anagrafiche', $anagrafica->id, $anagrafica->ragione_sociale).'
            </td>

            <td class="text-center">
                <button type="button" class="btn btn-xs btn-warning" onclick="modificaPrezzi(this)">
                    <i class="fa fa-money"></i>
                </button>
            </td>
        </tr>';

        foreach ($prezzi as $key => $dettaglio) {
            echo '
        <tr>
            <td></td>

            <td class="text-right">
                '.($dettaglio->minimo ? numberFormat($dettaglio->minimo) : '-').'
            </td>

            <td class="text-right">
                '.($dettaglio->massimo ? numberFormat($dettaglio->massimo) : '-').'
            </td>

            <td class="text-right">
                '.moneyFormat($dettaglio->prezzo_unitario).'
                <p><small class="label label-default tip" title="'.Translator::timestampToLocale($dettaglio['updated_at']).'"><i class="fa fa-clock-o"></i> '.Carbon::parse($dettaglio['updated_at'])->diffForHumans().'</small></p>
            </td>

            <td class="text-right">
                '.numberFormat($dettaglio->sconto_percentuale).'%
            </td>

            <td>';

            if (!isset($dettaglio->minimo) && !isset($dettaglio->massimo)) {
                echo '
                <span class="badge badge-primary">'.tr('Prezzo predefinito').'</span>';
            }

            echo '
            </td>
        </tr>';
        }
    }

    echo '
    </tbody>
</table>';
} else {
    echo '
<div class="alert alert-info">
    <i class="fa fa-info-circle"></i> '.tr('Nessuna informazione disponibile').'...
</div>';
}

echo '
<script>
function modificaPrezzi(button) {
    let tr = $(button).closest("tr");
    let id_anagrafica = tr.data("id_anagrafica");
    let direzione = tr.data("direzione");

    gestionePrezzi(id_anagrafica, direzione);
}

function gestionePrezzi(id_anagrafica, direzione) {
    openModal("'.tr('Gestisci prezzi specifici').'", "'.$structure->fileurl('dettaglio_prezzi.php').'?id_plugin='.$id_plugin.'&id_module='.$id_module.'&id_parent='.$id_record.'&id_articolo='.$id_record.'&id_anagrafica=" + id_anagrafica + "&direzione=" + direzione);
}

function aggiungiPrezzi(button) {
    let panel = $(button).closest(".box");
    let tab = panel.closest(".tab-pane");

    let direzione = tab.attr("id") === "fornitori" ? "uscita" : "entrata";
    let id_anagrafica = panel.find("select").val();

    if (id_anagrafica) {
        gestionePrezzi(id_anagrafica, direzione);
    } else {
        swal("'.tr('Attenzione').'", "'.tr('Inserire un\'anagrafica').'", "warning");
    }
}
</script>';
