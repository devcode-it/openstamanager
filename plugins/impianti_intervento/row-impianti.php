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

use Modules\Checklists\Check;
use Models\Module;

$id_modulo_impianti = (new Module())->getByName('Impianti')->id_record;
$checklist_module = Module::find((new Module())->getByName('Checklists')->id_record);
// Blocco della modifica impianti se l'intervento è completato
$dati_intervento = $dbo->fetchArray('SELECT `in_statiintervento`.`is_completato` FROM `in_statiintervento` INNER JOIN `in_interventi` ON `in_statiintervento`.`id` = `in_interventi`.`idstatointervento` WHERE `in_interventi`.`id`='.prepare($id_record));
$is_completato = $dati_intervento[0]['is_completato'];

if ($is_completato) {
    $readonly = 'readonly';
    $disabled = 'disabled';
} else {
    $readonly = '';
    $disabled = '';
}

$where = get('search') ? 'AND (my_impianti.matricola LIKE '.prepare('%'.get('search').'%').' OR my_impianti.nome LIKE '.prepare('%'.get('search').'%').')' : '';
$impianti_collegati = $dbo->fetchArray('SELECT * FROM my_impianti_interventi INNER JOIN my_impianti ON my_impianti_interventi.idimpianto = my_impianti.id WHERE idintervento = '.prepare($id_record).' '.$where);
$n_impianti = count($impianti_collegati);

$impianti_non_completati = 0;
$impianti_completati = 0;
$impianti_non_previsti = 0;
foreach ($impianti_collegati as $impianto) {
    $checks = Check::where('id_module_from', $id_modulo_impianti)->where('id_record_from', $impianto['id'])->where('id_module', $id_module)->where('id_record', $id_record)->where('id_parent', null)->get();
    if (sizeof($checks)) {
        $has_checks_not_verified = $checks->where('checked_at', null)->count();
        if ($has_checks_not_verified) {
            $impianti_non_completati += 1;
        } else {
            $impianti_completati += 1;
        }
    } else {
        $impianti_non_previsti += 1;
    }
}

$percentuale_completati = $n_impianti ? round(($impianti_completati * 100) / $n_impianti) : 0;
$percentuale_non_completati = $n_impianti ? round(($impianti_non_completati * 100) / $n_impianti) : 0;
$percentuale_non_previsti = $n_impianti ? round(($impianti_non_previsti * 100) / $n_impianti) : 0;

echo '
<div class="row">
    <div class="col-md-offset-4 col-md-4 text-center">
        <h4>'.strtoupper( tr('Impianti') ).': '.$n_impianti.'</h4>
        <div class="progress">
            <div class="progress-bar progress-bar-striped progress-bar-success" role="progressbar" style="width:'.$percentuale_completati.'%"><i class="fa fa-check"></i> <b>'.$impianti_completati.'</b></div>

            <div class="progress-bar progress-bar-striped progress-bar-danger" role="progressbar" style="width:'.$percentuale_non_completati.'%"><i class="fa fa-clock-o"></i> <b>'.$impianti_non_completati.'</b></div>

            <div class="progress-bar progress-bar-striped progress-bar-warning" role="progressbar" style="width:'.$percentuale_non_previsti.'%"><i class="fa fa-times"></i> <b>'.$impianti_non_previsti.'</b></div>
        </div>
    </div>

    <div class="col-md-4 text-left">
        <br><br>
        <button type="button" class="btn btn-sm btn-default" onclick="caricaImpianti()">
            <i class="fa fa-refresh"></i> '.tr('Aggiorna').'
        </button>
    </div>
</div>


<table class="table table-hover table-condensed table-striped">
    <tr>
        <th class="text-center" width="1%"></th>
        <th class="text-center" width="10%">'.tr('Matricola').'</th>
        <th class="text-center" width="20%">'.tr('Nome').'</th>
        <th class="text-center" width="7%">'.tr('Data').'</th>
        <th class="text-center">'.tr('Note').'</th>
        <th class="text-center" width="25%">'.tr("Componenti soggetti all'intervento").'</th>
        <th class="text-center" width="5%">Checklist</th>
        <th class="text-center" width="2%"></th>
    </tr>';
