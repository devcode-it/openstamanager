<?php

include_once __DIR__.'/../../core.php';

$backup_dir = Backup::getDirectory();

switch (filter('op')) {
    case 'getfile':
        $number = filter('number');
        $number = intval($number);

        $backups = Backup::getList();
        $backup = $backups[$number];
        $filename = basename($backup);

        download($backup, $filename);

        break;

    case 'del':
        $number = filter('number');
        $number = intval($number);

        $backups = Backup::getList();
        $backup = $backups[$number];
        $filename = basename($backup);

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
        if (Backup::create()) {
            flash()->info(tr('Nuovo backup creato correttamente!'));
        } else {
            flash()->error(tr('Errore durante la creazione del backup!').' '.str_replace('_DIR_', '"'.$backup_dir.'"', tr('Verifica che la cartella _DIR_ abbia i permessi di scrittura!')));
        }

        break;

    case 'size':
        $number = filter('number');
        $number = intval($number);

        $backups = Backup::getList();
        $backup = $backups[$number];
        $filename = basename($backup);

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

    if (filter('number') == null) {
        $path = $_FILES['blob']['tmp_name'];
    } else {
        $number = filter('number');
        $number = intval($number);

        $backups = Backup::getList();
        $path = $backups[$number];
    }

    Backup::restore($path, is_file($path));

    flash()->info(tr('Backup ripristinato correttamente!'));
}
