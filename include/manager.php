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

use HTMLBuilder\HTMLBuilder;

include_once __DIR__.'/../core.php';

// Compatibilità per controller ed editor
if (!empty($id_plugin)) {
    $structure = Plugins::get($id_plugin);
} else {
    $structure = Modules::get($id_module);
}

if (!empty($id_plugin)) {
    // Inclusione di eventuale plugin personalizzato
    if (!empty($structure['script'])) {
        include $structure->getEditFile();

        return;
    }

    echo '
        <h4>
			<span  class="'.(!empty($structure['help']) ? ' tip' : '').'"'.(!empty($structure['help']) ? ' title="'.prepareToField($structure['help']).'" data-position="bottom"' : '').' >
            '.$structure['title'].(!empty($structure['help']) ? ' <i class="fa fa-question-circle-o"></i>' : '').'</span>';

    if ($structure->hasAddFile()) {
        echo '
        <button type="button" class="btn btn-primary" data-toggle="modal" data-title="'.tr('Aggiungi').'..." data-href="add.php?id_module='.$id_module.'&id_plugin='.$id_plugin.'&id_parent='.$id_record.'"><i class="fa fa-plus"></i></button>';
    }

    echo '
    </h4>';
}

$type = $structure['option'];

// Lettura risultato query del modulo
// include $structure->filepath('init.php');

// Caricamento file aggiuntivo su elenco record
$controller_before = $structure->filepath('controller_before.php');
if (!empty($controller_before)) {
    include $controller_before;
}

/*
 * Datatables con record
 */
if (!empty($type) && $type != 'menu' && $type != 'custom') {
    $total = Util\Query::readQuery($structure);

    if (empty($id_plugin) && count(Modules::getSegments($id_module)) > 1) {
        echo '
    <div class="row">
    	<div class="col-md-4 pull-right">
    		{[ "type": "select", "name": "id_segment_", "required": 0, "values": "query=SELECT id, name AS descrizione FROM zz_segments WHERE id_module = '.prepare($id_module).'", "value": "'.$_SESSION['module_'.$id_module]['id_segment'].'" ]}
    	</div>
    </div>
    <br>';

        echo '
    <script>
    $(document).ready(function () {
    	$("#id_segment_").on("change", function(){
    		if ($(this).val() < 1){
    			session_set("module_'.$id_module.',id_segment", "", 1, 1);
    		} else {
    			session_set("module_'.$id_module.',id_segment", $(this).val(), 0, 1);
    		}
      });
    });
    </script>';
    }

    // Reset della selezione precedente
    $_SESSION['module_'.$id_module]['selected'] = [];
    $selezione = array_keys($_SESSION['module_'.$id_module]['selected']);

    $table_id = 'main_'.rand(0, 99);
    echo '
    <table data-idmodule="'.$id_module.'" data-idplugin="'.$id_plugin.'" data-idparent="'.$id_record.'" data-selected="'.implode(';', $selezione).'" id="'.$table_id.'" width="100%" class="main-records table table-condensed table-bordered">
        <thead>
            <tr>
                <th id="th_selector"></th>';

    foreach ($total['fields'] as $key => $field) {
        $attr_td = '';
        $name = trim($field);

        // Check per tipologie di campi particolari
        if (preg_match('/^color_/', $field)) {
            $attr_td .= " width='140'";
            $field = str_replace('color_', '', $field);
        }

        // Data (larghezza fissa)
        elseif (preg_match('/^Data/', $field)) {
            $attr_td .= " width='100'";
        }

        // Icona di stampa
        elseif (trim($field) == '_print_') {
            $attr_td .= " width='30'";
            $field = str_replace('_print_', '', $field);
        } elseif (preg_match('/^icon_/', $field)) {
            $attr_td .= " width='30'";
            $name = str_replace('icon_', 'icon_title_', $name);
            $field = str_replace('icon_', '', $field);
        }

        echo '
                <th'.$attr_td.' id="th_'.searchFieldName($name).'"';
        if ($total['search'][$key] == 1) {
            echo ' class="search"';
        } else {
            echo ' class="no-search"';
        }
        if ($total['slow'][$key] == 1) {
            echo ' data-slow="1"';
        }
        echo '>'.$field.'</th>';
    }

    echo '
            </tr>
        </thead>

        <tbody>
        </tbody>

        <tfoot>
            <tr>';
    echo '
                <td></td>';
    foreach ($total['fields'] as $key => $field) {
        echo '
                <td></td>';
    }
    echo '
            </tr>
        </tfoot>
    </table>';

    echo '
    <div class="row" data-target="'.$table_id.'">
        <div class="col-md-5">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-primary btn-select-all">'.tr('Seleziona tutto').'</button>
                <button type="button" class="btn btn-default btn-select-none">'.tr('Deseleziona tutto').'</button>
            </div>
        </div>

        <div class="col-md-2 dropdown">';

    if (!empty($bulk)) {
        echo '
            <button class="btn btn-primary btn-block dropdown-toggle bulk-container disabled" type="button" data-toggle="dropdown" disabled>'.tr('Azioni di gruppo').' <span class="caret"></span></button>
            <ul class="dropdown-menu" data-target="'.$table_id.'" role="menu">';

        foreach ($bulk as $key => $value) {
            $text = is_array($value) ? $value['text'] : $value;

            $data = isset($value['data']) && is_array($value['data']) ? $value['data'] : [];
            $extra = [];
            foreach ($data as $k => $v) {
                $extra[] = 'data-'.$k.'="'.prepareToField(HTMLBuilder::replace($v)).'"';
            }

            echo '
                <li role="presentation"><a class="bulk-action clickable" data-op="'.prepareToField($key).'" data-backto="record-list" '.implode(' ', $extra).'>'.$text.'</a></li>';
        }

        echo '
            </ul>';
    }

    echo '
        </div>

        <div class="col-md-5 text-right">
            <i class="fa fa-question-circle-o tip" title="'.tr('Le operazioni di esportazione, copia e stampa sono limitate alle righe selezionate e visibili della tabella').'. '.tr('Per azioni su tutti i contenuti selezionati, utilizzare le Azioni di gruppo').'."></i>
            <div class="btn-group" role="group">';

    if (setting('Abilita esportazione Excel e PDF')) {
        echo '
                <div class="btn-group">
                    <button type="button" class="btn btn-primary table-btn btn-csv disabled" disabled>'.tr('Esporta').'</button>

                    <button type="button" class="btn btn-primary  table-btn disabled dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="caret"></span>
                        <span class="sr-only">Toggle Dropdown</span>
                    </button>

                    <ul class="dropdown-menu">
                        <li><a class="table-btn btn-pdf disabled" disabled>'.tr('PDF').'</a></li>

                        <li><a class="table-btn btn-excel disabled" disabled>'.tr('Excel').'</a></li>
                    </ul>
                </div>';
    } else {
        echo '
            <button type="button" class="btn btn-primary table-btn btn-csv disabled" disabled>'.tr('Esporta').'</button>';
    }

    echo '

                <button type="button" class="btn btn-default table-btn btn-copy disabled" disabled>'.tr('Copia').'</button>

                <button type="button" class="btn btn-default table-btn btn-print disabled" disabled>'.tr('Stampa').'</button>
            </div>
        </div>
    </div>';
}

/*
 * Inclusione modulo personalizzato
 */
elseif ($type == 'custom') {
    include $structure->getEditFile();
}

// Caricamento file aggiuntivo su elenco record
$controller_after = $structure->filepath('controller_after.php');
if (!empty($controller_after)) {
    include $controller_after;
}
