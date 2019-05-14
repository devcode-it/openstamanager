<?php

include_once __DIR__.'/../../core.php';

$id = post('id');

switch (filter('op')) {
    case 'check':
        $api = json_decode(get_remote_data('https://api.github.com/repos/devcode-it/openstamanager/releases'), true);

        $version = ltrim($api[0]['tag_name'], 'v');
        $current = Update::getVersion();

        if (version_compare($current, $version) < 0) {
            echo $version;
        } else {
            echo 'none';
        }

        break;

    case 'upload':
        include DOCROOT.'/modules/aggiornamenti/upload_modules.php';

        break;
}
