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

include_once __DIR__.'/../../core.php';

use API\Services;
use Models\Cache;
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

                $uninstall_script = base_dir().'/modules/'.$module_dir.'/update/uninstall.php';

                if (file_exists($uninstall_script)) {
                    include_once $uninstall_script;
                }

                delete(base_dir().'/modules/'.$module_dir.'/');

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
            base_dir().'/files' => tr('Allegati'),
            base_dir().'/logs' => tr('Logs'),
        ];

        foreach ($dirs as $dir => $description) {
            $size = FileSystem::folderSize($dir, ['htaccess']);

            $results[] = [
                'description' => $description,
                'size' => $size,
                'formattedSize' => FileSystem::formatBytes($size),
                'count' => FileSystem::fileCount($dir, ['htaccess']) ?: 0,
                'dbSize' => ($description == 'Allegati') ? $dbo->fetchOne('SELECT SUM(`size`) AS dbsize FROM zz_files')['dbsize'] : '',
                'dbCount' => ($description == 'Allegati') ? $dbo->fetchOne('SELECT COUNT(`id`) AS dbcount FROM zz_files')['dbcount'] : '',
                'dbExtensions' => ($description == 'Allegati') ? $dbo->fetchArray("SELECT SUBSTRING_INDEX(filename, '.', -1) AS extension, COUNT(*) AS num FROM zz_files GROUP BY extension ORDER BY num DESC LIMIT 10") : '',
            ];
        }

        echo json_encode($results);

        break;

    case 'informazioni-fe':
        $info = Cache::pool('Informazioni su spazio FE');
        if (!$info->isValid()) {
            $response = Services::request('POST', 'informazioni_fe');
            $response = Services::responseBody($response);

            $info->set($response['result']);
        }

        $informazioni = $info->content;

        // Formattazione dei contenuti
        $history = (array) $informazioni['history'];
        foreach ($history as $key => $value) {
            $history[$key]['size'] = Filesystem::formatBytes($value['size']);
        }

        echo json_encode([
            'invoice_number' => $informazioni['invoice_number'],
            'size' => Filesystem::formatBytes($informazioni['size']),
            'history' => $history,
        ]);
        break;
}
