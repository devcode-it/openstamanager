<?php

include_once __DIR__.'/../../core.php';

if (!get_var('Attiva aggiornamenti')) {
    die(tr('Accesso negato'));
}

if (!extension_loaded('zip')) {
    $_SESSION['errors'][] = tr('Estensione zip non supportata!').'<br>'.tr('Verifica e attivala sul tuo file _FILE_', [
        '_FILE_' => '<b>php.ini</b>',
    ]);

    return;
}

$file = $_FILES['blob'];
$type = $_POST['type'];

// Lettura dell'archivio
$zip = new ZipArchive();
if (!$zip->open($file['tmp_name'])) {
    $_SESSION['errors'][] = checkZip($file['tmp_name']);

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
}

// Installazione/aggiornamento di un modulo
elseif (file_exists($extraction_dir.'/MODULE')) {
    // Leggo le info dal file di configurazione del modulo
    $info = Util\Ini::readFile($extraction_dir.'/MODULE');

    // Copio i file nella cartella "modules/<directory>/"
    copyr($extraction_dir, $docroot.'/modules/'.$info['directory']);

    // Verifico se il modulo non esista già
    $installed = Modules::get($info['name']);
    if (empty($installed)) {
        $info['parent'] = Modules::get($info['parent']) ? $info['parent'] : null;

        $dbo->insert('zz_modules', [
            'name' => $info['name'],
            'title' => !empty($info['title']) ? $info['title'] : $info['name'],
            'directory' => $info['directory'],
            'options' => $info['options'],
            'version' => $info['version'],
            'compatibility' => $info['compatibility'],
            'order' => 100,
            'parent' => $info['parent'],
            'default' => 0,
            'enabled' => 1,
        ]);
    }
}

// Installazione/aggiornamento di un plugin
elseif (file_exists($extraction_dir.'/PLUGIN')) {
    // Leggo le info dal file di configurazione del modulo
    $info = Util\Ini::readFile($extraction_dir.'/PLUGIN');

    // Copio i file nella cartella "modules/<directory>/"
    copyr($extraction_dir, $docroot.'/plugins/'.$info['directory']);

    // Verifico se il modulo non esista già
    $installed = Plugins::get($info['name']);
    if (empty($installed)) {
        $info['parent'] = Plugins::get($info['parent']) ? $info['parent'] : null;

        $dbo->insert('zz_plugins', [
            'name' => $info['name'],
            'title' => !empty($info['title']) ? $info['title'] : $info['name'],
            'directory' => $info['directory'],
            'options' => $info['options'],
            'version' => $info['version'],
            'compatibility' => $info['compatibility'],
            'order' => 100,
            'parent' => $info['parent'],
            'default' => 0,
            'enabled' => 1,
        ]);
    }
}

// File di installazione non valido
else {
    $_SESSION['errors'][] = tr('File di installazione non valido!');
}

delete($extraction_dir);
redirect($rootdir);

$zip->close();
