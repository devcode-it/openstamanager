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

switch (filter('op')) {
    case 'getfile':
        $number = filter('number');
        $number = intval($number);

        $backups = Backup::getList();
        $backup = $backups[$number];
        $filename = basename((string) $backup);

        download($backup, $filename);

        break;

    case 'del':
        $number = filter('number');
        $number = intval($number);

        $backups = Backup::getList();
        $backup = $backups[$number];
        $filename = basename((string) $backup);

        delete($backup);

        if (!file_exists($backup)) {
            flash()->info(tr('Backup _FILE_ eliminato!', [
                '_FILE_' => '"'.$filename.'"',
            ]));
        } else {
            flash()->error(tr("Errore durante l'eliminazione del backup _FILE_!", [
                '_FILE_' => '"'.$filename.'"',
            ]));
        }

        break;

    case 'backup':
        $ignores = ['dirs' => [], 'files' => []];

        if (filter('exclude') == 'exclude_attachments') {
            $ignores = ['dirs' => ['files']];
        } elseif (filter('exclude') == 'only_database') {
            $ignores = ['dirs' => ['vendor', 'update', 'templates', 'src', 'plugins', 'modules', 'logs', 'locale', 'lib', 'include', 'files', 'config', 'assets', 'api'], 'files' => ['*.php', '*.md', '*.json', '*.js', '*.xml', '.*']];
        }

        try {
            $result = Backup::create($ignores);

            if ($result) {
                flash()->info(tr('Nuovo backup creato correttamente!'));
            } else {
                $backup_dir = Backup::getDirectory();
                flash()->error(tr('Errore durante la creazione del backup!').' '.str_replace('_DIR_', '"'.$backup_dir.'"', tr('Verifica che la cartella _DIR_ abbia i permessi di scrittura!')));
            }
        } catch (Exception $e) {
            flash()->error(tr('Errore durante la creazione del backup!').' '.$e->getMessage());
        }

        break;

    case 'size':
        $number = filter('number');
        $number = intval($number);

        $backups = Backup::getList();
        $backup = $backups[$number] ?: Backup::getDirectory();

        echo Util\FileSystem::size($backup);

        break;
}

if (filter('op') == 'restore') {
    if (!extension_loaded('zip')) {
        flash()->error(tr('Estensione zip non supportata!').'<br>'.tr('Verifica e attivala sul tuo file _FILE_', [
            '_FILE_' => '<b>php.ini</b>',
        ]));

        return;
    }

    $number = filter('number');
    if ($number === null) {
        $path = $_FILES['blob']['tmp_name'];
    } else {
        $number != '' ? $number : 0;
        $number = intval($number);

        $backups = Backup::getList();
        $path = $backups[$number];
    }

    try {
        // Ottieni la password per i backup esterni se impostata
        $password = setting('Password backup esterni');

        $result = Backup::restore($path, is_file($path), $password);
        $database->beginTransaction();

        if ($result) {
            flash()->warning(tr('Ripristino eseguito correttamente!'));
        } else {
            flash()->error(tr('Errore durante il ripristino del backup!').'<br>'.$result);
        }
    } catch (Exception $e) {
        flash()->error(tr('Errore durante il ripristino del backup!').' '.$e->getMessage());
    }
}
