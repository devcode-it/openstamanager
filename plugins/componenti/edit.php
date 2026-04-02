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

$totali = [
    'installati' => $componenti_installati->count(),
    'sostituiti' => $componenti_sostituiti->count(),
    'rimossi' => $componenti_rimossi->count(),
    'totali' => $compontenti_impianto->count(),
];

$con_allegati = 0;
$senza_allegati = 0;
foreach ($componenti_installati as $c) {
    $num = $database->fetchNum('SELECT id FROM zz_files WHERE id_plugin='.prepare($id_plugin).' AND id_record='.$c['id']);
    if ($num > 0) {
        $con_allegati++;
    } else {
        $senza_allegati++;
    }
}

// Avviso sul numero di componenti
if ($totali['totali'] == 0) {
    echo '
<div class="alert alert-info">
    <i class="fa fa-info-circle"></i> '.tr("Nessun componente disponibile per l'impianto corrente").'
</div>';
} else {
    echo '
<div class="row">
    <div class="col-md-3 col-sm-6">
        <div class="info-box bg-success">
            <span class="info-box-icon"><i class="fa fa-cog"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">'.tr('Installati').'</span>
                <span class="info-box-number">'.$totali['installati'].'</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="info-box bg-warning">
            <span class="info-box-icon"><i class="fa fa-refresh"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">'.tr('Sostituiti').'</span>
                <span class="info-box-number">'.$totali['sostituiti'].'</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="info-box bg-danger">
            <span class="info-box-icon"><i class="fa fa-trash"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">'.tr('Rimossi').'</span>
                <span class="info-box-number">'.$totali['rimossi'].'</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="info-box bg-info">
            <span class="info-box-icon"><i class="fa fa-file-text-o"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">'.tr('Con allegati').'</span>
                <span class="info-box-number">'.$con_allegati.' / '.$totali['installati'].'</span>
            </div>
        </div>
    </div>
</div>';
}

$elenchi = [
    [
        'componenti' => $componenti_installati,
        'type' => 'success',
        'icon' => 'fa fa-check-circle',
        'title' => tr('Componenti installati'),
        'date' => 'data_installazione',
        'date_name' => tr('Installato'),
    ],
    [
        'componenti' => $componenti_sostituiti,
        'type' => 'warning',
        'icon' => 'fa fa-refresh',
        'title' => tr('Componenti sostituiti'),
        'date' => 'data_sostituzione',
        'date_name' => tr('Sostituzione'),
    ],
    [
        'componenti' => $componenti_rimossi,
        'type' => 'danger',
        'icon' => 'fa fa-times-circle',
        'title' => tr('Componenti rimossi'),
        'date' => 'data_rimozione',
        'date_name' => tr('Rimosso'),
    ],
];

$plugin = Plugin::where('name', 'Componenti')->first();
$module = $plugin->module;

