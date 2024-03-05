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

use Modules\Checklists\Check;
use Models\Module;

$matricole = (array) post('matricole');
$id_modulo_impianti = (new Module())->getByName('Impianti')->id_record;
$modulo_checklist = Module::find((new Module())->getByName('Checklists')->id_record);

// Salvo gli impianti selezionati
if (filter('op') == 'link_impianti') {
    $matricole_old = $dbo->fetchArray('SELECT * FROM my_impianti_interventi WHERE idintervento = '.prepare($id_record));
    $matricole_old = array_column($matricole_old, 'idimpianto');

    // Individuazione delle matricole mancanti
    foreach ($matricole_old as $matricola) {
        if (!in_array($matricola, $matricole)) {
            $dbo->query('DELETE FROM my_impianti_interventi WHERE idintervento='.prepare($id_record).' AND idimpianto = '.prepare($matricola));
            Check::deleteLinked([
                'id_module' => $id_module,
                'id_record' => $id_record,
                'id_module_from' => $id_modulo_impianti,
                'id_record_from' => $matricola,
            ]);

            $components = $dbo->fetchArray('SELECT * FROM my_componenti WHERE id_impianto = '.prepare($matricola));
            if (!empty($components)) {
                foreach ($components as $component) {
                    $dbo->query('DELETE FROM my_componenti_interventi WHERE id_componente = '.prepare($component['id']).' AND id_intervento = '.prepare($id_record));
                }
            }
        }
    }

    foreach ($matricole as $matricola) {
        if (!in_array($matricola, $matricole_old)) {
            $dbo->query('INSERT INTO my_impianti_interventi(idimpianto, idintervento) VALUES('.prepare($matricola).', '.prepare($id_record).')');

            $checks_impianti = $dbo->fetchArray('SELECT * FROM zz_checks WHERE id_module = '.prepare($id_modulo_impianti).' AND id_record = '.prepare($matricola));
            foreach ($checks_impianti as $check_impianto) {
                $id_parent_new = null;
                if ($check_impianto['id_parent']) {
                    $parent = $dbo->selectOne('zz_checks', '*', ['id' => $check_impianto['id_parent']]);
                    $id_parent_new = $dbo->selectOne('zz_checks', '*', ['content' => $parent['content'], 'id_module' => $id_module, 'id_record' => $id_record])['id'];
                }
                $check = Check::build($user, $structure, $id_record, $check_impianto['content'], $id_parent_new, $check_impianto['is_titolo'], $check_impianto['order'], $id_modulo_impianti, $matricola);
                $check->id_module = $id_module;
                $check->id_plugin = $id_plugin;
                $check->note = $check_impianto['note'];
                $check->save();
            }
        }
    }

    flash()->info(tr('Informazioni impianti salvate!'));
} elseif (filter('op') == 'link_componenti') {
    $components = (array) post('componenti');
    $id_impianto = post('id_impianto');

    $dbo->query('DELETE FROM my_componenti_interventi WHERE id_componente IN (SELECT id FROM my_componenti WHERE id_impianto = '.prepare($id_impianto).') AND id_intervento = '.prepare($id_record));

    foreach ($components as $component) {
        $dbo->query('INSERT INTO my_componenti_interventi(id_componente, id_intervento) VALUES ('.prepare($component).', '.prepare($id_record).')');
    }

    flash()->info(tr('Informazioni componenti salvate!'));
}

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

/*
 * Aggiunta impianti all'intervento
*/
// Elenco impianti collegati all'intervento
$impianti = $dbo->fetchArray('SELECT idimpianto FROM my_impianti_interventi WHERE idintervento='.prepare($id_record));
$impianti = !empty($impianti) ? array_column($impianti, 'idimpianto') : [];

// Elenco sedi
$sedi = $dbo->fetchArray('SELECT id, nomesede, citta FROM an_sedi WHERE idanagrafica='.prepare($record['idanagrafica'])." UNION SELECT 0, 'Sede legale', '' ORDER BY id");

echo '
    <form action="'.base_path().'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'&op=link_impianti" method="post">
        <input type="hidden" name="backto" value="record-edit">
        <div class="row">
            <div class="col-md-12">
                {[ "type": "select", "name": "matricole[]", "label": "'.tr('Impianti').'", "multiple": 1, "value": "'.implode(',', $impianti).'", "ajax-source": "impianti-cliente", "select-options": {"idanagrafica": '.$record['idanagrafica'].', "idsede_destinazione": '.($record['idsede_destinazione'] ?: '""').'}, "extra": "'.$readonly.'", "icon-after": "add|'.$id_modulo_impianti.'|id_anagrafica='.$record['idanagrafica'].'" ]}
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <button type="submit" class="btn btn-success pull-right" '.$disabled.'><i class="fa fa-check"></i> '.tr('Salva impianti').'</button>
            </div>
        </div>
    </form>
    <br>';

