<?php

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'getfile':
        $file = filter('file');

        download($backup_dir.$file, $file);

        break;

    case 'del':
        $file = filter('file');

        delete($backup_dir.$file);

        if (!file_exists($backup_dir.$file)) {
            flash()->info(tr('Backup _FILE_ eliminato!', [
                '_FILE_' => '"'.$file.'"',
            ]));
        } else {
            flash()->error(tr("Errore durante l'eliminazione del backup _FILE_!", [
                '_FILE_' => '"'.$file.'"',
            ]));
        }

        break;

    case 'backup':
        if (Backup::create()) {
            flash()->info(tr('Nuovo backup creato correttamente!'));
        } else {
            flash()->error(tr('Errore durante la creazione del backup!').' '.tr_replace('_DIR_', '"'.$backup_dir.'"', tr('Verifica che la cartella _DIR_ abbia i permessi di scrittura!')));
        }

        break;
}

if (filter('op') == 'restore') {
    if (!extension_loaded('zip')) {
        flash()->error(tr('Estensione zip non supportata!').'<br>'.tr('Verifica e attivala sul tuo file _FILE_', [
                '_FILE_' => '<b>php.ini</b>',
            ]));

        return;
    }

    if (post('folder') == null) {
        $file = $_FILES['blob']['tmp_name'] ?: post('zip');

        // Lettura dell'archivio
        $zip = new ZipArchive();
        if (!$zip->open($file)) {
            flash()->error(tr('File di installazione non valido!'));
            flash()->error(checkZip($file));

            return;
        }

        // Percorso di estrazione
        $extraction_dir = $docroot.'/tmp';
        directory($extraction_dir);

        // Estrazione dell'archivio
        $zip->extractTo($extraction_dir);
        $zip->close();
    } else {
        $extraction_dir = $backup_dir.'/'.post('folder');
    }

    // Rimozione del database
    $tables = include $docroot.'/update/tables.php';

    $database->query('SET foreign_key_checks = 0');
    foreach ($tables as $tables) {
        $database->query('DROP TABLE `'.$tables.'`');
    }
    $database->query('DROP TABLE `updates`');

    // Ripristino del database
    $database->multiQuery($extraction_dir.'/database.sql');
    $database->query('SET foreign_key_checks = 1');

    // Salva il file di configurazione
    $config = file_get_contents($docroot.'/config.inc.php');

    // Copia i file dalla cartella temporanea alla root
    copyr($extraction_dir, $docroot);

    // Ripristina il file di configurazione dell'installazione
    file_put_contents($docroot.'/config.inc.php', $config);

    // Pulizia
    if (post('folder') == null) {
        delete($extraction_dir);
    }
    delete($docroot.'/database.sql');
}
