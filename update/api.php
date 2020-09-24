<?php

/*
* Creazione dei campi per l'API (created_at e updated_at)
*/

use API\Response;

// I record precedenti vengono impostati a NULL
$tables = include __DIR__.'/tables.php';

foreach ($tables as $table) {
    if ($database->tableExists($table)) {
        $query = 'SHOW COLUMNS FROM `'.$table.'` IN `'.$database->getDatabaseName()."` WHERE Field='|field|'";

        $created_at = $database->fetchArray(str_replace('|field|', 'created_at', $query));
        if (empty($created_at)) {
            $database->query('ALTER TABLE `'.$table.'` ADD `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP');
        }

        if (Response::isCompatible()) {
            $updated_at = $database->fetchArray(str_replace('|field|', 'updated_at', $query));
            if (empty($updated_at)) {
                $database->query('ALTER TABLE `'.$table.'` ADD `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
            }
        }
    }
}
