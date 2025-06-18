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

use Models\Module;
use Models\Plugin;
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
    
    // Rinomina la cartella vendor per evitare conflitti
    if (is_dir(base_dir().'/vendor')) {
        copyr(base_dir().'/vendor', base_dir().'/vendor.old');
        copyr($extraction_dir.'/vendor', base_dir().'/vendor.new');
    }

    // Copia i file dalla cartella temporanea alla root
    copyr($extraction_dir, base_dir());
    delete(base_dir().'/vendor');
    rename(base_dir().'/vendor.new', base_dir().'/vendor');
    delete(base_dir().'/vendor.old');

    // Ripristina il file di configurazione dell'installazione
    file_put_contents(base_dir().'/config.inc.php', $config);
} else {
    $finder = Symfony\Component\Finder\Finder::create()
        ->files()
        ->ignoreDotFiles(true)
        ->ignoreVCS(true)
        ->in($extraction_dir);

    $files_module = $finder->name('MODULE');

    foreach ($files_module as $file) {
        // Informazioni dal file di configurazione
        $info = Util\Ini::readFile($file->getRealPath());

        // Informazioni aggiuntive per il database
        $insert = [];

        // Modulo
        if (basename($file->getRealPath()) == 'MODULE') {
            $directory = 'modules';
            $table = 'zz_modules';

            $installed = Module::where('name', $info['name'])->first();
        }

        // Copia dei file nella cartella relativa
        copyr(dirname($file->getRealPath()), base_dir().'/'.$directory.'/'.$info['directory']);

        // Eventuale registrazione nel database
        if (empty($installed)) {
            $dbo->insert($table, array_merge($insert, [
                'directory' => $info['directory'],
                'name' => $info['name'],
                'options' => $info['options'],
                'version' => $info['version'],
                'compatibility' => $info['compatibility'],
                'order' => 100,
                'default' => 0,
                'enabled' => 1,
                'icon' => $info['icon'],
                'parent' => Module::where('name', $info['parent'])->first()->id,
            ]));
            $id_record = $dbo->lastInsertedID();
            $dbo->insert($table.'_lang', array_merge($insert, [
                'title' => !empty($info['title']) ? $info['title'] : $info['name'],
                'id_record' => $id_record,
                'id_lang' => Models\Locale::getDefault()->id,
            ]));

            flash()->error(tr('Installazione completata!'));
        } else {
            flash()->error(tr('Aggiornamento completato!'));
        }
    }

    $finder = Symfony\Component\Finder\Finder::create()
        ->files()
        ->ignoreDotFiles(true)
        ->ignoreVCS(true)
        ->in($extraction_dir);

    $files_plugin_template = $finder->name('PLUGIN')->name('TEMPLATES');

    foreach ($files_plugin_template as $file) {
        // Informazioni dal file di configurazione
        $info = Util\Ini::readFile($file->getRealPath());

        // Informazioni aggiuntive per il database
        $insert = [];
        $insert_lang = [];

        // Plugin
        if (basename($file->getRealPath()) == 'PLUGIN') {
            $directory = 'plugins';
            $table = 'zz_plugins';

            $installed = Plugin::where('name', $info['name'])->first()->id;
            $insert['idmodule_from'] = Module::where('name', $info['module_from'])->first()->id;
            $insert['idmodule_to'] = Module::where('name', $info['module_to'])->first()->id;
            $insert['position'] = $info['position'];
            $insert['default'] = 0;
        }

        // Templates
        elseif (basename($file->getRealPath()) == 'TEMPLATES') {
            $directory = 'templates';
            $table = 'zz_prints';

            $installed = Prints::getPrints()[$info['name']];
            $insert['id_module'] = Module::where('name', $info['module'])->first()->id;
            $insert['is_record'] = $info['is_record'];
            $insert_lang['filename'] = $info['filename'];
            $insert['icon'] = $info['icon'];
            $insert['predefined'] = 0;
        }

        // Modules
        else{
            $insert['default'] = 0;
        }

        // Copia dei file nella cartella relativa
        copyr(dirname($file->getRealPath()), base_dir().'/'.$directory.'/'.$info['directory']);

        // Eventuale registrazione nel database
        if (basename($file->getRealPath()) == 'PLUGIN') {
            if (empty($installed)) {
                $dbo->insert($table, array_merge($insert, [
                    'directory' => $info['directory'],
                    'name' => $info['name'],
                    'options' => $info['options'],
                    'idmodule_from' => $insert['idmodule_from'],
                    'idmodule_to' => $insert['idmodule_to'],
                    'position' => $insert['position'],
                    'version' => $info['version'],
                    'compatibility' => $info['compatibility'],
                    'order' => 100,
                    'enabled' => 1,
                ]));
                $id_record = $dbo->lastInsertedID();
                $dbo->insert($table.'_lang', array_merge($insert_lang, [
                    'title' => !empty($info['title']) ? $info['title'] : $info['name'],
                    'id_record' => $id_record,
                    'id_lang' => Models\Locale::getDefault()->id,
                ]));
                flash()->error(tr('Installazione completata!'));
            } else {
                flash()->error(tr('Aggiornamento completato!'));
            }
        } else {
            if (empty($installed)) {
                $dbo->insert($table, array_merge($insert, [
                    'directory' => $info['directory'],
                    'name' => $info['name'],
                    'options' => $info['options'],
                    'version' => $info['version'],
                    'compatibility' => $info['compatibility'],
                    'id_module' => $insert['id_module'],
                    'is_record' => $insert['is_record'],
                    'icon' => $insert['icon'],
                    'order' => 100,
                    'enabled' => 1,
                ]));
                $id_record = $dbo->lastInsertedID();
                $dbo->insert($table.'_lang', array_merge($insert_lang, [
                    'title' => !empty($info['title']) ? $info['title'] : $info['name'],
                    'id_record' => $id_record,
                    'filename' => $insert_lang['filename'],
                    'id_lang' => Models\Locale::getDefault()->id,
                ]));
                flash()->error(tr('Installazione completata!'));
            } else {
                flash()->error(tr('Aggiornamento completato!'));
            }
        }
    }
}

// Rimozione delle risorse inutilizzate
delete($extraction_dir);

// Redirect
redirect(base_path().'/editor.php?id_module='.$id_module);
