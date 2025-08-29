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

use Models\Module;
use Modules\Checklists\Check;

$id_modulo_impianti = Module::where('name', 'Impianti')->first()->id;
$checklist_module = Module::where('name', 'Checklists')->first();
// Blocco della modifica impianti se l'intervento è completato
$dati_intervento = $dbo->fetchArray('SELECT `in_statiintervento`.`is_bloccato` FROM `in_statiintervento` INNER JOIN `in_interventi` ON `in_statiintervento`.`id` = `in_interventi`.`idstatointervento` WHERE `in_interventi`.`id`='.prepare($id_record));
$is_bloccato = $dati_intervento[0]['is_bloccato'];

if ($is_bloccato) {
    $readonly = 'readonly';
    $disabled = 'disabled';
} else {
    $readonly = '';
    $disabled = '';
}

$where = get('search') ? 'AND (my_impianti.matricola LIKE '.prepare('%'.get('search').'%').' OR my_impianti.nome LIKE '.prepare('%'.get('search').'%').')' : '';
$impianti_collegati = $dbo->fetchArray('SELECT my_impianti.id, my_impianti.matricola, my_impianti.nome, my_impianti.descrizione, my_impianti.data, my_impianti_interventi.note FROM my_impianti_interventi INNER JOIN my_impianti ON my_impianti_interventi.idimpianto = my_impianti.id WHERE idintervento = '.prepare($id_record).' '.$where);
$n_impianti = count($impianti_collegati);

// Calcolo percentuali rimosso - non più necessario

// Sezione impianti collegati
if ($n_impianti > 0) {
    echo '
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fa fa-list"></i>
                '.tr('Impianti Collegati').' ('.$n_impianti.')
            </h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" width="120px">'.tr('Matricola').'</th>
                            <th>'.tr('Nome Impianto').'</th>
                            <th class="text-center" width="100px">'.tr('Data').'</th>
                            <th width="300px">'.tr('Note').'</th>
                            <th width="250px">'.tr('Componenti').'</th>
                            <th class="text-center" width="60px">#</th>
                        </tr>
                    </thead>
                    <tbody>';
} else {
    // Determina il messaggio in base alla presenza di una ricerca
    $search_term = get('search');
    if (!empty($search_term)) {
        // Se c'è una ricerca attiva, mostra "Nessun risultato"
        $title = tr('Nessun risultato');
        $message = tr('La ricerca non ha prodotto risultati. Prova con termini diversi.');
    } else {
        // Se non c'è ricerca, mostra il messaggio originale
        $title = tr('Nessun impianto collegato');
        $message = tr('Utilizza il modulo sopra per aggiungere impianti a questa attività');
    }

    echo '
    <div class="alert alert-info text-center">
        <i class="fa fa-info-circle fa-2x mb-2"></i>
        <h5>'.$title.'</h5>
        <p class="mb-0">'.$message.'</p>
    </div>';

    return;
}
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
                            <td class="text-center">
                                <span class="badge badge-secondary">'.$impianto['matricola'].'</span>
                            </td>
                            <td>
                                '.Modules::link('Impianti', $impianto['id'], $impianto['nome']).'
                                '.(!empty($impianto['descrizione']) ? '<br><small class="text-muted">'.$impianto['descrizione'].'</small>' : '').'
                            </td>
                            <td class="text-center">
                                <small>'.Translator::dateToLocale($impianto['data']).'</small>
                            </td>
                            <td>
                                {[ "type": "textarea", "name": "note", "id": "note_imp_'.$impianto['id'].'", "value": "'.$impianto['note'].'", "placeholder": "'.tr('Aggiungi note').'...", "readonly": "'.!empty($readonly).'", "disabled": "'.!empty($disabled).'", "rows": 2 ]}
                            </td>
                            <td>';
    $inseriti = $dbo->fetchArray('SELECT * FROM my_componenti_interventi WHERE id_intervento = '.prepare($id_record));
    $ids = array_column($inseriti, 'id_componente');

    echo '
                                {[ "type": "select", "multiple": 1, "name": "componenti[]", "id": "componenti_imp_'.$impianto['id'].'", "ajax-source": "componenti", "select-options": {"matricola": '.$impianto['id'].'}, "value": "'.implode(',', $ids).'", "onchange": "updateImpianto($(this).closest(\'tr\').data(\'id\'))", "readonly": "'.!empty($readonly).'", "disabled": "'.!empty($disabled).'" ]}
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-danger '.$disabled.'" onclick="rimuoviImpianto($(this).closest(\'tr\').data(\'id\'))" title="'.tr('Rimuovi impianto').'">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>';

    // Checklist rimossa come richiesto
}
echo '
                    </tbody>
                </table>
            </div>
        </div>
    </div>';

// CSS rimosso come richiesto

echo '
<script>
$(document).ready(function() {
    init();
    initNoteAutoSave();
});

// Inizializza il salvataggio automatico delle note
function initNoteAutoSave() {
    let saveTimeout;

    // Gestione eventi per le note
    $(document).on("input blur", "textarea[id^=\'note_imp_\']", function() {
        const $textarea = $(this);
        const impiantoId = $textarea.closest("tr").data("id");

        if (!impiantoId) {
            console.error("ID impianto non trovato");
            return;
        }

        // Cancella il timeout precedente
        clearTimeout(saveTimeout);

        // Imposta nuovo timeout per il salvataggio usando la funzione esistente
        saveTimeout = setTimeout(function() {
            console.log("Auto-salvataggio nota per impianto:", impiantoId);
            updateImpianto(impiantoId);
        }, 1000); // Salva dopo 1 secondo di inattività
    });

    // Salvataggio immediato con Enter
    $(document).on("keydown", "textarea[id^=\'note_imp_\']", function(e) {
        if (e.key === "Enter" && !e.shiftKey) {
            e.preventDefault();
            const $textarea = $(this);
            const impiantoId = $textarea.closest("tr").data("id");

            if (!impiantoId) {
                console.error("ID impianto non trovato");
                return;
            }

            clearTimeout(saveTimeout);
            console.log("Salvataggio immediato nota per impianto:", impiantoId);
            updateImpianto(impiantoId);
        }
    });
}

// Funzione refreshChecklist rimossa - non più necessaria

function rimuoviImpianto(id) {
    // Mostra loading sulla riga
    var row = $("tr[data-id=\"" + id + "\"]");
    row.addClass("table-warning").find("td").append("<i class=\"fa fa-spinner fa-spin ml-2\"></i>");
    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "POST",
        dataType: "json",
        contentType: "application/x-www-form-urlencoded; charset=UTF-8",
        data: {
            id_module: globals.id_module,
            id_record: globals.id_record,
            id_plugin: '.$id_plugin.',
            op: "delete_impianto",
            id: id,
        },
        success: function (response) {
            if (response.status === "success") {
                // Animazione di rimozione - rimuovi solo la riga senza ricaricare
                row.fadeOut(300, function() {
                    row.remove();
                    renderMessages();
                });
            } else {
                // Errore dal server
                row.removeClass("table-warning").find(".fa-spinner").remove();
                renderMessages();
                caricaImpianti();
            }
        },
        error: function(xhr, status, error) {
            row.removeClass("table-warning").find(".fa-spinner").remove();
            renderMessages();
            caricaImpianti();
        }
    });
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
    launch_modal("Modifica checklist", "'.$checklist_module->fileurl('components/edit-check.php').'?id_module='.$id_module.'&id_plugin='.$id_plugin.'&id_record="+id, 1);
}

