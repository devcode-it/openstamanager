<?php

include_once __DIR__.'/../../core.php';

use Modules\Articoli\Articolo;

if (isset($id_record)) {
    $articolo = Articolo::find($id_record);

    $record = $dbo->fetchOne('SELECT *, (SELECT COUNT(id) FROM mg_prodotti WHERE id_articolo = mg_articoli.id) AS serial FROM mg_articoli WHERE id='.prepare($id_record));
}
