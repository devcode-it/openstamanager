<?php

include_once __DIR__.'/../../core.php';

use Models\Cache;
use Modules\Aggiornamenti\UpdateHook;

$id = post('id');

switch (filter('op')) {
    case 'check':
        $result = UpdateHook::isAvailable();
        $versione = $result[0].' ('.$result[1].')';

        // Salvataggio della versione nella cache
        Cache::get('Ultima versione di OpenSTAManager disponibile')->set($versione);

        echo $versione;

        break;

    case 'upload':
        include DOCROOT.'/modules/aggiornamenti/upload_modules.php';

        break;
}
