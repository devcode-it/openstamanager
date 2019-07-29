<?php

// File e cartelle deprecate
$files = [
    'src\API.php',
    'modules\utenti\api',
    'modules\stato_servizi\api',
    'modules\stati_preventivo\api',
    'modules\stati_intervento\api',
    'modules\tipi_intervento\api',
    'modules\stati_contratto\api',
    'modules\articoli\api',
    'modules\anagrafiche\api',
    'modules\interventi\api\update.php',
    'modules\interventi\api\retrieve.php',
    'modules\interventi\api\delete.php',
    'modules\interventi\api\create.php',
    'modules\aggiornamenti\api',
    'plugins\exportFE\src\Connection.php',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(DOCROOT.'/'.$value);
}

delete($files);
