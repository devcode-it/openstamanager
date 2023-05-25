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
                        <i class="fa fa-plus"></i> '.tr('Nuova').'
                    </a>

                    <a class="btn btn-sm btn-primary" data-href="'.$checklist_module->fileurl('components/add-checklist.php').'?id_module='.$id_module.'&id_record='.$id_record.'&id_plugin='.$id_plugin.'&manager_id='.$checks_id.'" data-toggle="tooltip" data-title="'.tr('Aggiungi check').'">
                        <i class="fa fa-plus"></i> '.tr('Checklist predefinite').'
                    </a>
                </div>
            </div>

            <div class="clearfix"></div>
            <br>';
}

$checks = $structure->mainChecks($id_record);

echo "      <table class='table'>
                <tbody class='sort' data-sonof='0'>";
foreach ($checks as $check) {
    echo renderChecklist($check);
}
echo "          </tbody>
            </table>";

    echo '
        </div>
    </div>
</div>';

echo '
<script>$(document).ready(init)</script>

<script>

$(document).ready(function(){
    $("[data-toggle=\'tooltip\']").tooltip();
  });

sortable("#tab_checks .sort", {
    axis: "y",
    handle: ".handle",
    cursor: "move",
    dropOnEmpty: true,
    scroll: true,
});

sortable_table = sortable("#tab_checks .sort").length;

for(i=0; i<sortable_table; i++){
    sortable("#tab_checks .sort")[i].addEventListener("sortupdate", function(e) {

        var sonof = $(this).data("sonof");

        let order = $(this).find(".sonof_"+sonof+"[data-id]").toArray().map(a => $(a).data("id"))
    
        $.post("'.$checklist_module->fileurl('ajax.php').'", {
            op: "update_position",
            order: order.join(","),
        });
    });
}

$("textarea[name=\'note_checklist\']").keyup(function(){
    $.post("'.$checklist_module->fileurl('ajax.php').'", {
        op: "save_note",
        note: $(this).val(),
        id: $(this).attr("id"),
    }, function() {
        alertPush();
    });
});

$(".checkbox").click(function(){
    if($(this).is(":checked")){
        $.post("'.$checklist_module->fileurl('ajax.php').'", {
            op: "save_checkbox",
            id: $(this).attr("data-id"),
        },function(result){
            reload();
        });

        $(this).parent().parent().find(".text").css("text-decoration", "line-through");

        parent = $(this).attr("data-id");
        $("tr.sonof_"+parent).find("input[type=checkbox]").each(function(){
            if(!$(this).is(":checked")){
                $(this).click();
            }
        });
    }else{
        $.post("'.$checklist_module->fileurl('ajax.php').'", {
            op: "remove_checkbox",
            id: $(this).attr("data-id"),
        },function(result){
            reload();
        });

        $(this).parent().parent().find(".text").css("text-decoration", "none");

        parent = $(this).attr("data-id");
        $("tr.sonof_"+parent).find("input[type=checkbox]").each(function(){
            if($(this).is(":checked")){
                $(this).click();
            }
        });
    }
})

function delete_check(id){
    if(confirm("Eliminare questa checklist?")){
        $.post("'.$checklist_module->fileurl('ajax.php').'", {
            op: "delete_check",
            id: id,
        }, function(){
            location.reload();
        });
    }
}

function edit_check(id){
    launch_modal("Modifica checklist", "'.$checklist_module->fileurl('components/edit-check.php').'?id_record="+id, 1);
}

function reload(){
    $("#loading_'.$checks_id.'").removeClass("hide");
    $("#loading_'.$checks_id.'").addClass("show");
    $("#'.$checks_id.'").load(globals.rootdir + "/ajax.php?op=checklists&id_module='.$id_module.'&id_record='.$id_record.'&id_plugin='.$id_plugin.'", function() {
        $("#loading_'.$checks_id.'").removeClass("show");
        $("#loading_'.$checks_id.'").addClass("hide");
    });
}

</script>';

?>