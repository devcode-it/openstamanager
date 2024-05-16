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

use Models\Module;
use Plugins\ListinoClienti\DettaglioPrezzo;
use Plugins\ListinoFornitori\DettaglioFornitore;

include_once __DIR__.'/../../core.php';

$id_articolo = $id_record;
echo '
<div class="card">
    <div class="card-header">
        <h3 class="card-title">'.tr('Informazioni specifiche per fornitore').'</h3>
    </div>

    <div class="card-body">
        <div class="row">
            <div class="col-md-9">
                {[ "type": "select", "label": "'.tr('Fornitore').'", "name": "id_fornitore_informazioni", "required":"1", "ajax-source": "fornitori","icon-after": "add|'.(new Module())->getByField('title', 'Anagrafiche', Models\Locale::getPredefined()->id).'|tipoanagrafica=Fornitore&readonly_tipo=1" ]}
            </div>

            <div class="col-md-3">
                <div class="btn-group btn-group-flex">
                    <button type="button" class="btn btn-info" style="margin-top:25px;" onclick="aggiungiFornitorePrezzi(this)">
                        <i class="fa fa-money"></i> '.tr('Aggiungi prezzi e fornitore').'
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<h4>'.tr('Elenco fornitori').'</h4>';

$dettagli_fornitori = DettaglioFornitore::where('id_articolo', $id_record)->get()
    ->mapToGroups(fn ($item, $key) => [$item->id_fornitore => $item]);
$prezzi_fornitori = DettaglioPrezzo::where('id_articolo', $id_articolo)
    ->where('dir', 'uscita')
    ->get()
    ->groupBy('id_anagrafica');

$fornitori_disponibili = $dettagli_fornitori->keys()
    ->merge($prezzi_fornitori->keys())
    ->unique();

