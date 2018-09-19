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
        $path = $_FILES['blob']['tmp_name'] ?: $backup_dir.'/'.post('zip');
    } else {
        $path = $backup_dir.'/'.post('folder');
    }

    Backup::restore($path, post('folder') == null);
}
