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

use Models\Plugin;
use Plugins\ComponentiImpianti\Componente;

include_once __DIR__.'/../../core.php';

$compontenti_impianto = Componente::where('id_impianto', '=', $id_record);

// Avviso sul numero di componenti
if ($compontenti_impianto->count() == 0){
    echo '
<div class="alert alert-info">
    <i class="fa fa-info-circle"></i> '.tr("Nessun componente disponibile per l'impianto corrente").'
</div>';
}

$componenti_installati = (clone $compontenti_impianto)
    ->whereNull('data_sostituzione')
    ->whereNull('data_rimozione')
    ->get();
$componenti_sostituiti = (clone $compontenti_impianto)
    ->whereNotNull('data_sostituzione')
    ->get();
$componenti_rimossi = (clone $compontenti_impianto)
    ->whereNotNull('data_rimozione')
    ->get();

$elenchi = [
    [
        'componenti' => $componenti_installati,
        'type' => 'primary',
        'title' => tr('Componenti installati'),
        'date' => 'data_installazione',
        'date_name' => tr('Installato'),
    ],
    [
        'componenti' => $componenti_sostituiti,
        'type' => 'warning',
        'title' => tr('Componenti sostituiti'),
        'date' => 'data_sostituzione',
        'date_name' => tr('Sostituzione'),
    ],
    [
        'componenti' => $componenti_rimossi,
        'type' => 'danger',
        'title' => tr('Componenti rimossi'),
        'date' => 'data_rimozione',
        'date_name' => tr('Rimosso'),
    ]
];

$plugin = Plugin::pool('Componenti');
$module = $plugin->module;

