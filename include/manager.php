<?php

include_once __DIR__.'/../core.php';

// Lettura parametri iniziali
if (!empty($id_plugin)) {
    $element = Plugins::get($id_plugin);

    $directory = '/plugins/'.$element['directory'];
} else {
    $element = Modules::get($id_module);

    $directory = '/modules/'.$element['directory'];
}

$php = App::filepath($directory.'|custom|', 'edit.php');
$html = App::filepath($directory.'|custom|', 'edit.html');
$element['edit_file'] = !empty($php) ? $php : $html;

if (!empty($id_plugin)) {
    // Inclusione di eventuale plugin personalizzato
    if (!empty($element['script'])) {
        include App::filepath('modules/'.$element['module_dir'].'/plugins|custom|', $element['script']);

        return;
    }

    echo '
        <h4>
			<span  class="'.(!empty($element['help']) ? ' tip' : '').'"'.(!empty($element['help']) ? ' title="'.prepareToField($element['help']).'" data-position="bottom"' : '').' >
            '.$element['title'].(!empty($element['help']) ? ' <i class="fa fa-question-circle-o"></i>' : '').'</span>';

    if (!empty(Plugins::filepath($id_plugin, 'add.php'))) {
        echo '
        <button type="button" class="btn btn-primary" data-toggle="modal" data-title="'.tr('Aggiungi').'..." data-target="#bs-popup" data-href="add.php?id_module='.$id_module.'&id_plugin='.$id_plugin.'&id_parent='.$id_record.'"><i class="fa fa-plus"></i></button>';
    }

    echo '
    </h4>';
}

$type = $element['option'];

// Caricamento helper modulo (verifico se ci sono helper personalizzati)
include_once App::filepath($directory.'|custom|', 'modutil.php');

// Lettura risultato query del modulo
// include App::filepath($directory.'|custom|', 'init.php');

// Caricamento file aggiuntivo su elenco record
include App::filepath($directory.'|custom|', 'controller_before.php');

/*
 * Datatables con record
 */
if (!empty($type) && $type != 'menu' && $type != 'custom') {
    $total = App::readQuery($element);

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
    			session_set("m'.$id_module.',id_segment", "", 1, 1);
    		} else {
    			session_set("m'.$id_module.',id_segment", $(this).val(), 0, 1);
    		}
      });
    });
    </script>';
    }

    $table_id = 'main_'.rand(0, 99);
    echo '
    <table data-idmodule="'.$id_module.'" data-idplugin="'.$id_plugin.'" data-idparent="'.$id_record.'" id="'.$table_id.'" width="100%" class="main-records table table-condensed table-bordered">
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
                <th'.$attr_td.' id="th_'.str_replace([' ', '.'], ['-', ''], $name).'"';
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

            $data = is_array($value) ? $value['data'] : [];
            $extra = [];
            foreach ($data as $k => $v) {
                $extra[] = 'data-'.$k.'="'.$v.'"';
            }

            echo '
                <li role="presentation"><a class="bulk-action clickable" data-op="'.$key.'" data-backto="record-list" '.implode(' ', $extra).'>'.$text.'</a></li>';
        }

        echo '
            </ul>';
    }

    echo '
        </div>

        <div class="col-md-5 text-right">
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
    include $element['edit_file'];
}

// Caricamento file aggiuntivo su elenco record
include App::filepath($directory.'|custom|', 'controller_after.php');
