<?php

include_once __DIR__.'/../core.php';

// Lettura parametri iniziali del modulo
if (!empty($id_plugin)) {
    $info = Plugins::getPlugin($id_plugin);

    if (!empty($info['script'])) {
        // Inclusione di eventuale plugin personalizzato
        if (file_exists($docroot.'/modules/'.$info['module_dir'].'/plugins/custom/'.$info['script'])) {
            include $docroot.'/modules/'.$info['module_dir'].'/plugins/custom/'.$info['script'];
        } elseif (file_exists($docroot.'/modules/'.$info['module_dir'].'/plugins/'.$info['script'])) {
            include $docroot.'/modules/'.$info['module_dir'].'/plugins/'.$info['script'];
        }

        return;
    }

    echo '
        <h4>
            '.$info['name'];

    if (file_exists($docroot.'/plugins/'.$info['directory'].'/add.php')) {
        echo '
        <button type="button" class="btn btn-primary" data-toggle="modal" data-title="'.tr('Aggiungi').'..." data-target="#bs-popup" data-href="add.php?id_module='.$id_module.'&id_plugin='.$id_plugin.'&id_parent='.$id_record.'"><i class="fa fa-plus"></i></button>';
    }

    echo '
    </h4>';

    $total = Plugins::getQuery($id_plugin);

    $directory = '/plugins/'.$info['directory'];
} else {
    $info = Modules::getModule($id_module);

    $total = Modules::getQuery($id_module);

    $directory = '/modules/'.$info['directory'];
}

$module_options = (!empty($info['options2'])) ? $info['options2'] : $info['options'];

// Caricamento file aggiuntivo su elenco record
if (file_exists($docroot.$directory.'/custom/controller_before.php')) {
    include $docroot.$directory.'/custom/controller_before.php';
} elseif (file_exists($docroot.$directory.'/controller_before.php')) {
    include $docroot.$directory.'/controller_before.php';
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

    $bulk = null;
    if (file_exists($docroot.$directory.'/custom/bulk.php')) {
        $bulk = include $docroot.$directory.'/custom/bulk.php';
    } elseif (file_exists($docroot.$directory.'/bulk.php')) {
        $bulk = include $docroot.$directory.'/bulk.php';
    }
    $bulk = (array) $bulk;

    echo '
    <div class="row" data-target="'.$table_id.'">
        <div class="col-xs-12 col-md-5">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-primary btn-select-all">'.tr('Seleziona tutto').'</button>
                <button type="button" class="btn btn-default btn-select-none">'.tr('Deseleziona tutto').'</button>
            </div>
        </div>

        <div class="col-xs-12 col-md-2 dropdown">';

    if (!empty($bulk)) {
        echo '
            <button class="btn btn-primary btn-block dropdown-toggle bulk-container disabled" type="button" data-toggle="dropdown" disabled>'.tr('Azioni di gruppo').' <span class="caret"></span></button>
            <ul class="dropdown-menu" data-target="'.$table_id.'" role="menu">';

        foreach ($bulk as $key => $value) {
            echo '
                <li role="presentation"><a class="bulk-action" data-op="'.$value.'">'.$key.'</a></li>';
        }

        echo '
            </ul>';
    }

        echo '
        </div>

        <div class="col-xs-12 col-md-5 text-right">
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
    // Inclusione elementi fondamentali del modulo
    include $docroot.'/actions.php';

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
