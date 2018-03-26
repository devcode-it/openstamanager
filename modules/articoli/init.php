<?php

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $records = $dbo->fetchArray('SELECT *, (SELECT COUNT(id) FROM mg_prodotti WHERE id_articolo = mg_articoli.id) AS serial FROM mg_articoli WHERE id='.prepare($id_record));
}
