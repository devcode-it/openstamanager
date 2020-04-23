<?php

include_once __DIR__.'/../../core.php';

use Models\Cache;
use Modules\Aggiornamenti\UpdateHook;

$id = post('id');

switch (filter('op')) {
    case 'check':
        $result = UpdateHook::isAvailable();

        Cache::get('Ultima versione di OpenSTAManager disponibile')->set($result);

        echo $result;

        break;

    case 'upload':
        include DOCROOT.'/modules/aggiornamenti/upload_modules.php';

        break;
}
