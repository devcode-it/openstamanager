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

if (!auth()->check()) {
    throw new \LegacyExitException();
}

// Database
$dbo = $database = database();

$lang = app()->getLocale();

/* INTERNAZIONALIZZAZIONE */

// Individuazione di versione e revisione del progetto
$version = Update::getVersion();
$revision = Update::getRevision();

/* ACCESSO E INSTALLAZIONE */
// Controllo sulla presenza dei permessi di accesso basilari
if (!empty($skip_permissions)) {
    Permissions::skip();
}

if (!(auth()->check() || $api_request) && !Permissions::getSkip()) {
    throw new \LegacyExitException();
}

/* INIZIALIZZAZIONE GENERALE */
// Operazione aggiuntive (richieste non API)
if (!$api_request) {
    // Registrazione globale del template per gli input HTML
    ob_start();

    // Impostazione del tema grafico di default
    $theme = 'default';

    $id_record = filter('id_record');
    $id_parent = filter('id_parent');

    Modules::setCurrent(filter('id_module'));
    Plugins::setCurrent(filter('id_plugin'));

    // Variabili fondamentali
    $module = Modules::getCurrent();
    $plugin = Plugins::getCurrent();
    $structure = isset($plugin) ? $plugin : $module;

    $id_module = $module ? $module['id'] : null;
    $id_plugin = $plugin ? $plugin['id'] : null;

    $user = auth()->user();

    if (!empty($id_module)) {
        // Segmenti
        if (session('module_'.$id_module.'.id_segment') === null) {
            $segments = Modules::getSegments($id_module);
            session(['module_'.$id_module.'.id_segment' => isset($segments[0]['id']) ? $segments[0]['id'] : null]);
        }

        Permissions::addModule($id_module);
    }

    Permissions::check();

    // Retrocompatibilità
    $post = Filter::getPOST();
    $get = Filter::getGET();
}

// Inclusione dei file modutil.php
// TODO: sostituire * con lista module dir {aggiornamenti,anagrafiche,articoli}
// TODO: sostituire tutte le funzioni dei moduli con classi Eloquent relative
$files = glob(__DIR__.'/{modules,plugins}/*/modutil.php', GLOB_BRACE);
$custom_files = glob(__DIR__.'/{modules,plugins}/*/custom/modutil.php', GLOB_BRACE);
foreach ($custom_files as $key => $value) {
    $index = array_search(str_replace('custom/', '', $value), $files);
    if ($index !== false) {
        unset($files[$index]);
    }
}

$list = array_merge($files, $custom_files);
foreach ($list as $file) {
    include_once $file;
}

// Inclusione dei file vendor/autoload.php di Composer
$files = glob(__DIR__.'/{modules,plugins}/*/vendor/autoload.php', GLOB_BRACE);
$custom_files = glob(__DIR__.'/{modules,plugins}/*/custom/vendor/autoload.php', GLOB_BRACE);
foreach ($custom_files as $key => $value) {
    $index = array_search(str_replace('custom/', '', $value), $files);
    if ($index !== false) {
        unset($files[$index]);
    }
}

$list = array_merge($files, $custom_files);
foreach ($list as $file) {
    include_once $file;
}
