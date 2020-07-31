<?php

include_once __DIR__.'/../../core.php';

echo '
<form action="" method="post" id="edit-form">
    <input type="hidden" name="op" value="update">
    <input type="hidden" name="backto" value="record-edit">

    <!-- DATI -->
    <div class="panel panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title">'.tr('Dati').'</h3>
        </div>

        <div class="panel-body">
            <div class="row">
                <div class="col-md-6">
                    {[ "type": "text", "label": "'.tr('Nome').'", "name": "name", "value": "$name$", "required": 1 ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Modulo del template').'", "name": "module", "values": "query=SELECT id, title AS descrizione FROM zz_modules WHERE enabled = 1", "value": "'.$record['id_module'].'", "disabled": "'.!empty($record['id_plugin']).'" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Plugin del template').'", "name": "plugin", "values": "query=SELECT id, title AS descrizione, (SELECT name FROM zz_modules WHERE zz_modules.id = zz_plugins.idmodule_from) AS optgroup FROM zz_plugins WHERE enabled = 1", "value": "'.$record['id_plugin'].'", "disabled": "'.!empty($record['id_module']).'" ]}
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

$checks = $record->checks;

$list = [];
foreach ($checks as $check) {
    $list[] = [
        'id' => $check->id,
        'text' => $check->content,
    ];
}

echo '

<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title">'.tr('Checklist').'</h3>
    </div>

    <div class="panel-body">
        <form action="" method="post" id="checklist-form" class="row">
            <input type="hidden" name="op" value="add_item">
            <input type="hidden" name="backto" value="record-edit">

            <div class="col-md-6">
                {[ "type": "text", "placeholder": "'.tr('Contenuto').'", "name": "content", "class": "unblockable", "required": 1 ]}
            </div>

            <div class="col-md-4">
                {[ "type": "select", "placeholder": "'.tr('Genitore').'", "name": "parent", "class": "unblockable", "values": '.json_encode($list).' ]}
            </div>

            <div class="col-md-1 text-right">
                <button type="submit" class="btn btn-success">
                    <i class="fa fa-upload"></i> '.tr('Crea').'
                </button>
            </div>
        </form>
        <hr>

        <ul class="todo-list checklist">';

    $checks = $record->mainChecks();
    foreach ($checks as $check) {
        echo renderChecklist($check);
    }

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
</a>';
