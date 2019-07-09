<?php

function submodules($list, $depth = 0)
{
    $osm_version = Update::getVersion();

    $id_module = Modules::getCurrent()['id'];

    $result = '';

    foreach ($list as $sub) {
        // STATO
        if (!empty($sub['enabled'])) {
            $text = tr('Abilitato');
            $text .= ($sub['id'] != $id_module) ? '. '.tr('Clicca per disabilitarlo').'...' : '';
            $stato = '<i class="fa fa-cog fa-spin text-success tip" title="'.$text.'"></i>';
        } else {
            $stato = '<i class="fa fa-cog text-warning tip" title="'.tr('Non abilitato').'"></i>';
            $class = 'warning';
        }

        // Possibilità di disabilitare o abilitare i moduli tranne quello degli aggiornamenti
        if ($sub['id'] != $id_module) {
            if ($sub['enabled']) {
                $stato = "<a href='javascript:;' onclick=\"if( confirm('".tr('Disabilitare questo modulo?')."') ){ $.post( '".ROOTDIR.'/actions.php?id_module='.$id_module."', { op: 'disable', id: '".$sub['id']."' }, function(response){ location.href='".ROOTDIR.'/controller.php?id_module='.$id_module."'; }); }\">".$stato."</a>\n";
            } else {
                $stato = "<a href='javascript:;' onclick=\"if( confirm('".tr('Abilitare questo modulo?')."') ){ $.post( '".ROOTDIR.'/actions.php?id_module='.$id_module."', { op: 'enable', id: '".$sub['id']."' }, function(response){ location.href='".ROOTDIR.'/controller.php?id_module='.$id_module."'; }); }\"\">".$stato."</a>\n";
            }
        }

        // COMPATIBILITA'
        // Controllo per ogni versione se la regexp combacia per dire che è compatibile o meno
        $compatibilities = explode(',', $sub['compatibility']);

        $comp = false;
        foreach ($compatibilities as $compatibility) {
            $comp = (preg_match('/'.$compatibility.'/', $osm_version)) ? true : $comp;
        }

        if ($comp) {
            $compatible = '<i class="fa fa-check-circle text-success tip" title="'.tr('Compatibile').'"></i>';
            ($sub['enabled']) ? $class = 'success' : $class = 'warning';
        } else {
            $compatible = '<i class="fa fa-warning text-danger tip"  title="'.tr('Non compatibile!').' '.tr('Questo modulo è compatibile solo con le versioni').': '.$sub['compatibility'].'"></i>';
            $class = 'danger';
        }

        $result .= '
        <tr class="'.$class.'">
            <td><small>'.str_repeat('&nbsp;', $depth * 4).'- '.$sub['title'].'</small></td>
            <td align="left">'.$sub['version'].'</td>
            <td align="center">'.$stato.'</td>
            <td align="center">'.$compatible.'</td>';

        $result .= '
            <td  align="center">';

        // Possibilità di disinstallare solo se il modulo non è tra quelli predefiniti
        if (empty($sub['default'])) {
            $result .= "
                <a href=\"javascript:;\" class=\"tip\"  title=\"".tr('Disinstalla')."...\" onclick=\"if( confirm('".tr('Vuoi disinstallare questo modulo?').' '.tr('Tutti i dati salvati andranno persi!')."') ){ if( confirm('".tr('Sei veramente sicuro?')."') ){ $.post( '".ROOTDIR.'/actions.php?id_module='.$id_module."', { op: 'uninstall', id: '".$sub['id']."' }, function(response){ location.href='".ROOTDIR.'/controller.php?id_module='.$id_module."'; }); } }\">
                    <i class='fa fa-trash'></i>
                </a>";
        } else {
            $result .= "
                <a class='disabled text-muted'>
                    <i class='fa fa-trash'></i>
                </a>";
        }

        $result .= '
            </td>
        </tr>';

        $result .= submodules($sub['all_children'], $depth + 1);
    }

    return $result;
}
