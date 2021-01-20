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

$checklists = $structure->checklists()->orderBy('created_at')->get();
$list = [];
foreach ($checklists as $checklist) {
    $list[] = [
        'id' => $checklist->id,
        'text' => $checklist->name,
    ];
}

echo '
<form action="" method="post" id="check-form">
    <div class="row">
        <div class="col-md-12">
            {[ "type": "select", "label": "'.tr('Checklist').'", "name": "checklist", "values": '.json_encode($list).' ]}
        </div>
    </div>

    <div class="row">
         <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Utente assegnato').'", "name": "assigned_users", "ajax-source": "utenti", "multiple": 1 ]}
        </div>

        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Gruppo assegnato').'", "name": "group_id", "values": "query=SELECT id, nome AS text FROM zz_groups" ]}
        </div>
    </div>

    <!-- PULSANTI -->
	<div class="row">
        <div class="col-md-12 text-right">
            <button type="button" class="btn btn-primary" id="check-add">
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
        addChecklist(this);
    });

    $("#assigned_users").change(function() {
        if ($(this).selectData() && $(this).val()!="") {
            $("#group_id").val("").attr("disabled", true).attr("required", false);
        } else {
            $("#group_id").val("").attr("disabled", false).attr("required", true);
        }
    });

    $("#group_id").change(function() {
        if ($(this).selectData()) {
            $("#assigned_users").val("").attr("disabled", true).attr("required", false);
        } else {
            $("#assigned_users").val("").attr("disabled", false).attr("required", true);
        }
    });
});

function addChecklist(btn) {
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

    checklist.cloneChecklist({
        checklist: $form.find("#checklist").val(),
        assigned_users: $form.find("#assigned_users").val(),
        group_id: $form.find("#group_id").val(),
    });

    $form.closest(".modal").modal("hide");
}
</script>';
