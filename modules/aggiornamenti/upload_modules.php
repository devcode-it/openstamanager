<?php

include_once __DIR__.'/../../core.php';

if (!setting('Attiva aggiornamenti')) {
    die(tr('Accesso negato'));
}

if (!extension_loaded('zip')) {
    flash()->error(tr('Estensione zip non supportata!').'<br>'.tr('Verifica e attivala sul tuo file _FILE_', [
        '_FILE_' => '<b>php.ini</b>',
    ]));

    return;
}

$file = $_FILES['blob'];
$type = $_POST['type'];

// Lettura dell'archivio
$zip = new ZipArchive();
if (!$zip->open($file['tmp_name'])) {
    flash()->error(tr('File di installazione non valido!'));
    flash()->error(checkZip($file['tmp_name']));

    return;
}

// Percorso di estrazione
$extraction_dir = $docroot.'/tmp';
directory($extraction_dir);

// Estrazione dell'archivio
$zip->extractTo($extraction_dir);

// Aggiornamento del progetto
if (file_exists($extraction_dir.'/VERSION')) {
    // Salva il file di configurazione
    $config = file_get_contents($docroot.'/config.inc.php');

    // Copia i file dalla cartella temporanea alla root
    copyr($extraction_dir, $docroot);

    // Ripristina il file di configurazione dell'installazione
    file_put_contents($docroot.'/config.inc.php', $config);
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
            $insert['parent'] = Modules::get($info['parent']);
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
        copyr(dirname($file->getRealPath()), $docroot.'/'.$directory.'/'.$info['directory']);

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
$zip->close();

// Redirect
redirect(ROOTDIR.'/editor.php?id_module='.$id_module);
