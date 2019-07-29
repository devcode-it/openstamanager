<?php

namespace Modules\Checklists\HTMLBuilder;

use HTMLBuilder\Manager\ManagerInterface;
use Models\User;
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
        $module = Modules::get('Checklists');
        $structure = Plugins::get($options['id_plugin']) ?: Modules::get($options['id_module']);

        $utente = \Auth::user();

        // ID del form
        $manager_id = 'checklist_'.$options['id_module'].'_'.$options['id_plugin'];

        $result = '
<div class="panel panel-primary" id="'.$manager_id.'" style="position:relative">
    <div class="panel-heading">
        <h3 class="panel-title">'.tr('Checklist').'</h3>
    </div>
    <div class="panel-body" style="position:relative">
        <div id="loading_'.$manager_id.'" class="text-center hide component-loader">
            <div>
                <i class="fa fa-refresh fa-spin fa-3x fa-fw"></i>
                <span class="sr-only">'.tr('Caricamento...').'</span>
            </div>
        </div>';

        // Form per la creazione di una nuova checklist
        if (!$options['readonly']) {
            $result .= '
        <div class="row">
            <div class="col-md-12">
                <a class="btn btn-sm btn-primary" data-href="'.$module->fileurl('components/add-check.php').'?id_module='.$options['id_module'].'&id_record='.$options['id_record'].'&id_plugin='.$options['id_plugin'].'&manager_id='.$manager_id.'" data-toggle="tooltip" data-title="'.tr('Aggiungi check').'">
                    <i class="fa fa-plus"></i> '.tr('Check').'
                </a>
                
                <a class="btn btn-sm btn-primary" data-href="'.$module->fileurl('components/add-checklist.php').'?id_module='.$options['id_module'].'&id_record='.$options['id_record'].'&id_plugin='.$options['id_plugin'].'&manager_id='.$manager_id.'" data-toggle="tooltip" data-title="'.tr('Aggiungi check').'">
                    <i class="fa fa-plus"></i> '.tr('Checklist').'
                </a>
            </div>
        </div>
        
        <div class="clearfix"></div>
        <br>';
        }

        $checks = $structure->mainChecks($options['id_record']);
        $users = $checks->groupBy('id_utente_assegnato');

        $result .= '
        <div class="row">';
        foreach ($users as $user_id => $checks) {
            $user = User::find($user_id);

            $result .= '
            <div class="col-md-6" '.($utente->id != $user_id ? 'style="opacity: 0.5"' : '').'>
                <div class="box box-info">
                    <div class="box-header">
                        <h3 class="box-title">'.$user->nome_completo.'</h3>
                    </div>
                
                    <div class="box-body">
                        <ul class="checklist">';

            foreach ($checks as $check) {
                $result .= self::renderChecklist($check);
            }

            $result .= '
                        </ul>
                    </div>
                </div>
            </div>';
        }

        $result .= '
        </div>
    </div>
</div>';

        $result .= '
<script>$(document).ready(init)</script>

<script type="module">
import Checklist from "./modules/checklists/js/checklist.js";

var checklists = checklists ? checklists : {};
$(document).ready(function() {
    checklists["'.$manager_id.'"] = new Checklist({
        id_module: "'.$options['id_module'].'",
        id_plugin: "'.$options['id_plugin'].'",
        id_record: "'.$options['id_record'].'",
    }, "'.$manager_id.'");
    
    $(".check-item").click(function(event){
        var id = $(this).attr("id").replace("check_", "");

        var result = checklists["'.$manager_id.'"].toggleCheck(id, '.$utente->id.');
        
        if (!result){
            swal("'.tr('Errore').'", "'.tr('La tua utenza non corrisponde a quella assegnata al check').'", "error");
        }
    });
    
    $(".check-delete").click(function(event){
        var li = $(this).closest("li");
        var id = li.attr("id").replace("check_", "");

        var result = checklists["'.$manager_id.'"].deleteCheck(id, '.$utente->id.');
        
        if (!result){
            swal("'.tr('Errore').'", "'.tr('La tua utenza non possiede i permessi di modificare questo check').'", "error");
        }
        
        event.stopPropagation();  
    });
});

function deleteCheck(id) {
    swal({
        title: "'.tr("Rimuovere l'elemento della checklist?").'",
        html: "'.tr('Tutti gli elementi figli saranno rimossi di conseguenza. Continuare?').'",
        showCancelButton: true,
        confirmButtonText: "'.tr('Procedi').'",
        type: "error",
    }).then(function (result) {
        checklists["'.$manager_id.'"].toggleCheck(id);
    });
}
</script>';

        return $result;
    }

    public static function renderChecklist($check, $level = 0)
    {
        $result = '
            <li id="check_'.$check->id.'" class="check-item'.(!empty($check->checked_at) ? ' checked' : '').'" data-user_id="'.$check->id_utente.'" data-assigned_user_id="'.$check->id_utente_assegnato.'">
                '.str_repeat('&nbsp;', $level * 8).'
                
                <i class="check-icon fa '.(!empty($check->checked_at) ? 'fa-check-square-o' : 'fa-square-o').'"></i>
                <span class="check-text">'.$check->content.'</span>
                
                <div class="pull-right">
                    <span class="badge check-date">'.timestampFormat($check->checked_at).'</span>
                    <i class="fa fa-close check-delete"></i>
                </div>
            </li>
                <ul class="checklist" class="check-children">';

        $children = $check->children;
        foreach ($children as $child) {
            $result .= self::renderChecklist($child, $level + 1);
        }

        $result .= '
                </ul>';

        return $result;
    }
}
