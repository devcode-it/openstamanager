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

echo '<hr>';

$componenti = $dbo->fetchArray('SELECT my_componenti_articoli.*, my_impianti.idanagrafica, CONCAT(a.codice, " - ", a.descrizione) AS art_sostituito, CONCAT(b.codice, " - ", b.descrizione)  AS art_installato, a.codice FROM my_componenti_articoli LEFT JOIN my_impianti ON my_componenti_articoli.id_impianto=my_impianti.id LEFT JOIN mg_articoli AS a ON my_componenti_articoli.pre_id_articolo=a.id LEFT JOIN mg_articoli AS b ON my_componenti_articoli.id_articolo=b.id WHERE id_impianto='.prepare($id_record).' ORDER BY data_registrazione, id_articolo DESC');

$installati = 0;
$disinstallati = 0;

foreach ($componenti as $componente) {
    if (!empty($componente['pre_id_articolo'])) {
        $id_articolo = $componente['pre_id_articolo'];
        $check_value = 1;
        $box = 'danger';
        $articolo = $componente['art_sostituito'];
        $data = dateFormat($componente['data_disinstallazione']);
        $text = 'DISINSTALLATO';
        $class = 'danger';
        $title = ''.tr('Storico').'';
        $table = 'default';
        if ($disinstallati == 0) {
            echo '
            <div class="row">
                <div class="col-md-12 text-center">
                    <h4 class="text-danger">ARTICOLI DISINSTALLATI</h4>
                </div>
            </div>
            <hr>';
            ++$disinstallati;
        }
    } else {
        $id_articolo = $componente['id_articolo'];
        $check_value = 0;
        $box = 'primary';
        $articolo = $componente['art_installato'];
        $data = dateFormat($componente['data_installazione']);
        $text = 'INSTALLATO';
        $class = 'primary';
        $title = ''.tr('Dati').'';
        $table = 'primary';
        if ($installati == 0) {
            echo '
            <div class="row">
                <div class="col-md-12 text-center">
                    <h4 class="text-blue">ARTICOLI INSTALLATI</h4>
                </div>
            </div>
            <hr>';
            ++$installati;
        }
    }

    $allegati = $dbo->fetchOne('SELECT COUNT(id) AS num FROM zz_files WHERE id_plugin='.prepare($id_plugin).' AND id_record='.$componente['id'].' GROUP BY id_record')['num'];

    if ($allegati) {
        $icon = 'fa fa-check text-success';
    } else {
        $icon = 'fa fa-times text-danger';
    }

    echo '
    <form action="'.base_path().'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'" method="post" role="form">
        <input type="hidden" name="id_plugin" value="'.$id_plugin.'">
        <input type="hidden" name="id_record" value="'.$id_record.'">
        <input type="hidden" name="sostituito" value="'.$check_value.'">
        <input type="hidden" name="backto" value="record-edit">
        <input type="hidden" name="op" value="update">


        <div class="panel-group" id="accordion">
            <div class="box collapsed-box box-'.$box.'">
                <div class="box-header with-border mini">
                    <small class="text-'.$class.'">
                        <table class="table" style="margin:0; padding:0;">
                            <thead>
                                <tr>
                                    <th class="text-center">'.tr('ARTICOLO').'</td>
                                    <th class="text-center" width="20%">'.$text.'</th>
                                    <th class="text-center" width="20%">'.tr('REGISTRAZIONE').'</th> 
                                    <th class="text-center" width="10%">'.tr('ALLEGATI').'</th>
                                </tr>
                                <tr>
                                    <td class="text-center">'.$articolo.'</td>
                                    <td class="text-center">'.$data.'</td>
                                    <td class="text-center">'.dateFormat($componente['data_registrazione']).'</td>
                                    <td class="text-center"><i class="'.$icon.' fa-lg"></i></td>
                                </tr>
                            </thead>
                        </table>
                    </small>

                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse">
                            <i class="fa fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div id="collapse_'.$j.'" class="box-body">
                    <div class="panel panel-'.$table.'">
                        <div class="panel-heading">
                            <h3 class="panel-title">'.$title.'</h3>
                        </div>
                    

                        <div class="panel-body">
                        
                            <div class="row">
                                <div class="col-md-6">
                                    {[ "type":"select","label":"'.tr('Articolo').'","name":"id_articolo['.$componente['id'].']", "required":"1","value":"'.$id_articolo.'", "ajax-source": "articoli", "select-options": {"permetti_movimento_a_zero": 1} ]}
                                </div>
                                <div class="col-md-2">
                                    {[ "type":"date","label":"'.tr('Data registrazione').'","name":"data_registrazione['.$componente['id'].']", "value":"'.$componente['data_registrazione'].'" ]}
                                </div>
                                <div class="col-md-2">
                                    {[ "type":"date","label":"'.tr('Data installazione').'","name":"data_installazione['.$componente['id'].']", "value":"'.$componente['data_installazione'].'" ]}
                                </div>

                                <div class="col-md-2">
                                    {[ "type":"date","label":"'.tr('Data disinstallazione').'","name":"data_disinstallazione['.$componente['id'].']", "value":"'.$componente['data_disinstallazione'].'" ]}
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    {[ "type":"textarea","label":"'.tr('Note').'","name":"note['.$componente['id'].']", "value":"'.$componente['note'].'" ]}
                                </div>
                            </div>

                            <!-- PULSANTI -->
                            <div class="row">
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-danger" onclick="elimina('.$componente['id'].')"><i class="fa fa-trash"></i> '.tr('Elimina').'</button>
                                </div>
                                <div class="col-md-1">
                                    <a onclick="openModal(\'Aggiungi file\', \''.$structure->fileurl('allegati.php').'?id_module='.$id_module.'&id_plugin='.$id_plugin.'id_record='.$id_record.'&id='.$componente['id'].'\');" class="btn btn-default">
                                            <i class="fa fa-file-text-o"></i> '.tr('Allegati _NUM_', [
                                                '_NUM_' => $allegati,
                                            ]).'
                                    </a>
                                </div>';

    if (!empty($componente['id_articolo'])) {
        echo '
                                <div class="col-md-9">
                                    <button type="button" class="btn btn-warning pull-right" onclick="sostituisci('.$componente['id'].')"><i class="fa fa-cog"></i> '.tr('Sostituisci').'</button>
                                </div>';
    }
    echo '
                                <div class="col-md-1 pull-right">
                                    <button type="submit" class="btn btn-success pull-right"><i class="fa fa-check"></i> '.tr('Salva').'</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>';
}

echo '
<script>
    function sostituisci(id) {
        if(confirm("'.tr('Vuoi sostituire questo componente?').'")){
            redirect("'.base_path().'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'&op=sostituisci&backto=record-edit&id_plugin='.$id_plugin.'&id_old="+id);
        }
    }

    function elimina(id) {
        if(confirm("'.tr('Vuoi eliminare questo componente?').'")){
            redirect("'.base_path().'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'&op=delete&backto=record-edit&id_plugin='.$id_plugin.'&id="+id);
        }
    }

</script>';