// Generazione elenchi HTML
foreach ($elenchi as $elenco) {
    $componenti = $elenco['componenti'];
    $type = $elenco['type'];
    $title = $elenco['title'];
    $date = $elenco['date'];
    $date_name = $elenco['date_name'];

    if (empty($componenti) || $componenti->isEmpty()) {
        continue;
    }

    $icon = $elenco['icon'] ?? 'fa fa-cog';
    $count = $componenti->count();

    echo '
<div class="card card-'.$type.'" style="margin-bottom: 15px;">
    <div class="card-header" style="padding: 8px 15px;">
        <div class="row">
            <div class="col-md-6">
                <h5 style="margin: 0; font-size: 15px;">
                    <i class="'.$icon.'"></i> '.$title.'
                </h5>
            </div>
            <div class="col-md-6 text-right">
                <span class="badge" style="font-size: 13px; padding: 4px 8px;">'.$count.' '.($count == 1 ? tr('componente') : tr('componenti')).'</span>
            </div>
        </div>
    </div>
    
    <div class="card-body" style="padding: 0;">
        <table class="table table-striped table-hover table-condensed" style="margin: 0;">
            <thead>
                <tr style="background-color: #f5f5f5;">
                    <th class="text-center" width="5%">'.tr('ID', [], ['upper' => true]).'</th>
                    <th class="text-left" width="35%">'.tr('Articolo', [], ['upper' => true]).'</th>
                    <th class="text-center" width="15%">'.tr($date_name, [], ['upper' => true]).'</th>
                    <th class="text-center" width="15%">'.tr('Registrazione', [], ['upper' => true]).'</th>
                    <th class="text-center" width="10%">'.tr('Allegati', [], ['upper' => true]).'</th>
                    <th class="text-center" width="20%">'.tr('Azioni', [], ['upper' => true]).'</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($componenti as $componente) {
        $articolo = $componente->articolo;
        $numero_allegati = $database->fetchNum('SELECT id FROM zz_files WHERE id_plugin='.prepare($id_plugin).' AND id_record='.$componente['id']);

        $data = dateFormat($componente[$date]);
        $icona_allegati = $numero_allegati == 0 ? 'fa fa-times-circle text-danger' : 'fa fa-check-circle text-success';
        $badge_allegati = $numero_allegati > 0 ? '<span class="badge badge-success">'.$numero_allegati.'</span>' : '<span class="badge badge-default">0</span>';

        $serial_badge = '';
        if (! empty($articolo->abilita_serial)) {
            if (! empty($componente->serial)) {
                $serial_badge = ' <span class="label label-primary"><i class="fa fa-barcode"></i> '.$componente->serial.'</span>';
            } else {
                $serial_badge = ' <span class="label label-warning"><i class="fa fa-exclamation-triangle"></i> '.tr('Nessun seriale selezionato').'</span>';
            }
        }

        $categoria = $articolo->categoria ? $articolo->categoria->getTranslation('title') : '';
        $categoria_badge = $categoria ? '<span class="label label-default"><i class="fa fa-tag"></i> '.$categoria.'</span>' : '';

        echo '
                <tr class="riga-componente" data-id="'.$componente->id.'">
                    <td class="text-center"><strong>#'.$componente->id.'</strong></td>
                    <td>
                        <div>
                            <strong>'.$articolo->codice.'</strong> - '.$articolo->getTranslation('title').'
                        </div>
                        <div class="text-muted small">'.$categoria_badge.' '.$serial_badge.'</div>
                    </td>
                    <td class="text-center">
                        <span class="label label-'.$type.'">'.$data.'</span>
                    </td>
                    <td class="text-center">'.dateFormat($componente->data_registrazione).'</td>
                    <td class="text-center">'.$badge_allegati.'</td>
                    <td class="text-center">
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-default" onclick="toggleDettagli(this)" title="'.tr('Dettagli').'">
                                <i class="fa fa-eye"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-info" onclick="gestisciAllegati(this)" title="'.tr('Allegati').'">
                                <i class="fa fa-paperclip"></i>
                            </button>';

        if (empty($componente->id_sostituzione) && empty($componente->data_rimozione)) {
            echo '
                            <button type="button" class="btn btn-sm btn-warning" onclick="sostituisciComponente(this)" title="'.tr('Sostituisci').'">
                                <i class="fa fa-refresh"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="rimuoviComponente(this)" title="'.tr('Rimuovi').'">
                                <i class="fa fa-trash"></i>
                            </button>';
        }

        echo '
                        </div>
                    </td>
                </tr>

                <tr class="dettagli-componente" data-id="'.$componente->id.'" style="display: none">
                    <td colspan="6" style="background-color: #f9f9f9; padding: 15px;">
                        <div class="well" style="margin: 0;">
                            <form action="'.base_path_osm().'/editor.php" method="post" role="form">
                                <input type="hidden" name="id_module" value="'.$module->id.'">
                                <input type="hidden" name="id_record" value="'.$componente->id_impianto.'">
                                <input type="hidden" name="id_plugin" value="'.$plugin->id.'">
                                <input type="hidden" name="id_componente" value="'.$componente->id.'">
                                <input type="hidden" name="backto" value="record-edit">
                                <input type="hidden" name="op" value="update">

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="row">
                                            <div class="col-md-12">
                                                {[ "type": "select", "label": "'.tr('Articolo').'", "name": "id_articolo", "id": "id_articolo_'.$componente->id.'", "disabled": "1", "value": "'.$componente->id_articolo.'", "ajax-source": "articoli", "select-options": {"permetti_movimento_a_zero": 1} ]}
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                {[ "type": "select", "label": "'.tr('Serial').'", "name": "serial", "id": "serial_'.$componente->id.'", "disabled": "'.(empty($articolo->abilita_serial) ? '1' : '0').'", "value": "'.$componente->serial.'", "ajax-source": "serial-articolo", "select-options": '.json_encode(['idarticolo' => $componente->id_articolo]).' ]}
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                {[ "type": "date", "label": "'.tr('Data installazione').'", "name": "data_installazione", "id": "data_installazione_'.$componente->id.'", "value": "'.$componente['data_installazione'].'" ]}
                                            </div>
                                            <div class="col-md-4">
                                                {[ "type": "date", "label": "'.tr('Data registrazione').'", "name": "data_registrazione", "id": "data_registrazione_'.$componente->id.'", "value": "'.$componente['data_registrazione'].'" ]}
                                            </div>
                                            <div class="col-md-4">
                                                {[ "type": "date", "label": "'.tr('Data rimozione').'", "name": "data_rimozione", "id": "data_rimozione_'.$componente->id.'", "value": "'.$componente['data_rimozione'].'" ]}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">';

        echo input([
            'type' => 'ckeditor',
            'label' => tr('Note'),
            'name' => 'note',
            'id' => 'note_'.$componente->id,
            'value' => $componente['note'],
            'extra' => 'style="height: 50px;"',
        ]);

        echo '
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12 text-right">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fa fa-check"></i> '.tr('Salva').'
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </td>
                </tr>';
    }

    echo '
            </tbody>
        </table>
    </div>
</div>
<br>';
}

echo '
<script>
    function toggleDettagli(trigger) {
        const tr = $(trigger).closest("tr");
        const dettagli = tr.next();
        const icon = $(trigger).find("i");

        if (dettagli.css("display") === "none"){
            dettagli.show(500);
            icon.removeClass("fa-eye").addClass("fa-eye-slash");
        } else {
            dettagli.hide(500);
            icon.removeClass("fa-eye-slash").addClass("fa-eye");
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

        swal({
            title: "'.tr('Vuoi sostituire questo componente?').'",
            type: "warning",
            showCancelButton: true,
            confirmButtonText: "'.tr('Sì').'"
        }).then(function () {
            redirect_url("'.base_path_osm().'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'&op=sostituisci&backto=record-edit&id_plugin='.$id_plugin.'&id_componente=" + id_componente + "&hash=tab_'.$structure->id.'");
        }).catch(swal.noop);
            
    }

    function rimuoviComponente(trigger) {
        const tr = $(trigger).closest("tr");
        const id_componente = tr.data("id");

        swal({
            title: "'.tr('Vuoi eliminare questo componente?').'",
            type: "warning",
            showCancelButton: true,
            confirmButtonText: "'.tr('Sì').'"
        }).then(function () {
            redirect_url("'.base_path_osm().'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'&op=rimuovi&backto=record-edit&id_plugin='.$id_plugin.'&id_componente=" + id_componente + "&hash=tab_'.$structure->id.'");
        }).catch(swal.noop);
    }

</script>';
