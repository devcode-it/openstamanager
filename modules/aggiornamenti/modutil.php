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
            $stato = '<i class="fa fa-cog fa-spin text-success" data-toggle="tooltip" title="'.$text.'"></i>';
        } else {
            $stato = '<i class="fa fa-cog text-warning" data-toggle="tooltip" title="'.tr('Non abilitato').'"></i>';
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
            $compatible = '<i class="fa fa-check-circle text-success" data-toggle="tooltip" title="'.tr('Compatibile').'"></i>';
            ($sub['enabled']) ? $class = 'success' : $class = 'warning';
        } else {
            $compatible = '<i class="fa fa-warning text-danger" data-toggle="tooltip" title="'.tr('Non compatibile!').tr('Questo modulo è compatibile solo con le versioni').': '.$sub['compatibility'].'"></i>';
            $class = 'danger';
        }

        $result .= '
        <tr class="'.$class.'">
            <td><small>'.str_repeat('&nbsp;', $depth * 4).'- '.$sub['title'].'</small></td>
            <td align="right">'.$sub['version'].'</td>
            <td align="center">'.$stato.'</td>
            <td align="center">'.$compatible.'</td>';

        $result .= '
            <td>';

        // Possibilità di disinstallare solo se il modulo non è tra quelli predefiniti
        if (empty($sub['default'])) {
            $result .= "
                <a href=\"javascript:;\" data-toggle='tooltip' title=\"".tr('Disinstalla')."...\" onclick=\"if( confirm('".tr('Vuoi disinstallare questo modulo?').' '.tr('Tutti i dati salvati andranno persi!')."') ){ if( confirm('".tr('Sei veramente sicuro?')."') ){ $.post( '".ROOTDIR.'/actions.php?id_module='.$id_module."', { op: 'uninstall', id: '".$sub['id']."' }, function(response){ location.href='".ROOTDIR.'/controller.php?id_module='.$id_module."'; }); } }\">
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

/**
 * Controlla se il database presenta alcune sezioni personalizzate.
 *
 * @return array
 */
function customStructure()
{
    $results = [];

    $dirs = [
        'modules',
        'templates',
        'plugins',
    ];

    // Controlli di personalizzazione fisica
    foreach ($dirs as $dir) {
        $files = glob(DOCROOT.'/'.$dir.'/*/custom/*.{php,html}', GLOB_BRACE);
        foreach ($files as $file) {
            $file = str_replace(DOCROOT.'/', '', $file);
            $result = explode('/custom/', $file)[0];

            if (!in_array($result, $results)) {
                $results[] = $result;
            }
        }
    }

    return $results;
}

/**
 * Controlla se il database presenta alcune sezioni personalizzate.
 *
 * @return array
 */
function customTables()
{
    $tables = include DOCROOT.'/update/tables.php';

    $names = [];
    foreach ($tables as $table) {
        $names[] = prepare($table);
    }

    $database = database();

    $results = $database->fetchArray('SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '.prepare($database->getDatabaseName()).' AND TABLE_NAME NOT IN ('.implode(',', $names).") AND TABLE_NAME != 'updates'");

    return array_column($results, 'TABLE_NAME');
}

/**
 * Controlla se il database presenta alcune sezioni personalizzate.
 *
 * @return array
 */
function customDatabase()
{
    $database = database();
    $modules = $database->fetchArray("SELECT name, CONCAT('modules/', directory) AS directory FROM zz_modules WHERE options2 != ''");
    $plugins = $database->fetchArray("SELECT name, CONCAT('plugins/', directory) AS directory FROM zz_plugins WHERE options2 != ''");

    $results = array_merge($modules, $plugins);

    return $results;
}

function custom()
{
    $database_check = customDatabase();
    $structure_check = customStructure();

    $list = [];
    foreach ($database_check as $element) {
        $pos = array_search($element['directory'], $structure_check);

        $list[] = [
            'path' => $element['directory'],
            'database' => true,
            'directory' => $pos !== false,
        ];

        if ($pos !== false) {
            unset($structure_check[$pos]);
        }
    }

    foreach ($structure_check as $element) {
        $list[] = [
            'path' => $element,
            'database' => false,
            'directory' => true,
        ];
    }

    return $list;
}