// Generazione elenchi HTML
foreach($elenchi as $elenco){
    $componenti = $elenco['componenti'];
    $type = $elenco['type'];
    $title = $elenco['title'];
    $date = $elenco['date'];
    $date_name = $elenco['date_name'];

    if (empty($componenti) || $componenti->isEmpty()) {
        continue;
    }

    echo '
<div class="row">
    <div class="col-md-12 text-center">
        <h4 class="text-'.$type.'">'.$title.'</h4>
    </div>
</div>
<hr>';

    echo '
<div class="box collapsed-box box-'.$type.'">
    <div class="box-header with-border mini">
        <table class="table" style="margin:0; padding:0;">
            <thead>
                <tr>
                    <th class="text-center">'.tr('ID', [], ['upper' => true]).'</td>
                    <th class="text-center">'.tr('Articolo', [], ['upper' => true]).'</td>
                    <th class="text-center" width="20%">'.tr($date_name, [], ['upper' => true]).'</th>
                    <th class="text-center" width="20%">'.tr('Registrazione', [], ['upper' => true]).'</th>
                    <th class="text-center" width="10%">'.tr('Allegati', [], ['upper' => true]).'</th>
                </tr>
            </thead>

            <tbody>';

    foreach ($componenti as $componente) {
        $articolo = $componente->articolo;
        $numero_allegati = $database->fetchNum('SELECT id FROM zz_files WHERE id_plugin='.prepare($id_plugin).' AND id_record='.$componente['id'].' GROUP BY id_record');

        $data = dateFormat($componente[$date]);
        $icona_allegati = $numero_allegati == 0 ? 'fa fa-times text-danger' : 'fa fa-check text-success';

        echo '
                <tr class="riga-componente" data-id="'.$componente->id.'">
                    <td class="text-center">#'.$componente->id.'</td>
                    <td class="text-center">'.$articolo->codice.' - '.$articolo->descrizione.'</td>
                    <td class="text-center">'.$data.'</td>
                    <td class="text-center">'.dateFormat($componente->data_registrazione).'</td>
                    <td class="text-center">
                        <i class="'.$icona_allegati.' fa-lg"></i>

                        <div class="box-tools pull-right">
                            <button type="button" class="btn btn-box-tool" onclick="toggleDettagli(this)">
                                <i class="fa fa-plus"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                <tr class="dettagli-componente" data-id="'.$componente->id.'" style="display: none">
                    <td colspan="5">
                        <div class="panel panel-'.$type.'">
                            <div class="panel-heading">
                                <h3 class="panel-title">'.tr('Dati').'</h3>
                            </div>

                            <div class="panel-body">
                                <form action="'.base_path().'/editor.php" method="post" role="form">
                                    <input type="hidden" name="id_module" value="'.$module->id.'">
                                    <input type="hidden" name="id_record" value="'.$componente->id_impianto.'">
                                    <input type="hidden" name="id_plugin" value="'.$plugin->id.'">

                                    <input type="hidden" name="id_componente" value="'.$componente->id.'">
                                    <input type="hidden" name="backto" value="record-edit">
                                    <input type="hidden" name="op" value="update">

                                    <div class="row">
                                        <div class="col-md-6">
                                            {[ "type": "select", "label": "'.tr('Articolo').'", "name": "id_articolo", "id": "id_articolo_'.$componente->id.'", "disabled": "1", "value": "'.$componente->id_articolo.'", "ajax-source": "articoli", "select-options": {"permetti_movimento_a_zero": 1} ]}
                                        </div>

                                        <div class="col-md-2">
                                            {[ "type": "date", "label": "'.tr('Data registrazione').'", "name": "data_registrazione", "id": "data_registrazione_'.$componente->id.'", "value": "'.$componente['data_registrazione'].'" ]}
                                        </div>

                                        <div class="col-md-2">
                                            {[ "type": "date", "label": "'.tr('Data installazione').'", "name": "data_installazione", "id": "data_installazione_'.$componente->id.'", "value": "'.$componente['data_installazione'].'" ]}
                                        </div>

                                        <div class="col-md-2">
                                            {[ "type": "date", "label": "'.tr('Data rimozione').'", "name": "data_rimozione", "id": "data_rimozione_'.$componente->id.'", "value": "'.$componente['data_rimozione'].'" ]}
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            {[ "type": "ckeditor", "label": "'.tr('Note').'", "name": "note", "id": "note_'.$componente->id.'", "value": "'.$componente['note'].'" ]}
                                        </div>
                                    </div>

                                    <!-- PULSANTI -->
                                    <div class="row">';
        if (empty($componente->id_sostituzione)) {
            echo '
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-danger" onclick="rimuoviComponente(this)">
                                                <i class="fa fa-trash"></i> '.tr('Rimuovi').'
                                            </button>
                                        </div>';
        }

        echo '
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-default" onclick="gestisciAllegati(this)">
                                                <i class="fa fa-file-text-o"></i> '.tr('Allegati (_NUM_)', [
                                                    '_NUM_' => $numero_allegati,
                                                ]).'
                                            </button>
                                        </div>';

        if (empty($componente->id_sostituzione)) {
            echo '
                                        <div class="col-md-9">
                                            <button type="button" class="btn btn-warning pull-right" onclick="sostituisciComponente(this)">
                                                <i class="fa fa-cog"></i> '.tr('Sostituisci').'
                                            </button>
                                        </div>';
        }
        echo '
                                        <div class="col-md-1 pull-right">
                                            <button type="submit" class="btn btn-success pull-right">
                                                <i class="fa fa-check"></i> '.tr('Salva').'
                                            </button>
                                        </div>
                                    </div>
                                </form>

                            </div>
                        </div>
                    </td>
                </tr>';
    }

    echo '
            </tbody>
        </table>

    </div>
</div>';
}

echo '
<script>
    function toggleDettagli(trigger) {
        const tr = $(trigger).closest("tr");
        const dettagli = tr.next();

        if (dettagli.css("display") === "none"){
            dettagli.show(500);
        } else {
            dettagli.hide(500);
        }
    }

    function gestisciAllegati(trigger) {
        const tr = $(trigger).closest("tr");
        const id_componente = tr.data("id");

        openModal(\'Aggiungi file\', \''.$structure->fileurl('allegati.php').'?id_module='.$id_module.'&id_plugin='.$id_plugin.'id_record='.$id_record.'&id=\' + id_componente);
    }

    function sostituisciComponente(trigger) {
        const tr = $(trigger).closest("tr");
        const id_componente = tr.data("id");

        if(confirm("'.tr('Vuoi sostituire questo componente?').'")) {
            redirect("'.base_path().'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'&op=sostituisci&backto=record-edit&id_plugin='.$id_plugin.'&id_componente=" + id_componente + "&hash=tab_'.$structure->id.'");
        }
    }

    function rimuoviComponente(trigger) {
        const tr = $(trigger).closest("tr");
        const id_componente = tr.data("id");

        if(confirm("'.tr('Vuoi eliminare questo componente?').'")){
            redirect("'.base_path().'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'&op=delete&backto=record-edit&id_plugin='.$id_plugin.'&id=" + id_componente + "&hash=tab_'.$structure->id.'");
        }
    }

</script>';
