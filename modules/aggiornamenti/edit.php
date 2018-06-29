<?php

include_once __DIR__.'/../../core.php';

if (get_var('Attiva aggiornamenti')) {
    $alerts = [];

    if (!extension_loaded('zip')) {
        $alerts[tr('Estensione ZIP')] = tr('da abilitare');
    }

    $upload_max_filesize = ini_get('upload_max_filesize');
    $upload_max_filesize = str_replace(['k', 'M'], ['000', '000000'], $upload_max_filesize);
    // Dimensione minima: 16MB
    if ($upload_max_filesize < 16000000) {
        $alerts['upload_max_filesize'] = '16MB';
    }

    $post_max_size = ini_get('post_max_size');
    $post_max_size = str_replace(['k', 'M'], ['000', '000000'], $post_max_size);
    // Dimensione minima: 16MB
    if ($post_max_size < 16000000) {
        $alerts['post_max_size'] = '16MB';
    }

    if (!empty($alerts)) {
        echo '
<div class="alert alert-warning">
    <p>'.tr('Devi modificare il seguenti parametri del file di configurazione PHP (_FILE_) per poter caricare gli aggiornamenti', [
        '_FILE_' => '<b>php.ini</b>',
    ]).':<ul>';
        foreach ($alerts as $key => $value) {
            echo '
        <li><b>'.$key.'</b> = '.$value.'</li>';
        }
        echo '
    </ul></p>
</div>';
    }

    echo '
        <div class="row">';
    // Aggiornamento
    echo '
            <div class="col-md-6">
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title">'.tr('Carica un aggiornamento').'</h3>
                    </div>
                    <div class="box-body">
                        <form action="'.ROOTDIR.'/controller.php?id_module='.$id_module.'" method="post" enctype="multipart/form-data" class="form-inline" id="update">
                            <input type="hidden" name="op" value="upload">
                            <input type="hidden" name="type" value="update">

                            <label><input type="file" name="blob"></label>

                            <button type="button" class="btn btn-primary" onclick="if( confirm(\''.tr('Avviare la procedura?').'\') ){ $(\'#update\').submit(); }">
                                <i class="fa fa-upload"></i> '.tr('Carica').'...
                            </button>
                        </form>
                    </div>
                </div>
            </div>';

    // Nuovo modulo
    echo '
            <div class="col-md-6">
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title">'.tr('Carica un nuovo modulo').'</h3>
                    </div>
                    <div class="box-body">
                        <form action="'.ROOTDIR.'/controller.php?id_module='.$id_module.'" method="post" enctype="multipart/form-data" class="form-inline" id="module">
                            <input type="hidden" name="op" value="upload">
                            <input type="hidden" name="type" value="new">

                            <label><input type="file" name="blob"></label>
                            <button type="button" class="btn btn-primary" onclick="if( confirm(\''.tr('Avviare la procedura?').'\') ){ $(\'#module\').submit(); }">
                                <i class="fa fa-upload"></i> '.tr('Carica').'...
                            </button>
                        </form>
                    </div>
                </div>
            </div>';
    echo '
        </div>';
}

// Elenco moduli installati
echo '
<div class="row">
    <div class="col-md-12 col-lg-6">
        <h3>'.tr('Moduli installati').'</h3>
        <table class="table table-hover table-bordered table-condensed">
            <tr>
                <th>'.tr('Nome').'</th>
                <th width="50">'.tr('Versione').'</th>
                <th width="30">'.tr('Stato').'</th>
                <th width="30">'.tr('Compatibilità').'</th>
                <th width="20">'.tr('Opzioni').'</th>
            </tr>';

$modules = Modules::getHierarchy();

$osm_version = Update::getVersion();