if (!empty($impianti)) {
    // IMPIANTI
    echo '
    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">'.tr('Impianti soggetti ad intervento').'</h3>
        </div>
        <div class="box-body">
            <table class="table table-hover table-condensed table-striped">
                <tr>
                    <th class="text-center" width="1%"></th>
                    <th class="text-center" width="10%">'.tr('Matricola').'</th>
                    <th class="text-center" width="20%">'.tr('Nome').'</th>
                    <th class="text-center" width="8%">'.tr('Data').'</th>
                    <th class="text-center">'.tr('Descrizione').'</th>
                    <th class="text-center" width="25%">'.tr("Componenti soggetti all'intervento").'</th>
                    <th class="text-center" width="5%">Checklist</th>
                </tr>';

    $impianti_collegati = $dbo->fetchArray('SELECT * FROM my_impianti_interventi INNER JOIN my_impianti ON my_impianti_interventi.idimpianto = my_impianti.id WHERE idintervento = '.prepare($id_record));
    foreach ($impianti_collegati as $impianto) {
        $checks = Check::where('id_module_from', $id_modulo_impianti)->where('id_record_from', $impianto['id'])->where('id_module', $id_module)->where('id_record', $id_record)->where('id_parent', null)->get();

        $type = 'muted';
        $class = 'disabled';
        $icon = 'circle-o';
        $icon2 = 'remove';
        if (sizeof($checks)) {
            $class = '';
            $icon = 'plus';
            $checks_not_verified = $checks->where('checked_at', null)->count();
            $type = $checks_not_verified ? 'warning' : 'success';
            $icon2 = $checks_not_verified ? 'clock-o' : 'check';
        }
        echo '
                <tr>
                    <td class="text-left">
                        <button type="button" class="btn btn-xs btn-default '.$class.'" onclick="toggleDettagli(this)">
                            <i class="fa fa-'.$icon.'"></i>
                        </button>
                        
                    </td>
                    <td>'.$impianto['matricola'].'</td>
                    <td>'.Modules::link('Impianti', $impianto['id'], $impianto['nome']).'</td>
                    <td class="text-center">'.Translator::dateToLocale($impianto['data']).'</td>
                    <td>'.$impianto['descrizione'].'</td>
                    <td>
                        <form action="'.base_path().'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'&op=link_componenti&matricola='.$impianto['id'].'" method="post">
                            <input type="hidden" name="backto" value="record-edit">
                            <input type="hidden" name="id_impianto" value="'.$impianto['id'].'">';

        $inseriti = $dbo->fetchArray('SELECT * FROM my_componenti_interventi WHERE id_intervento = '.prepare($id_record));
        $ids = array_column($inseriti, 'id_componente');

        echo '
                            {[ "type": "select", "multiple": 1, "name": "componenti[]", "id": "componenti_'.$impianto['id'].'", "ajax-source": "componenti", "select-options": {"matricola": '.$impianto['id'].'}, "value": "'.implode(',', $ids).'", "readonly": "'.!empty($readonly).'", "disabled": "'.!empty($disabled).'", "icon-after": "<button type=\"submit\" class=\"btn btn-success\" '.$disabled.'> <i class=\"fa fa-check\"></i> '.tr('Salva').'</button>" ]}
                        </form>
                    </td>
                    <td class="text-center"><i class="fa fa-'.$icon2.' fa-2x text-'.$type.'"></i></td>
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
        </div>
    </div>';
} else {
    echo '
    <div class="alert alert-info text-center">
        <i class="fa fa-info-circle"></i> '.tr('Nessun impianto collegato a questo intervento').'
    </div>';
}

echo '
<script>
    function toggleDettagli(trigger) {
        const tr = $(trigger).closest("tr");
        const dettagli = tr.next();

        if (dettagli.css("display") === "none"){
            dettagli.show(500);
            $(trigger).children().removeClass("fa-plus"); 
            $(trigger).children().addClass("fa-minus");
        } else {
            dettagli.hide(500);
            $(trigger).children().removeClass("fa-minus"); 
            $(trigger).children().addClass("fa-plus");
        }
    }
</script>';

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
    
        $.post("'.$modulo_checklist->fileurl('ajax.php').'", {
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
    $.post("'.$modulo_checklist->fileurl('ajax.php').'", {
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
        $.post("'.$modulo_checklist->fileurl('ajax.php').'", {
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
        $.post("'.$modulo_checklist->fileurl('ajax.php').'", {
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
        $.post("'.$modulo_checklist->fileurl('ajax.php').'", {
            op: "delete_check",
            id: id,
        }, function(){
            location.reload();
        });
    }
}

function edit_check(id){
    launch_modal("Modifica checklist", "'.$modulo_checklist->fileurl('components/edit-check.php').'?id_record="+id, 1);
}

</script>';
