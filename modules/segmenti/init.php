<?php

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT *, (SELECT options FROM zz_modules WHERE id = zz_segments.id_module) options, (SELECT name FROM zz_modules WHERE id = zz_segments.id_module) AS modulo, (SELECT COUNT(t.id) FROM zz_segments t WHERE t.id_module = zz_segments.id_module) AS n_sezionali FROM zz_segments WHERE id='.prepare($id_record));

    $array = preg_match('/(?<=FROM)\s([^\s]+)\s/', $record['options'], $table);
    if (strpos($table[0], 'co_documenti') !== false) {
        $righe = $dbo->fetchArray('SELECT COUNT(*) AS tot FROM '.$table[0].' WHERE id_segment = '.prepare($id_record));
        $tot = $righe[0]['tot'];
    }
}