function saveNota(id) {
    $.post("'.$checklist_module->fileurl('ajax.php').'", {
        op: "save_note",
        note: $("#note_" + id).val(),
        id: id
    }, function() {
        renderMessages();
        content_was_modified = false;
        $("#note_" + id).closest("tr").find(".save-nota").removeClass("btn-success");
        $("#note_" + id).closest("tr").find(".save-nota").addClass("btn-default");
    });
}

function loadChecklist(id){
    const $loading = $("#loading-checks_" + id);
    const $checklist = $("#checklist_" + id);

    // Mostra loading e nasconde checklist esistente
    $loading.show();
    $checklist.empty();

    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "POST",
        data: {
            id_module: globals.id_module,
            id_plugin: '.$id_plugin.',
            id_record: globals.id_record,
            op: "load_checklist",
            id_impianto: id,
        },
        success: function (response) {
            $loading.hide();

            if (response && response.trim() !== "") {
                $checklist.html(response);
                init();

                // Mostra messaggio di successo temporaneo
                setTimeout(function() {
                    $checklist.prepend(\'<div class="alert alert-success alert-dismissible fade show mb-3" style="animation: fadeIn 0.5s;"><i class="fa fa-check mr-2"></i>\' + "'.tr('Checklist caricata con successo').'" + \'<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>\');

                    // Rimuovi automaticamente dopo 3 secondi
                    setTimeout(function() {
                        $checklist.find(".alert-success").fadeOut();
                    }, 3000);
                }, 100);
            } else {
                $checklist.html(\'<div class="alert alert-info text-center border-0"><i class="fa fa-info-circle fa-2x mb-2 text-info"></i><h6 class="text-info">\' + "'.tr('Nessuna checklist disponibile').'" + \'</h6><p class="mb-0 text-muted">\' + "'.tr('Non sono presenti checklist per questo impianto').'" + \'</p></div>\');
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
        },
        error: function(xhr, status, error) {
            $loading.hide();
            $checklist.html(\'<div class="alert alert-danger text-center border-0"><i class="fa fa-exclamation-triangle fa-2x mb-2 text-danger"></i><h6 class="text-danger">\' + "'.tr('Errore nel caricamento').'" + \'</h6><p class="mb-2 text-muted">\' + "'.tr('Impossibile caricare la checklist per questo impianto').'" + \'</p><button class="btn btn-outline-primary btn-sm" onclick="refreshChecklist(\' + id + \')"><i class="fa fa-refresh mr-1"></i>\' + "'.tr('Riprova').'" + \'</button></div>\');

            console.error("Errore caricamento checklist:", error);
            renderMessages();
        }
    });
}

// Funzione per migliorare la ricerca
function filtroImpianti() {
    var filtro = $("#input-cerca").val().toLowerCase();

    if (filtro === "") {
        $(".impianto-row").show();
        return;
    }

    $(".impianto-row").each(function() {
        var $row = $(this);
        var matricola = $row.find("td:nth-child(1)").text().toLowerCase();
        var nome = $row.find("td:nth-child(2)").text().toLowerCase();

        if (matricola.includes(filtro) || nome.includes(filtro)) {
            $row.show();
        } else {
            $row.hide();
        }
    });
}

// Aggiungi evento di ricerca in tempo reale
$(document).ready(function() {
    $("#input-cerca").on("input", filtroImpianti);
});
</script>';
