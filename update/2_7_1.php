<?php

include __DIR__.'/../config.inc.php';

$codici = database()->FetchArray('SELECT `id` FROM `co_iva` WHERE `deleted_at` IS NULL AND `codice` IS NULL');

if (!empty($codici)) {
    $max = database()->fetchOne('SELECT MAX(CAST(codice AS UNSIGNED)) AS max FROM co_iva WHERE deleted_at IS NULL')['max'];
    $maxCodice = $max + 1;

    foreach ($codici as $codice) {
        database()->query('UPDATE co_iva SET codice = '.$maxCodice.' WHERE id = '.$codice['id']);
        ++$maxCodice;
    }
}