foreach ($modules as $module) {
    // STATO
    if (!empty($module['enabled'])) {
        $text = tr('Abilitato');
        $text .= ($module['id'] != $id_module) ? '. '.tr('Clicca per disabilitarlo').'...' : '';
        $stato = '<i class="fa fa-cog fa-spin text-success" data-toggle="tooltip" title="'.$text.'"></i>';
    } else {
        $stato = '<i class="fa fa-cog text-warning" data-toggle="tooltip" title="'.tr('Non abilitato').'"></i>';
        $class = 'warning';
    }

    // Possibilità di disabilitare o abilitare i moduli tranne quello degli aggiornamenti
    if ($module['id'] != $id_module) {
        if ($module['enabled']) {
            $stato = "<a href='javascript:;' onclick=\"if( confirm('".tr('Disabilitare questo modulo?')."') ){ $.post( '".ROOTDIR.'/actions.php?id_module='.$id_module."', { op: 'disable', id: '".$module['id']."' }, function(response){ location.href='".ROOTDIR.'/controller.php?id_module='.$id_module."'; }); }\">".$stato."</a>\n";
        } else {
            $stato = "<a href='javascript:;' onclick=\"if( confirm('".tr('Abilitare questo modulo?')."') ){ $.post( '".ROOTDIR.'/actions.php?id_module='.$id_module."', { op: 'enable', id: '".$module['id']."' }, function(response){ location.href='".ROOTDIR.'/controller.php?id_module='.$id_module."'; }); }\"\">".$stato."</a>\n";
        }
    }

    // COMPATIBILITA'
    $compatibilities = explode(',', $module['compatibility']);
    // Controllo per ogni versione se la regexp combacia per dire che è compatibile o meno
    $comp = false;
    foreach ($compatibilities as $compatibility) {
        $comp = (preg_match('/'.$compatibility.'/', $osm_version)) ? true : $comp;
    }

    if ($comp) {
        $compatible = '<i class="fa fa-check-circle text-success" data-toggle="tooltip" title="'.tr('Compatibile').'"></i>';
        ($module['enabled']) ? $class = 'success' : $class = 'warning';
    } else {
        $compatible = '<i class="fa fa-warning text-danger" data-toggle="tooltip" title="'.tr('Non compatibile!').tr('Questo modulo è compatibile solo con le versioni').': '.$module['compatibility'].'"></i>';
        $class = 'danger';
    }

    echo '
            <tr class="'.$class.'">
                <td>'.$module['name'].'</td>
                <td align="right">'.$module['version'].'</td>
                <td align="center">'.$stato.'</td>
                <td align="center">'.$compatible.'</td>';

    echo '
                <td>';

    // Possibilità di disinstallare solo se il modulo non è tra quelli predefiniti
    if (empty($module['default'])) {
        echo "
                    <a href=\"javascript:;\" data-toggle='tooltip' title=\"".tr('Disinstalla')."...\" onclick=\"if( confirm('".tr('Vuoi disinstallare questo modulo?').' '.tr('Tutti i dati salvati andranno persi!')."') ){ if( confirm('".tr('Sei veramente sicuro?')."') ){ $.post( '".ROOTDIR.'/actions.php?id_module='.$id_module."', { op: 'uninstall', id: '".$module['id']."' }, function(response){ location.href='".ROOTDIR.'/controller.php?id_module='.$id_module."'; }); } }\"><i class='fa fa-trash'></i></a>";
    } else {
        echo "
                    <a class='disabled text-muted'>
                        <i class='fa fa-trash'></i>
                    </a>";
    }

    echo '
                </td>
            </tr>';

    // Prima di cambiare modulo verifico se ci sono sottomoduli
    echo submodules($module['children']);
}

echo '
        </table>
    </div>';

// Widgets
echo '
    <div class="col-md-12 col-lg-6">
        <h3>'.tr('Widgets').'</h3>
        <table class="table table-hover table-bordered table-condensed">
            <tr>
                <th>'.tr('Nome').'</th>
                <th width="200">'.tr('Posizione').'</th>
                <th width="30">'.tr('Stato').'</th>
                <th width="30">'.tr('Posizione').'</th>
            </tr>';

