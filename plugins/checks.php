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

include_once __DIR__.'/../core.php';

$checklist_module = Modules::get('Checklists');
$checks_id = 'checklist_'.$id_module.'_'.$id_plugin;

echo '
<div id="'.$checks_id.'">
    <div class="box box-primary">
        <div class="box-header">
            <h3 class="box-title">'.tr('Checklist').'</h3>
        </div>
        <div class="box-body" style="position:relative">
            <div id="loading_'.$checks_id.'" class="text-center hide component-loader">
                <div>
                    <i class="fa fa-refresh fa-spin fa-3x fa-fw"></i>
                    <span class="sr-only">'.tr('Caricamento...').'</span>
                </div>
            </div>';

// Form per la creazione di una nuova checklist
if ($structure->permission == 'rw') {
    echo '
            <div class="row">
                <div class="col-md-12">
                    <a class="btn btn-sm btn-primary" data-href="'.$checklist_module->fileurl('components/add-check.php').'?id_module='.$id_module.'&id_record='.$id_record.'&id_plugin='.$id_plugin.'&manager_id='.$checks_id.'" data-toggle="tooltip" data-title="'.tr('Aggiungi check').'">
                        <i class="fa fa-plus"></i> '.tr('Check').'
                    </a>

                    <a class="btn btn-sm btn-primary" data-href="'.$checklist_module->fileurl('components/add-checklist.php').'?id_module='.$id_module.'&id_record='.$id_record.'&id_plugin='.$id_plugin.'&manager_id='.$checks_id.'" data-toggle="tooltip" data-title="'.tr('Aggiungi check').'">
                        <i class="fa fa-plus"></i> '.tr('Checklist').'
                    </a>
                </div>
            </div>

            <div class="clearfix"></div>
            <br>';
}

$checks = $structure->mainChecks($id_record);

echo '
            <ul class="todo-list checklist">';

    foreach ($checks as $check) {
        echo renderChecklist($check);
    }

    echo '
            </ul>
        </div>
    </div>
</div>';

echo '
<script>$(document).ready(init)</script>

<script type="module">
import Checklist from "./modules/checklists/js/checklist.js";

var checklists = checklists ? checklists : {};
$(document).ready(function() {
    checklists["'.$checks_id.'"] = new Checklist({
        id_module: "'.$id_module.'",
        id_plugin: "'.$id_plugin.'",
        id_record: "'.$id_record.'",
    }, "'.$checks_id.'");

    $(".checklist").sortable({
        placeholder: "sort-highlight",
        handle: ".handle",
        forcePlaceholderSize: true,
        zIndex: 999999,
        update: function(event, ui) {
            var order = [];
            $(".checklist > li").each( function(){
                order.push($(this).data("id"));
            });

            $.post(globals.rootdir + "/actions.php", {
                id_module: "'.$id_module.'",
                id_plugin: "'.$id_plugin.'",
                id_record: "'.$id_record.'",
                op: "sort_checks",
                order: order.join(","),
            });
        }
    });

    $(".checklist").todoList({
        onCheck  : function () {
            var id = $(this).parent().data("id");

            checklists["'.$checks_id.'"].toggleCheck(id);
        },
        onUnCheck: function () {
            var id = $(this).parent().data("id");

            checklists["'.$checks_id.'"].toggleCheck(id);
        }
    });

    $(".check-delete").click(function(event){
        var li = $(this).closest("li");
        var id = li.data("id");

        swal({
            title: "'.tr("Rimuovere l'elemento della checklist?").'",
            html: "'.tr('Tutti gli elementi figli saranno rimossi di conseguenza. Continuare?').'",
            showCancelButton: true,
            confirmButtonText: "'.tr('Procedi').'",
            type: "error",
        }).then(function (result) {
            checklists["'.$checks_id.'"].deleteCheck(id);
        });

        event.stopPropagation();
    });
});
</script>';