foreach ($impianti_collegati as $impianto) {
    $checks = Check::where('id_module_from', $id_modulo_impianti)->where('id_record_from', $impianto['id'])->where('id_module', $id_module)->where('id_record', $id_record)->where('id_parent', null)->get();

    $type = 'warning';
    $class = 'disabled';
    $icon = 'circle-o';
    $icon2 = 'remove';
    if (sizeof($checks)) {
        $class = '';
        $icon = 'plus';
        $checks_not_verified = $checks->where('checked_at', null)->count();
        $type = $checks_not_verified ? 'danger' : 'success';
        $icon2 = $checks_not_verified ? 'clock-o' : 'check';
    }
echo '
    <tr data-id="'.$impianto['id'].'">
        <td class="text-left">
            <button type="button" class="btn btn-xs btn-default '.$class.'" onclick="toggleDettagli(this)">
                <i class="fa fa-'.$icon.'"></i>
            </button>
            
        </td>
        <td>'.$impianto['matricola'].'</td>
        <td>'.Modules::link('Impianti', $impianto['id'], $impianto['nome']).'</td>
        <td class="text-center">'.Translator::dateToLocale($impianto['data']).'</td>
        <td>
            {[ "type": "textarea", "name": "note", "id": "note_imp_'.$impianto['id'].'", "value": "'.$impianto['note'].'", "onchange": "updateImpianto($(this).closest(\'tr\').data(\'id\'))", "readonly": "'.!empty($readonly).'", "disabled": "'.!empty($disabled).'" ]}
        </td>
        <td>';
        $inseriti = $dbo->fetchArray('SELECT * FROM my_componenti_interventi WHERE id_intervento = '.prepare($id_record));
        $ids = array_column($inseriti, 'id_componente');

        echo '
                {[ "type": "select", "multiple": 1, "name": "componenti[]", "id": "componenti_imp_'.$impianto['id'].'", "ajax-source": "componenti", "select-options": {"matricola": '.$impianto['id'].'}, "value": "'.implode(',', $ids).'", "onchange": "updateImpianto($(this).closest(\'tr\').data(\'id\'))", "readonly": "'.!empty($readonly).'", "disabled": "'.!empty($disabled).'" ]}
            </form>
        </td>
        <td class="text-center"><i class="fa fa-'.$icon2.' fa-2x text-'.$type.'"></i></td>
        <td class="text-center"><button class="btn btn-sm btn-danger '.$disabled.'" onclick="rimuoviImpianto($(this).closest(\'tr\').data(\'id\'))"><i class="fa fa-trash"></i></button></td>
    </tr>

    <tr style="display: none">
        <td colspan="7">
            <table class="table">
                <tbody class="sort check-impianto" data-sonof="0">';
                    foreach ($checks as $check) {
                        echo renderChecklist($check);
                    }
                echo '
                </tbody>
            </table>
        </td>
    </tr>';
}
echo '
</table>
   
<script>
$(document).ready(init);

function rimuoviImpianto(id) {
    swal({
        title: "'.tr('Rimuovere questo impianto?').'",
        html: "'.tr('Sei sicuro di volere rimuovere questo impianto dal documento?').' '.tr("L'operazione è irreversibile").'.",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "'.tr('Sì').'"
    }).then(function () {
        $.ajax({
            url: globals.rootdir + "/actions.php",
            type: "POST",
            dataType: "json",
            data: {
                id_module: globals.id_module,
                id_record: globals.id_record,
                id_plugin: '.$id_plugin.',
                op: "delete_impianto",
                id: id,
            },
            success: function (response) {
                renderMessages();
                caricaImpianti();
            },
            error: function() {
                renderMessages();
                caricaImpianti();
            }
        });
    }).catch(swal.noop);
}

function updateImpianto(id) {
    var note = $("#note_imp_"+ id).val();
    var componenti = $("#componenti_imp_"+ id).val();

    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "POST",
        data: {
            id_module: globals.id_module,
            id_plugin: '.$id_plugin.',
            id_record: globals.id_record,
            op: "update_impianto",
            id_impianto: id,
            note: note,
            componenti: componenti
        },
        success: function (response) {
            renderMessages();
        },
        error: function() {
            renderMessages();
        }
    });
}

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

$("textarea[name=\'note_checklist\']").keyup(function() {
    $(this).parent().parent().parent().find(".save-nota").removeClass("btn-default");
    $(this).parent().parent().parent().find(".save-nota").addClass("btn-success");
});

function saveNota(id) {
    $.post("'.$checklist_module->fileurl('ajax.php').'", {
        op: "save_note",
        note: $("#note_" + id).val(),
        id: id
    }, function() {
        alertPush();
        $("#note_" + id).parent().parent().parent().find(".save-nota").removeClass("btn-success");
        $("#note_" + id).parent().parent().parent().find(".save-nota").addClass("btn-default");
    });
}

$(".check-impianto .checkbox").click(function(){
    if($(this).is(":checked")){
        $.post("'.$checklist_module->fileurl('ajax.php').'", {
            op: "save_checkbox",
            id: $(this).attr("data-id"),
        },function(result){
        });

        $(this).parent().parent().find(".text").css("text-decoration", "line-through");

        parent = $(this).attr("data-id");
        $("tr.sonof_"+parent).find("input[type=checkbox]").each(function(){
            if(!$(this).is(":checked")){
                $(this).click();
            }
        });
        $(this).parent().parent().find(".verificato").removeClass("hidden");
        $(this).parent().parent().find(".verificato").text("'.tr('Verificato da _USER_ il _DATE_', [
            '_USER_' => $user->username,
            '_DATE_' => dateFormat(date('Y-m-d')).' '.date('H:i'),
        ]).'");
    }else{
        $.post("'.$checklist_module->fileurl('ajax.php').'", {
            op: "remove_checkbox",
            id: $(this).attr("data-id"),
        },function(result){
        });

        $(this).parent().parent().find(".text").css("text-decoration", "none");

        parent = $(this).attr("data-id");
        $("tr.sonof_"+parent).find("input[type=checkbox]").each(function(){
            if($(this).is(":checked")){
                $(this).click();
            }
        });

        $(this).parent().parent().find(".verificato").addClass("hidden");
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
</script>';