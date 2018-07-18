<?php

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT *, (SELECT options FROM zz_modules WHERE id = zz_segments.id_module) options, (SELECT name FROM zz_modules WHERE id = zz_segments.id_module) AS modulo, (SELECT COUNT(t.id) FROM zz_segments t WHERE t.id_module = zz_segments.id_module) AS n_sezionali FROM zz_segments WHERE id='.prepare($id_record));
}
