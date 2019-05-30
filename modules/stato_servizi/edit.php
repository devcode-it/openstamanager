<?php

// Elenco moduli installati
echo '
<div class="row">
    <div class="col-md-12 col-lg-6">
        <h3>'.tr('Moduli installati').'</h3>
        <table class="table table-hover table-bordered table-condensed">
            <tr>
                <th>'.tr('Nome').'</th>
                <th>'.tr('Versione').'</th>
                <th>'.tr('Stato').'</th>
                <th>'.tr('Compatibilità').'</th>
                <th>'.tr('Opzioni').'</th>
            </tr>';

$modules = Modules::getHierarchy();

$osm_version = Update::getVersion();

echo submodules($modules);

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
                <th>'.tr('Posizione').'</th>
                <th>'.tr('Stato').'</th>
                <th>'.tr('Posizione').'</th>
            </tr>';

$widgets = $dbo->fetchArray('SELECT zz_widgets.id, zz_widgets.name AS widget_name, zz_modules.name AS module_name, zz_widgets.enabled AS enabled, location, help FROM zz_widgets INNER JOIN zz_modules ON zz_widgets.id_module=zz_modules.id ORDER BY `id_module` ASC, `zz_widgets`.`order` ASC');

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
                <td>'.$widget['widget_name'].((!empty($widget['help'])) ? ' <i class="tip fa fa-question-circle-o" class="tip" title="'.$widget['help'].'"</i>' : '').'</td>
                <td align="left"><small>'.$location.'</small></td>
                <td align="center">'.$stato.'</td>
                <td align="center">'.$posizione.'</td>
            </tr>';

    $previous = $widget['module_name'];
}

echo '
        </table>
    </div>
</div>';
