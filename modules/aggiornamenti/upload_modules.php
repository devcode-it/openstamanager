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

use Util\Zip;

if (!setting('Attiva aggiornamenti')) {
    exit(tr('Accesso negato'));
}

if (!extension_loaded('zip')) {
    flash()->error(tr('Estensione zip non supportata!').'<br>'.tr('Verifica e attivala sul tuo file _FILE_', [
        '_FILE_' => '<b>php.ini</b>',
    ]));

    return;
}

$extraction_dir = Zip::extract($_FILES['blob']['tmp_name']);

// Aggiornamento del progetto
if (file_exists($extraction_dir.'/VERSION')) {
    // Salva il file di configurazione
    $config = file_get_contents(base_dir().'/config.inc.php');

    // Copia i file dalla cartella temporanea alla root
    copyr($extraction_dir, base_dir());

    // Ripristina il file di configurazione dell'installazione
    file_put_contents(base_dir().'/config.inc.php', $config);
} else {
    $finder = Symfony\Component\Finder\Finder::create()
        ->files()
        ->ignoreDotFiles(true)
        ->ignoreVCS(true)
        ->in($extraction_dir);

    $files = $finder->name('MODULE')->name('PLUGIN');

    foreach ($files as $file) {
        // Informazioni dal file di configurazione
        $info = Util\Ini::readFile($file->getRealPath());

        // Informazioni aggiuntive per il database
        $insert = [];

        // Modulo
        if (basename($file->getRealPath()) == 'MODULE') {
            $directory = 'modules';
            $table = 'zz_modules';

            $installed = Modules::get($info['name']);
            $insert['parent'] = Modules::get($info['parent'])['id'];
            $insert['icon'] = $info['icon'];
        }

        // Plugin
        elseif (basename($file->getRealPath()) == 'PLUGIN') {
            $directory = 'plugins';
            $table = 'zz_plugins';

            $installed = Plugins::get($info['name']);
            $insert['idmodule_from'] = Modules::get($info['module_from'])['id'];
            $insert['idmodule_to'] = Modules::get($info['module_to'])['id'];
            $insert['position'] = $info['position'];
        }

        // Copia dei file nella cartella relativa
        copyr(dirname($file->getRealPath()), base_dir().'/'.$directory.'/'.$info['directory']);

        // Eventuale registrazione nel database
        if (empty($installed)) {
            $dbo->insert($table, array_merge($insert, [
                'name' => $info['name'],
                'title' => !empty($info['title']) ? $info['title'] : $info['name'],
                'directory' => $info['directory'],
                'options' => $info['options'],
                'version' => $info['version'],
                'compatibility' => $info['compatibility'],
                'order' => 100,
                'default' => 0,
                'enabled' => 1,
            ]));

            flash()->error(tr('Installazione completata!'));
        } else {
            flash()->error(tr('Aggiornamento completato!'));
        }
    }
}

// Rimozione delle risorse inutilizzate
delete($extraction_dir);

// Redirect
redirect(base_path().'/editor.php?id_module='.$id_module);
