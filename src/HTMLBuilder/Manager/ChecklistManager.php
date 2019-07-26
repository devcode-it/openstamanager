<?php

namespace HTMLBuilder\Manager;

use Models\Checklist;
use Modules;
use Plugins;

/**
 * Gestione delle checklist.
 *
 * @since 2.4.11
 */
class ChecklistManager implements ManagerInterface
{
    /**
     * Gestione "checklists".
     * Esempio: {( "name": "checklists", "id_module": "2", "id_record": "1", "readonly": "false" )}.
     *
     * @param array $options
     *
     * @return string
     */
    public function manage($options)
    {
        $module = Modules::get($options['id_module']);
        $plugin = Plugins::get($options['id_plugin']);
        $structure = isset($plugin) ? $plugin : $module;

        // ID del form
        $manager_id = 'checklist_'.$options['id_module'].'_'.$options['id_plugin'];
        $checklists = $structure->checklists($options['id_record']);

        $list = [];
        foreach ($checklists as $checklist) {
            $list[] = [
                'id' => $checklist->id,
                'text' => $checklist->content,
            ];
        }

        $result = '
    <div class="panel panel-primary" id="'.$manager_id.'">
        <div class="panel-heading">
            <h3 class="panel-title">'.tr('Checklist').'</h3>
        </div>
        <div class="panel-body">
            <div id="loading_'.$manager_id.'" class="text-center hide" style="position:relative;top:100px;z-index:2;opacity:0.5;">
                <i class="fa fa-refresh fa-spin fa-3x fa-fw"></i><span class="sr-only">'.tr('Caricamento...').'</span>
            </div>';

        // Form per la creazione di una nuova checklist
        if (!$options['readonly']) {
            $result .= '
            <div id="checklist-form" class="row">
                <div class="col-md-6">
                    {[ "type": "text", "placeholder": "'.tr('Contenuto').'", "name": "content", "class": "unblockable", "required": 1 ]}
                </div>
        
                <div class="col-md-3">
                    {[ "type": "select", "placeholder": "'.tr('Genitore').'", "name": "parent", "class": "unblockable", "values": '.json_encode($list).' ]}
                </div>
                
                <div class="col-md-2">
                    {[ "type": "select", "placeholder": "'.tr('Utente').'", "name": "assigned_user", "class": "unblockable", "ajax-source": "utenti", "required": 1 ]}
                </div>
        
                <div class="col-md-1 text-right">
                    <button type="button" class="btn btn-success" onclick="addCheck(this)">
                        <i class="fa fa-upload"></i> '.tr('Crea').'
                    </button>
                </div>
            </div>
            <hr>';
        }

        $result .= '
            <ul class="checklist">';

        foreach ($checklists as $checklist) {
            $result .= $this->renderChecklist($checklist);
        }

        $result .= '
            </ul>
        </div>
    </div>';

        $result .= '
<script>$(document).ready(init)</script>

<script>
$(document).ready(function() {
    $(".check-item").click(function(event){
        var id = $(this).attr("id").replace("check_", "");

        toggleCheck(id);
    });
    
    $(".check-delete").click(function(event){
        var li = $(this).closest("li");
        var id = li.attr("id").replace("check_", "");
        console.log(id);

        deleteCheck(id);
        event.stopPropagation();  
    });
});

var data = {
    id_module: "'.$options['id_module'].'",
    id_plugin: "'.$options['id_plugin'].'",
    id_record: "'.$options['id_record'].'",
};

function addCheck(btn) {
    $form = $(btn).closest("#checklist-form");
    
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
    
    show_loader_'.$manager_id.'()

    var info = JSON.parse(JSON.stringify(data));
    info.op = "add_check";
    
    $form.ajaxSubmit({
        url: globals.rootdir + "/actions.php",
        data: info,
        type: "post",
        success: function(data){
            reload_'.$manager_id.'();
        },
        error: function(data) {
            alert("'.tr('Errore').': " + data);
        }
    });
}

function deleteCheck(id) {
    swal({
        title: "'.tr("Rimuovere l'elemento della checklist?").'",
        html: "'.tr('Tutti gli elementi figli saranno rimossi di conseguenza. Continuare?').'",
        showCancelButton: true,
        confirmButtonText: "'.tr('Procedi').'",
        type: "error",
    }).then(function (result) {
        var check = findCheck(id);
        check.icon.removeClass("fa-check").addClass("fa-refresh fa-spin bg-danger").show();
    
        var info = JSON.parse(JSON.stringify(data));
        info.op = "delete_check";
        info.check_id = id;
        
        show_loader_'.$manager_id.'();
        
        $.ajax({
            url: globals.rootdir + "/actions.php",
            cache: false,
            type: "POST",
            data: info,
            success: function(data) {
                reload_'.$manager_id.'()
            }
        });
    });
}

function toggleCheck(id) {
    var check = findCheck(id);
    check.icon.removeClass("fa-check").addClass("fa-refresh fa-spin").show();
    
    var info = JSON.parse(JSON.stringify(data));
    info.op = "toggle_check";
    info.check_id = id;
    
    $.ajax({
        url: globals.rootdir + "/actions.php",
        cache: false,
        type: "POST",
        data: info,
        success: function(data) {
            if(!data) return;
            
            data = JSON.parse(data);
            check.icon.removeClass("fa-refresh fa-spin")

            check.date.text(data.checked_at);
            if (data.checked_at){
                check.item.addClass("checked");
                check.icon.addClass("fa-check")
            } else {
                check.item.removeClass("checked");
            }
        }
    });
}

function findCheck(id) {
    var li = $("#check_" + id);
    
    return {
        item: li,
        icon: li.find(".check-icon"),
        date: li.find(".check-date"),
        text: li.find(".check-text"),
        children: li.find(".check-children"),
    };
}

function show_loader_'.$manager_id.'() {
    $("#loading_'.$manager_id.'").removeClass("hide");
}

function reload_'.$manager_id.'() {
    $("#'.$manager_id.'").load(globals.rootdir + "/ajax.php?op=checklists&id_module='.$options['id_module'].'&id_record='.$options['id_record'].'&id_plugin='.$options['id_plugin'].'", function() {
        $("#loading_'.$manager_id.'").addClass("hide");
    });
}
</script>';

        return $result;
    }

    protected function renderChecklist(Checklist $checklist, $level = 0)
    {
        $result = '
            <li id="check_'.$checklist->id.'" class="check-item'.(!empty($checklist->checked_at) ? ' checked' : '').'">
                '.str_repeat('&nbsp;', $level * 8).'
                
                <i class="check-icon fa fa-check"></i>
                <span class="check-text">'.$checklist->content.'</span>
                
                <div class="pull-right">
                    <span class="badge check-date">'.timestampFormat($checklist->checked_at).'</span>
                    <i class="fa fa-close check-delete"></i>
                </div>
            </li>
                <ul class="checklist" class="check-children">';

        $children = $checklist->children;
        foreach ($children as $child) {
            $result .= $this->renderChecklist($child, $level + 1);
        }

        $result .= '
                </ul>';

        return $result;
    }
}
