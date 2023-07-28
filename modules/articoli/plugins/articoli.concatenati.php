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

include_once __DIR__.'/../../../core.php';

//get articoli concatenati by id_record
$concatenati = $dbo->fetchArray(
    'SELECT mg_articoli_concatenati.*, mg_articoli.descrizione AS descrizione_concatenato
    FROM mg_articoli_concatenati
    JOIN mg_articoli ON mg_articoli.id=mg_articoli_concatenati.id_articolo_concatenato
    WHERE id_articolo='.prepare($id_record)
);


echo '
<div class="row">
    <div class="col-md-5">
        {[ "type":"select", "id":"select-articolo", "label":"'.tr('Articolo').'", "ajax-source": "articoli" ]}
    </div>

    <div class="col-md-1">
        <div class="btn-group btn-group-flex">
            <button type="button" class="btn btn-primary" style="margin-top:25px;" onclick="aggiungiArticolo(this)">
                <i class="fa fa-plus"></i> '.tr('Aggiungi').'
            </button>
        </div>
    </div>
</div>

<table class="table table-hover table-condensed table-bordered" id="tbl-concatenati">
    <thead>
        <tr>
            <th class="text-center" width="100">'.tr('Cod. articolo concatenato').'</th>
            <th class="text-center" width="450">'.tr('Descrizone articolo concatenato').'</th>
            <th class="text-center" width="95">'.tr('Prezzo').'</th>
            <th class="text-center" width="95">'.tr('Prezzo ivato').'</th>
            <th class="text-center" width="40"></th>
        </tr>
    </thead>
    <tbody>';
        foreach($concatenati as $concatenato) {
            echo '
            <tr>
                <td><a href="'.base_path().'/controller.php?id_module='.$id_module.'&id_record='.$concatenato['id_articolo_concatenato'].'" target="_blank">'.$concatenato['id_articolo_concatenato'].'</a></td>
                <td>'.$concatenato['descrizione_concatenato'].'</td>
                <td class="text-right">'.moneyFormat($concatenato['prezzo']).'</td>
                <td class="text-right">'.moneyFormat($concatenato['prezzo_ivato']).'</td>
                <td class="text-center">
                    <div class="input-group-btn">
                        <a class="btn btn-xs btn-warning" title="'.tr('Modifica riga').'" onclick="modificaRiga(\''.$concatenato['id'].'\')">
                            <i class="fa fa-edit"></i>
                        </a>

                        <a class="btn btn-xs btn-danger" title="'.tr('Rimuovi riga').'" onclick="rimuoviRiga(\''.$concatenato['id'].'\')">
                            <i class="fa fa-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>';
        }
    echo '
    </tbody>
</table>

<!--<div class="btn-group">
    <button type="button" class="btn btn-xs btn-default disabled" id="elimina_righe" onclick="rimuoviArticolo(getSelectData());">
        <i class="fa fa-trash"></i>
    </button>
</div>-->


<script>
	async function aggiungiArticolo(button) {
		id_articolo = $("#select-articolo").val();

        $.ajax({
            url: "'.$rootdir.'/modules/articoli/actions.php",
            type: "post",
            data: {
                op: "add_concatenato",
                id_articolo: globals.id_record,
                id_articolo_concatenato: id_articolo,
            },
            success: function(data){
                location.reload();
            },
        });
	}

	async function modificaRiga(id) {
        openModal("'.tr('Dettagli').'", "'.$rootdir.'/modules/articoli/plugins/articoli.concatenati.edit.php?id_module=" + globals.id_module + "&id_record=" + globals.id_record + "&id_plugin='.$id_plugin.'&id=" + id);
	}

    async function rimuoviRiga(id) {
        swal({
            title: "'.tr('Attenzione').'",
            text: "'.tr('Sei sicuro di voler eliminare questa riga?').'",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "'.tr('Sì').'",
            cancelButtonText: "'.tr('No').'",
        }).then((result) => { //click su si
            $.ajax({
                url: "'.$rootdir.'/modules/articoli/actions.php",
                type: "post",
                data: {
                    op: "remove_concatenato",
                    id: id,
                },
                success: function(data){
                    location.reload();
                },
            });
        }).catch((err) => { //click su no

        });
    }
</script>';