$widgets = $dbo->fetchArray('SELECT zz_widgets.id, zz_widgets.name AS widget_name, zz_modules.name AS module_name, zz_widgets.enabled AS enabled, location FROM zz_widgets INNER JOIN zz_modules ON zz_widgets.id_module=zz_modules.id ORDER BY `id_module` ASC, `zz_widgets`.`order` ASC');

$previous = '';

foreach ($widgets as $widget) {
    // Nome modulo come titolo sezione
    if ($widget['module_name'] != $previous) {
        echo '
            <tr>
                <th colspan="4">'.$widget['module_name'].'</th>
            </tr>';
    }

    // STATO
    if ($widget['enabled']) {
        $stato = '<i class="fa fa-cog fa-spin text-success" data-toggle="tooltip" title="'.tr('Abilitato').'. '.tr('Clicca per disabilitarlo').'..."></i>';
        $class = 'success';
    } else {
        $stato = '<i class="fa fa-cog text-warning" data-toggle="tooltip" title="'.tr('Non abilitato').'"></i>';
        $class = 'warning';
    }

    // Possibilità di disabilitare o abilitare i moduli tranne quello degli aggiornamenti
    if ($widget['enabled']) {
        $stato = "<a href='javascript:;' onclick=\"if( confirm('".tr('Disabilitare questo widget?')."') ){ $.post( '".ROOTDIR.'/actions.php?id_module='.$id_module."', { op: 'disable_widget', id: '".$widget['id']."' }, function(response){ location.href='".ROOTDIR.'/controller.php?id_module='.$id_module."'; }); }\">".$stato."</a>\n";
    } else {
        $stato = "<a href='javascript:;' onclick=\"if( confirm('".tr('Abilitare questo widget?')."') ){ $.post( '".ROOTDIR.'/actions.php?id_module='.$id_module."', { op: 'enable_widget', id: '".$widget['id']."' }, function(response){ location.href='".ROOTDIR.'/controller.php?id_module='.$id_module."'; }); }\"\">".$stato."</a>\n";
    }

    // POSIZIONE
    if ($widget['location'] == 'controller_top') {
        $location = tr('Schermata modulo in alto');
    } elseif ($widget['location'] == 'controller_right') {
        $location = tr('Schermata modulo a destra');
    }

    if ($widget['location'] == 'controller_right') {
        $posizione = "<i class='fa fa-arrow-up text-warning' data-toggle='tooltip' title=\"".tr('Clicca per cambiare la posizione...')."\"></i>&nbsp;<i class='fa fa-arrow-right text-success' data-toggle='tooltip' title=\"\"></i>";
        $posizione = "<a href='javascript:;' onclick=\"if( confirm('".tr('Cambiare la posizione di questo widget?')."') ){ $.post( '".ROOTDIR.'/actions.php?id_module='.$id_module."', { op: 'change_position_widget_top', id: '".$widget['id']."' }, function(response){ location.href='".ROOTDIR.'/controller.php?id_module='.$id_module."'; }); }\"\">".$posizione."</a>\n";
    } elseif ($widget['location'] == 'controller_top') {
        $posizione = "<i class='fa fa-arrow-up text-success' data-toggle='tooltip' title=\"\"></i>&nbsp;<i class='fa fa-arrow-right text-warning' data-toggle='tooltip' title=\"".tr('Clicca per cambiare la posizione...').'"></i></i>';
        $posizione = "<a href='javascript:;' onclick=\"if( confirm('".tr('Cambiare la posizione di questo widget?')."') ){ $.post( '".ROOTDIR.'/actions.php?id_module='.$id_module."', { op: 'change_position_widget_right', id: '".$widget['id']."' }, function(response){ location.href='".ROOTDIR.'/controller.php?id_module='.$id_module."'; }); }\"\">".$posizione."</a>\n";
    }

    echo '
            <tr class="'.$class.'">
                <td>'.$widget['widget_name'].'</td>
                <td align="right"><small>'.$location.'</small></td>
                <td align="center">'.$stato.'</td>
                <td align="center">'.$posizione.'</td>
            </tr>';

    $previous = $widget['module_name'];
}

echo '
        </table>
    </div>
</div>';
