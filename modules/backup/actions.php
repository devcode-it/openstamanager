<?php

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'getfile':
        $file = filter('file');

        force_download($file, file_get_contents($backup_dir.$file));

        break;

    case 'del':
        $file = filter('file');
        deltree($backup_dir.$file);

        if (!file_exists($backup_dir.$file)) {
            $_SESSION['infos'][] = str_replace('_FILE_', '"'.$file.'"', tr('Backup _FILE_ eliminato!'));
        } else {
            $_SESSION['errors'][] = str_replace('_FILE_', '"'.$file.'"', tr("Errore durante l'eliminazione del backup _FILE_!"));
        }

        break;

    case 'backup':
        if (!do_backup()) {
            $_SESSION['errors'][] = tr('Errore durante la creazione del backup!').' '.tr_replace('_DIR_', '"'.$backup_dir.'"', tr('Verifica che la cartella _DIR_ abbia i permessi di scrittura!'));
        }

        break;
}
