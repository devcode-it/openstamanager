<?php

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $mandato = $database->fetchOne('SELECT * FROM co_mandati_sepa WHERE id_banca = '.prepare($id_record));
}
