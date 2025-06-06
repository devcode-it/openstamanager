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

$manager_id = filter('manager_id');

$checks = $structure->recordChecks($id_record);
$list = [];
foreach ($checks as $check) {
    $list[] = [
        'id' => $check->id,
        'text' => $check->content,
    ];
}

echo '
<form action="" method="post" id="check-form">
    <div class="row">
        <div class="col-md-12">
            '.input([
    'type' => 'ckeditor',
    'label' => tr('Contenuto'),
    'name' => 'content',
    'required' => 1,
    'value' => '',
]).'
        </div> 
    </div>

    <div class="row">
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Collega a').'", "name": "parent", "values": '.json_encode($list).' ]}
        </div>

        <div class="col-md-6">
            {[ "type": "checkbox", "label": "'.tr('Utilizza come titolo').'", "name": "is_titolo" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Gruppo assegnato').'", "name": "group_id", "values": "query=SELECT `zz_groups`.`id`, `title` AS text FROM `zz_groups` LEFT JOIN `zz_groups_lang` ON (`zz_groups`.`id` = `zz_groups_lang`.`id_record` AND `zz_groups_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')" ]}
        </div>

        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Utente assegnato').'", "name": "assigned_users", "ajax-source": "utenti", "multiple": 1 ]}
        </div>
    </div>

    <!-- PULSANTI -->
	<div class="row">
        <div class="col-md-12 text-right">
            <br><br><button type="button" class="btn btn-primary" id="check-add">
                <i class="fa fa-plus"></i> '.tr('Aggiungi').'
            </button>
        </div>
    </div>
</form>

<script>$(document).ready(init)</script>

<script type="module">
import Checklist from "./modules/checklists/js/checklist.js";

$(document).ready(function() {
    $("#check-add").click(function(event){
        addCheck(this);
    });

    $("#parent").change(function() {
        if ($(this).selectData()) {
            $("#assigned_users").val("").attr("disabled", true).attr("required", false);
            $("#group_id").val("").attr("disabled", true).attr("required", false);
        } else {
            $("#assigned_users").val("").attr("disabled", false).attr("required", true);
            $("#group_id").val("").attr("disabled", false).attr("required", true);
        }
    });

    $("#assigned_users").change(function() {
        if ($(this).selectData() && $(this).val()!="") {
            $("#parent").val("").attr("disabled", true).attr("required", false);
            $("#group_id").val("").attr("disabled", true).attr("required", false);
        } else {
            $("#parent").val("").attr("disabled", false).attr("required", true);
            $("#group_id").val("").attr("disabled", false).attr("required", true);
        }
    });

    $("#group_id").change(function() {
        if ($(this).selectData()) {
            $("#parent").val("").attr("disabled", true).attr("required", false);
            $("#assigned_users").val("").attr("disabled", true).attr("required", false);
        } else {
            $("#parent").val("").attr("disabled", false).attr("required", true);
            $("#assigned_users").val("").attr("disabled", false).attr("required", true);
        }
    });
});

function addCheck(btn) {
    var $form = $(btn).closest("form");

    var continua = true;
    $form.find(":input:not(:button)").each(function (index, value) {
        continua &= $(this).parsley().validate();
    });

    if (!continua) {
        swal({
            type: "error",
            title: "'.tr('Errore').'",
            text: "'.tr('Alcuni campi obbligatori non sono stati compilati correttamente.').'",
        });

        return;
    }

    var checklist = new Checklist({
        id_module: "'.$id_module.'",
        id_plugin: "'.$id_plugin.'",
        id_record: "'.$id_record.'",
    }, "'.$manager_id.'");

    checklist.addCheck({
        content: input("content").get(),
        is_titolo: input("is_titolo").get(),
        parent: $form.find("#parent").val(),
        assigned_users: $form.find("#assigned_users").val(),
        group_id: $form.find("#group_id").val(),
    });

    $form.closest(".modal").modal("hide");
}
</script>';
