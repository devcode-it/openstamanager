<?php

include_once __DIR__.'/../core.php';

// Lettura parametri iniziali del modulo
if (!empty($id_plugin)) {
    $element = Plugins::get($id_plugin);

    if (!empty($element['script'])) {
        // Inclusione di eventuale plugin personalizzato
        if (file_exists($docroot.'/modules/'.$element['module_dir'].'/plugins/custom/'.$element['script'])) {
            include $docroot.'/modules/'.$element['module_dir'].'/plugins/custom/'.$element['script'];
        } elseif (file_exists($docroot.'/modules/'.$element['module_dir'].'/plugins/'.$element['script'])) {
            include $docroot.'/modules/'.$element['module_dir'].'/plugins/'.$element['script'];
        }

        return;
    }

    echo '
        <h4>
            '.$element['name'];

    if (file_exists($docroot.'/plugins/'.$element['directory'].'/add.php')) {
        echo '
        <button type="button" class="btn btn-primary" data-toggle="modal" data-title="'.tr('Aggiungi').'..." data-target="#bs-popup" data-href="add.php?id_module='.$id_module.'&id_plugin='.$id_plugin.'&id_parent='.$id_record.'"><i class="fa fa-plus"></i></button>';
    }

    echo '
    </h4>';

    $directory = '/plugins/'.$element['directory'];
} else {
    $element = Modules::get($id_module);

    $directory = '/modules/'.$element['directory'];
}
$total = App::readQuery($element);

$module_options = (!empty($element['options2'])) ? $element['options2'] : $element['options'];

// Caricamento file aggiuntivo su elenco record
if (file_exists($docroot.$directory.'/custom/controller_before.php')) {
    include $docroot.$directory.'/custom/controller_before.php';
} elseif (file_exists($docroot.$directory.'/controller_before.php')) {
    include $docroot.$directory.'/controller_before.php';
}

// Segmenti
/*deve sempre essere impostato almeno un sezionale*/
if (empty($_SESSION['m'.$id_module]['id_segment'])) {
    $rs = $dbo->fetchArray('SELECT id  FROM zz_segments WHERE predefined = 1 AND id_module = '.prepare($id_module).'LIMIT 0,1');
    $_SESSION['m'.$id_module]['id_segment'] = $rs[0]['id'];
}

if (count($dbo->fetchArray("SELECT id FROM zz_segments WHERE id_module = \"$id_module\"")) > 1) {
?>

    <div class="row">
    	<div class="col-md-4 pull-right">
    		{[ "type": "select", "label": "", "name": "id_segment_", "required": 0, "class": "", "values": "query=SELECT id, name AS descrizione FROM zz_segments WHERE id_module = '<?php echo $id_module; ?>'", "value": "<?php echo $_SESSION['m'.$id_module]['id_segment']; ?>", "extra": "" ]}
    	</div>
    </div>
    <br>


    <script>
    $(document).ready(function () {

    	$("#id_segment_").on("change", function(){
    		
    		if ($(this).val()<1){
    			session_set('<?php echo 'm'.$id_module; ?>,id_segment', '', 1, 1);
    		}else{
    			session_set('<?php echo 'm'.$id_module; ?>,id_segment', $(this).val(), 0, 1);
    		}

      });

    });
    </script>

<?php
}

/*
 * Datatables con record
 */
if (!empty($module_options) && $module_options != 'menu' && $module_options != 'custom') {
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
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-primary btn-csv disabled" disabled>'.tr('Esporta').'</button>
                <button type="button" class="btn btn-default btn-copy disabled" disabled>'.tr('Copia').'</button>
                <button type="button" class="btn btn-default btn-print disabled" disabled>'.tr('Stampa').'</button>
            </div>
        </div>
    </div>';
}

/*
 * Inclusione modulo personalizzato
 */
elseif ($module_options == 'custom') {
    // Lettura template modulo (verifico se ci sono template personalizzati, altrimenti uso quello base)
    if (file_exists($docroot.$directory.'/custom/edit.php')) {
        include $docroot.$directory.'/custom/edit.php';
    } elseif (file_exists($docroot.$directory.'/custom/edit.html')) {
        include $docroot.$directory.'/custom/edit.html';
    } elseif (file_exists($docroot.$directory.'/edit.php')) {
        include $docroot.$directory.'/edit.php';
    } elseif (file_exists($docroot.$directory.'/edit.html')) {
        include $docroot.$directory.'/edit.html';
    }
}

// Caricamento file aggiuntivo su elenco record
if (file_exists($docroot.$directory.'/custom/controller_after.php')) {
    include $docroot.$directory.'/custom/controller_after.php';
} elseif (file_exists($docroot.$directory.'/controller_after.php')) {
    include $docroot.$directory.'/controller_after.php';
}
