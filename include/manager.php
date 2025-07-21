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
use Models\Module;
use Models\Plugin;

include_once __DIR__.'/../core.php';

// Compatibilità per controller ed editor
if (!empty($id_plugin)) {
    $structure = Plugin::find($id_plugin);
} else {
    $structure = Module::find($id_module);
}

if (!empty($id_plugin)) {
    // Inclusione di eventuale plugin personalizzato
    if (!empty($structure['script'])) {
        $path = $structure->getEditFile();
        if (!empty($path)) {
            include $path;
        }

        return;
    }

    echo '
        <h4 class="plugin-title mb-3 d-flex align-items-center">
            <span class="badge badge-primary p-1 mr-2"><i class="fa fa-plug"></i></span>
			<span class="'.(!empty($structure['help']) ? ' tip' : '').'"'.(!empty($structure['help']) ? ' title="'.prepareToField($structure['help']).'" data-position="bottom"' : '').' >
            <strong class="text-primary">'.$structure->getTranslation('title').'</strong>'.(!empty($structure['help']) ? ' <i class="fa fa-question-circle-o"></i>' : '').'</span>';

    if ($structure->hasAddFile()) {
        echo '
        <div class="ml-auto">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-title="'.tr('Aggiungi').'..." data-href="add.php?id_module='.$id_module.'&id_plugin='.$id_plugin.'&id_parent='.$id_record.'"><i class="fa fa-plus"></i> '.tr('Nuovo').'</button>
        </div>';
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
    <div class="row justify-content-end">
        <div class="col-md-3">
            {[ "type": "select", "name": "id_segment_", "required": 0, "ajax-source": "segmenti", "select-options": '.json_encode(['id_module' => $id_module]).', "value": "'.$_SESSION['module_'.$id_module]['id_segment'].'" ]}
        </div>
    </div>';

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

    $table_id = 'main_'.random_int(0, 99);
    echo '
    <table data-idmodule="'.$id_module.'" data-idplugin="'.$id_plugin.'" data-idparent="'.$id_record.'" data-selected="'.implode(';', $selezione).'" id="'.$table_id.'" width="100%" class="table main-records table-hover table-striped '.(!empty($id_plugin) ? '-plugins' : '').'">
            <thead>
                <tr>
                    <th id="th_selector"></th>';

    foreach ($total['fields'] as $key => $field) {
        $attr_td = '';
        $name = trim((string) $field);

        // Check per tipologie di campi particolari
        if (preg_match('/^color_/', (string) $field)) {
            $attr_td .= " width='140'";
            $field = str_replace('color_', '', $field);
        }

        // Data (larghezza fissa)
        elseif (preg_match('/^Data/', (string) $field)) {
            $attr_td .= " width='100'";
        }

        // Immagine
        elseif (trim((string) $field) == '_img_') {
            $attr_td .= " width='30'";
            $field = str_replace('_img_', '', $field);
        }

        // Icona di stampa
        elseif (trim((string) $field) == '_print_') {
            $attr_td .= " width='30'";
            $field = str_replace('_print_', '', $field);
        } elseif (preg_match('/^icon_/', (string) $field)) {
            $attr_td .= " width='30'";
            $name = str_replace('icon_', 'icon_title_', $name);
            $field = str_replace('icon_', '', $field);
        } elseif (preg_match('/^mailto_/', (string) $field)) {
            $field = str_replace('mailto_', '', $field);
        } elseif (preg_match('/^tel_/', (string) $field)) {
            $field = str_replace('tel_', '', $field);
        }

        if (isMobile()) {
            $attr_td .= " style='min-width:100px;'";
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
    <div class="row datatables-buttons" data-target="'.$table_id.'">
        <div class="col-md-5">
            <div class="btn-group select-controller-container" role="group">
                <button type="button" class="btn btn-primary btn-select-all">'.tr('Seleziona tutto').'</button>
                <button type="button" class="btn btn-default btn-select-none">
                    '.tr('Deseleziona tutto').'
                    <span class="badge selected-count">0</span>
                </button>
            </div>
        </div>

        <div class="col-md-2 dropup">';
    if (!empty($bulk) && $structure->permission == 'rw' && empty($id_plugin)) {
        echo '
            <div class="btn-group">

                <button class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> '.tr('Azioni di gruppo').'   </button>
                <div class="dropdown-menu dropdown-menu-right">';

        foreach ($bulk as $key => $value) {
            $text = is_array($value) ? $value['text'] : $value;

            $data = isset($value['data']) && is_array($value['data']) ? $value['data'] : [];
            $extra = [];
            foreach ($data as $k => $v) {
                $extra[] = 'data-'.$k.'="'.prepareToField(HTMLBuilder::replace($v)).'"';
            }

            echo '
                <a class="bulk-action clickable dropdown-item" data-op="'.prepareToField($key).'" data-backto="record-list" '.implode(' ', $extra).'>'.$text.'</a>';
        }

        echo '
                </div>
            </div>';
    }
    echo '
            </div>

        <div class="col-md-5 text-right">
            <i class="fa fa-question-circle-o tip" title="'.tr('Le operazioni di esportazione, copia e stampa sono limitate alle righe selezionate e visibili della tabella').'. '.tr('Per azioni su tutti i contenuti selezionati, utilizzare le Azioni di gruppo').'."></i>
            <div class="btn-group export-container" role="group">';

    if (setting('Abilita esportazione Excel e PDF')) {
        echo '
                <div class="btn-group">
                    <button type="button" class="btn btn-primary table-btn btn-csv disabled" disabled>'.tr('Esporta').'</button>

                    <button type="button" class="btn btn-primary table-btn disabled dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="caret"></span>
                        <span class="sr-only">Toggle Dropdown</span>
                    </button>

                    <ul class="dropdown-menu">
                        <a class="table-btn btn-pdf disabled clickable dropdown-item" disabled>'.tr('PDF').'</a>

                        <a class="table-btn btn-excel disabled clickable dropdown-item" disabled>'.tr('Excel').'</a>
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
    $path = $structure->getEditFile();
    if (!empty($path)) {
        include $path;
    }
}

// Caricamento file aggiuntivo su elenco record
$controller_after = $structure->filepath('controller_after.php');
if (!empty($controller_after)) {
    include $controller_after;
}
