<?php

include_once __DIR__.'/../../core.php';

$path = $docroot.'/files/my_impianti/';

switch (post('op')) {
    case 'update':
        $nomefile = post('nomefile');
        $contenuto = post('contenuto');

        if (!file_put_contents($path.$nomefile, $contenuto)) {
            $_SESSION['errors'][] = _('Impossibile modificare il file!');
        } else {
            $_SESSION['infos'][] = _('Informazioni salvate correttamente!');
        }

    break;

    case 'add':
        $nomefile = str_replace('.ini', '', post('nomefile')).'.ini';
        $contenuto = post('contenuto');

        $cmp = \Util\Ini::getList($path);

        $duplicato = false;
        for ($c = 0; $c < count($cmp); ++$c) {
            if ($nomefile == $cmp[$c][0]) {
                $duplicato = true;
            }
        }

        if ($duplicato) {
            $_SESSION['errors'][] = str_replace('_FILE_', "'".$nomefile."'", _('Il file componente _FILE_ esiste già, nessun nuovo componente è stato creato!'));
        } elseif (!file_put_contents($path.$nomefile, $contenuto)) {
            $_SESSION['errors'][] = _('Impossibile creare il file!');
        } else {
            $_SESSION['infos'][] = str_replace('_FILE_', "'".$nomefile."'", _('Componente _FILE_ aggiunto correttamente!'));
        }

    break;

    case 'delete':
        $nomefile = post('nomefile');

        if (!empty($nomefile)) {
            unlink($path.$nomefile);
            $_SESSION['infos'][] = str_replace('_FILE_', "'".$nomefile."'", _('File _FILE_ rimosso correttamente!'));
        }

    break;
}
