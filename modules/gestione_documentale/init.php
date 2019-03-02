<?php

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT *, zz_documenti.`id`as id, zz_documenti.nome AS nome, zz_documenti.`data` AS `data` FROM zz_documenti WHERE zz_documenti.id = '.prepare($id_record));
}