if (!$fornitori_disponibili->isEmpty()) {
    echo '
<table class="table table-striped table-condensed table-bordered">
    <tbody>';

    foreach ($fornitori_disponibili as $id_fornitore) {
        $dettaglio = $dettagli_fornitori[$id_fornitore] ? $dettagli_fornitori[$id_fornitore]->first() : null;
        $prezzi = $prezzi_fornitori[$id_fornitore];

        $anagrafica = $dettaglio ? $dettaglio->anagrafica : $prezzi->first()->anagrafica;

        echo '
        <tr>
            <th>'.tr('Fornitore').'</th>
            <th>'.tr('Descrizione').'</th>
            <th width="150">'.tr('Codice').'</th>
            <th width="150">'.tr('Barcode').'</th>
            <th class="text-center" width="210">'.tr('Q.tà minima ordinabile').'</th>
            <th class="text-center" width="150">'.tr('Tempi di consegna').'</th>
            <th class="text-center" width="150">#</th>
        </tr>

        <tr data-id_anagrafica="'.$anagrafica->id.'" data-direzione="uscita" '.(($anagrafica->id == $articolo->id_fornitore) ? 'class="success"' : '').'>
            <td>
                '.Modules::link('Anagrafiche', $anagrafica->id, $anagrafica->ragione_sociale).'
            </td>';

        if (!empty($dettaglio)) {
            echo '
            <td>
                '.$dettaglio['descrizione'].'
            </td>
            
            <td class="text-center">
                '.$dettaglio['codice_fornitore'].'
            </td>

            <td class="text-center">
                '.$dettaglio['barcode_fornitore'].'
            </td>

            <td class="text-right">
                '.numberFormat($dettaglio['qta_minima']).' '.$articolo->um.'
            </td>

            <td class="text-right">
                '.tr('_NUM_ gg', [
                '_NUM_' => numberFormat($dettaglio['giorni_consegna'], 0),
            ]).'
            </td>';
        } else {
            echo '
            <td>-</td>
            <td class="text-center">-</td>
            <td class="text-center">-</td>
            <td class="text-right">-</td>
            <td class="text-right">-</td>';
        }

        echo '
            <td class="text-center">
                <button type="button" class="btn btn-xs btn-warning" onclick="modificaPrezzi(this)">
                    <i class="fa fa-money"></i>
                </button>';

        if (!empty($dettaglio)) {
            echo '

                <a class="btn btn-secondary btn-xs btn-warning" onclick="modificaFornitore('.$dettaglio['id'].', '.$anagrafica->id.')">
                    <i class="fa fa-edit"></i>
                </a>

                <a class="btn btn-secondary btn-xs btn-danger ask" data-op="delete_fornitore" data-id_riga="'.$dettaglio['id'].'" data-id_plugin="'.$id_plugin.'" data-backto="record-edit">
                    <i class="fa fa-trash-o"></i>
                </a>';
        }

        echo '
            </td>
        </tr>';

        /*
        $dettaglio_predefinito = $prezzi->whereStrict('minimo', null)
            ->whereStrict('massimo', null)
            ->first();

        $prezzi = $prezzi->reject(function ($item, $key) use ($dettaglio_predefinito) {
            return $item->id == $dettaglio_predefinito->id;
        });
        */
        if (!empty($prezzi) && !$prezzi->isEmpty()) {
            echo '
        <tr>
            <td colspan="2"></td>
            <th class="text-center">'.tr('Q.tà minima').'</th>
            <th class="text-center">'.tr('Q.tà massima').'</th>
            <th class="text-center">'.tr('Prezzo unitario').'</th>
            <th class="text-center">'.tr('Sconto').'</th>
            <td></td>
        </tr>';

            foreach ($prezzi as $key => $dettaglio) {
                echo '
        <tr '.(($anagrafica->id == $articolo->id_fornitore) ? 'class="success"' : '').'>
            <td colspan="2"></td>

            <td class="text-right">
                '.($dettaglio->minimo ? numberFormat($dettaglio->minimo) : '-').'
            </td>

            <td class="text-right">
                '.($dettaglio->massimo ? numberFormat($dettaglio->massimo) : '-').'
            </td>

            <td class="text-right">
                '.moneyFormat($dettaglio->prezzo_unitario).'
                <p><span class="right badge badge-default tip" title="'.Translator::timestampToLocale($dettaglio['updated_at']).'"><i class="fa fa-clock-o"></i> '.$dettaglio->updated_at->diffForHumans().'</small></p>
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
function modificaFornitore(id_riga, id_anagrafica) {
    openModal("Modifica dati fornitore", "'.$structure->fileurl('dettaglio_fornitore.php').'?id_plugin='.$id_plugin.'&id_module='.$id_module.'&id_parent='.$id_record.'&id_articolo='.$id_record.'&id_riga=" + id_riga + "&id_anagrafica=" + id_anagrafica);
}

function aggiungiFornitore() {
    let id_fornitore = $("#id_fornitore_informazioni").val();
    if (id_fornitore) {
        modificaFornitore("", id_fornitore);
    } else {
        swal("'.tr('Attenzione').'", "'.tr('Inserire un\'anagrafica').'", "warning");
    }
}

function modificaFornitorePrezzi(id_anagrafica, direzione) {
    openModal("'.tr('Gestisci prezzi specifici fornitore').'", "'.$structure->fileurl('dettaglio_prezzi_fornitore.php').'?id_plugin='.$id_plugin.'&id_module='.$id_module.'&id_parent='.$id_record.'&id_articolo='.$id_record.'&id_anagrafica=" + id_anagrafica + "&direzione=" + direzione);
}

function aggiungiFornitorePrezzi(button) {
    let card = $(button).closest(".box");

    let direzione = "uscita"
    let id_anagrafica = card.find("select").val();

    if (id_anagrafica) {
        modificaFornitorePrezzi(id_anagrafica, direzione);
    } else {
        swal("'.tr('Attenzione').'", "'.tr('Inserire un\'anagrafica').'", "warning");
    }
}
</script>';
