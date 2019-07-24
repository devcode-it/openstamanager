<?php

// File e cartelle deprecate
$files = [
    'src\API.php',
    'modules\utenti\api\create.php',
    'modules\stato_servizi\api\retrieve.php',
    'modules\stati_preventivo\api\retrieve.php',
    'modules\stati_intervento\api\retrieve.php',
    'modules\stati_contratto\api\retrieve.php',
    'modules\articoli\api\retrieve.php',
    'modules\anagrafiche\api\update.php',
    'modules\anagrafiche\api\retrieve.php',
    'modules\anagrafiche\api\delete.php',
    'modules\anagrafiche\api\create.php',
    'modules\interventi\api\update.php',
    'modules\interventi\api\retrieve.php',
    'modules\interventi\api\delete.php',
    'modules\interventi\api\create.php',
    'modules\aggiornamenti\api\retrieve.php',
    'modules\aggiornamenti\api\create.php',
    'plugins\exportFE\src\Connection.php',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(DOCROOT.'/'.$value);
}

delete($files);
