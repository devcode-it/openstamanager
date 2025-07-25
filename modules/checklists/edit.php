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

echo '
<form action="" method="post" id="edit-form">
    <input type="hidden" name="op" value="update">
    <input type="hidden" name="backto" value="record-edit">

    <!-- DATI -->
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">'.tr('Dati').'</h3>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    {[ "type": "text", "label": "'.tr('Nome').'", "name": "name", "value": "$name$", "required": 1 ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Modulo del template').'", "name": "module", "values": "query=SELECT `zz_modules`.`id`, `title` AS descrizione FROM `zz_modules` LEFT JOIN `zz_modules_lang` ON (`zz_modules`.`id` = `zz_modules_lang`.`id_record` AND `zz_modules_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `enabled` = 1", "value": "'.$record->id_module.'", "disabled": "'.!empty($record->id_plugin).'" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Plugin del template').'", "name": "plugin", "values": "query=SELECT `zz_plugins`.`id`, `zz_plugins_lang`.`title` AS descrizione, `zz_modules_lang`.`title` AS optgroup FROM zz_plugins INNER JOIN `zz_modules` ON `zz_plugins`.`idmodule_to` = `zz_modules`.`id` LEFT JOIN `zz_modules_lang` ON (`zz_modules`.`id` = `zz_modules_lang`.`id_record` AND `zz_modules_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') LEFT JOIN `zz_plugins_lang` ON (`zz_plugins`.`id` = `zz_plugins_lang`.`id_record` AND `zz_plugins_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `zz_plugins`.`enabled` = 1", "value": "'.$record->id_plugin.'", "disabled": "'.!empty($record->id_module).'" ]}
                </div>
            </div>
        </div>
    </div>
</form>

<script>
$(document).ready(function() {
    $("#module").change(function() {
        if ($(this).val()){
            $("#plugin").val("").attr("disabled", true);
        } else {
            $("#plugin").val("").attr("disabled", false);
        }
    });

    $("#plugin").change(function() {
        if ($(this).val()){
            $("#module").val("").attr("disabled", true);
        } else {
            $("#module").val("").attr("disabled", false);
        }
    });
});
</script>';

$checks = $record->mainChecks();

$list = [];
foreach ($checks as $check) {
    $list[] = [
        'id' => $check->id,
        'text' => $check->content,
    ];
}

echo '

<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">'.tr('Checklist').'</h3>
    </div>

    <div class="card-body">
        <form action="" method="post" id="checklist-form" class="row">
            <input type="hidden" name="op" value="add_item">
            <input type="hidden" name="backto" value="record-edit">

            <div class="col-md-7">
                '.input([
    'type' => 'ckeditor',
    'label' => tr('Contenuto'),
    'name' => 'content',
    'required' => 1,
    'value' => '',
]).'
            </div>

            <div class="col-md-5 text-center">
                {[ "type": "select", "label": "'.tr('Genitore').'", "name": "parent", "class": "unblockable", "values": '.json_encode($list).' ]}
                <br>
                {[ "type": "checkbox", "label": "'.tr('Utilizza come titolo').'", "name": "is_titolo" ]}
                <br><br>
                <button type="submit" class="btn btn-lg btn-success">
                    <i class="fa fa-upload"></i> '.tr('Crea').'
                </button>
            </div>
        </form>
        <hr>

        <ul class="todo-list checklist">';

echo "      <table class='table'>
                <tbody class='sort' data-sonof='0'>";
foreach ($checks as $check) {
    echo renderChecklistInserimento($check);
}
echo '          </tbody>
            </table>';

echo '
        </ul>
    </div>
</div>';

echo '
<script>
$(document).ready(function() {
    $(".checklist").sortable({
        placeholder: "sort-highlight",
        handle: ".handle",
        forcePlaceholderSize: true,
        zIndex: 999999,
        update: function(event, ui) {
            var order = [];
            $(".checklist > li").each( function() {
                order.push($(this).data("id"));
            });

            $.post(globals.rootdir + "/actions.php", {
                id_module: globals.id_module,
                id_record: globals.id_record,
                op: "update_position",
                order: order.join(","),
            });
        }
    });

    $(".check-delete").click(function(event){
        var li = $(this).closest("li");
        var id = li.attr("id").replace("check_", "");

        $.ajax({
            url: globals.rootdir + "/actions.php",
            cache: false,
            type: "POST",
            data: {
                id_module: globals.id_module,
                op: "delete_item",
                check_id: id,
            },
            success: function() {
                location.reload();
            }
        });

        event.stopPropagation();
    });
});
</script>';

echo '
<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> '.tr('Elimina').'
</a>

<script>

sortable(".sort", {
    axis: "y",
    handle: ".handle",
    cursor: "move",
    dropOnEmpty: true,
    scroll: true,
});

sortable_table = sortable(".sort").length;

for(i=0; i<sortable_table; i++){
    sortable(".sort")[i].addEventListener("sortupdate", function(e) {

        var sonof = $(this).data("sonof");

        let order = $(this).find(".sonof_"+sonof+"[data-id]").toArray().map(a => $(a).data("id"))
    
        $.post("'.$checklist_module->fileurl('ajax.php').'", {
            op: "update_position",
            order: order.join(","),
            main_check: 1,
        });
    });
}

function delete_check(id){
    if(confirm("Eliminare questa checklist?")){
        $.post("'.$checklist_module->fileurl('ajax.php').'", {
            op: "delete_check",
            id: id,
            main_check: 1,
            id_module: globals.id_module,
            id_record: id,
        }, function(){
            location.reload();
        });
    }
}

function edit_check(id){
    launch_modal("Modifica checklist", "'.$checklist_module->fileurl('components/edit-check.php').'?id_module=" + globals.id_module + "&id_plugin=" + globals.id_plugin + "&id_record="+id+"&main_check=1", 1);
}

</script>';
