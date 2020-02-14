<?php

include_once __DIR__.'/../../core.php';

use Util\FileSystem;

$id = post('id');

switch (filter('op')) {
    case 'uninstall':
        if (!empty($id)) {
            // Leggo l'id del modulo
            $rs = $dbo->fetchArray('SELECT id, name, directory FROM zz_modules WHERE id='.prepare($id).' AND `default`=0');
            $modulo = $rs[0]['title'];
            $module_dir = $rs[0]['directory'];

            if (count($rs) == 1) {
                // Elimino il modulo dal menu
                $dbo->query('DELETE FROM zz_modules WHERE id='.prepare($id).' OR parent='.prepare($id));

                $uninstall_script = DOCROOT.'/modules/'.$module_dir.'/update/uninstall.php';

                if (file_exists($uninstall_script)) {
                    include_once $uninstall_script;
                }

                delete(DOCROOT.'/modules/'.$module_dir.'/');

                flash()->info(tr('Modulo "_MODULE_" disinstallato!', [
                    '_MODULE_' => $modulo,
                ]));
            }
        }

        break;

    case 'disable':
        $dbo->query('UPDATE `zz_modules` SET `enabled` = 0 WHERE (`id` = '.prepare($id).' OR `parent` = '.prepare($id).') AND `id` != '.prepare(Modules::get('Stato dei servizi')['id']));

        flash()->info(tr('Modulo "_MODULE_" disabilitato!', [
            '_MODULE_' => Modules::get($id)['title'],
        ]));

        break;

    case 'enable':
        $dbo->query('UPDATE `zz_modules` SET `enabled` = 1 WHERE `id` = '.prepare($id).' OR `parent` = '.prepare($id));

        flash()->info(tr('Modulo "_MODULE_" abilitato!', [
            '_MODULE_' => Modules::get($id)['title'],
        ]));

        break;

    case 'disable_widget':
        $dbo->query('UPDATE zz_widgets SET enabled = 0 WHERE id = '.prepare($id));

        $rs = $dbo->fetchArray('SELECT id, name FROM zz_widgets WHERE id='.prepare($id));
        $widget = $rs[0]['name'];

        flash()->info(tr('Widget "_WIDGET_" disabilitato!', [
            '_WIDGET_' => $widget,
        ]));

        break;

    case 'enable_widget':
        $dbo->query('UPDATE zz_widgets SET enabled=1 WHERE id='.prepare($id));

        $rs = $dbo->fetchArray('SELECT id, name FROM zz_widgets WHERE id='.prepare($id));
        $widget = $rs[0]['name'];

        flash()->info(tr('Widget "_WIDGET_" abilitato!', [
            '_WIDGET_' => $widget,
        ]));

        break;

    case 'change_position_widget_top':
        $dbo->query("UPDATE zz_widgets SET location='controller_top' WHERE id=".prepare($id));

        $rs = $dbo->fetchArray('SELECT id, name FROM zz_widgets WHERE id='.prepare($id));
        $widget = $rs[0]['name'];

        flash()->info(tr('Posizione del widget "_WIDGET_" aggiornata!', [
            '_WIDGET_' => $widget,
        ]));

        break;

    case 'change_position_widget_right':
        $dbo->query("UPDATE zz_widgets SET location='controller_right' WHERE id=".prepare($id));

        $rs = $dbo->fetchArray('SELECT id, name FROM zz_widgets WHERE id='.prepare($id));
        $widget = $rs[0]['name'];

        flash()->info(tr('Posizione del widget "_WIDGET_" aggiornata!', [
            '_WIDGET_' => $widget,
        ]));

        break;

    // Ordinamento moduli di primo livello
    case 'sort_modules':
        $rs = $dbo->fetchArray('SELECT id FROM zz_modules WHERE enabled = 1 AND parent IS NULL ORDER BY `order` ASC');

        if ($_POST['ids'] != implode(',', array_column($rs, 'id'))) {
            $ids = explode(',', $_POST['ids']);

            for ($i = 0; $i < count($ids); ++$i) {
                $dbo->query('UPDATE zz_modules SET `order`='.prepare($i).' WHERE id='.prepare($ids[$i]));
            }

            flash()->info(tr('Posizione delle voci di menù aggiornata!'));
        }

        break;

    case 'sort_widgets':
        $location = post('location');
        $id_module_widget = post('id_module_widget');

        $location = empty($id_record) ? 'controller_'.$location : 'editor_'.$location;

        $rs = $dbo->fetchArray("SELECT CONCAT('widget_', id) AS id FROM zz_widgets WHERE enabled = 1 AND location = ".prepare($location).' AND id_module = '.prepare($id_module_widget).' ORDER BY `order` ASC');

        if ($_POST['ids'] != implode(',', array_column($rs, 'id'))) {
            $ids = explode(',', $_POST['ids']);

            for ($i = 0; $i < count($ids); ++$i) {
                $id = explode('_', $ids[$i]);
                $dbo->query('UPDATE zz_widgets SET `order`='.prepare($i).' WHERE id='.prepare($id[1]));
            }

            flash()->info(tr('Posizioni widgets aggiornate!'));
        }
        break;

    case 'sizes':
        $results = [];

        $backup_dir = App::getConfig()['backup_dir'];

        $dirs = [
            $backup_dir => tr('Backup'),
            DOCROOT.'/files' => tr('Allegati'),
            DOCROOT.'/logs' => tr('Logs'),
        ];

        foreach ($dirs as $dir => $description) {
            $size = FileSystem::folderSize($dir);

            $results[] = [
                'description' => $description,
                'size' => $size,
                'formattedSize' => FileSystem::formatBytes($size),
            ];
        }

        echo json_encode($results);

        break;
}
