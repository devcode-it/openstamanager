<?php

include_once __DIR__.'/../../core.php';

$records = $dbo->fetchArray('SELECT *, zz_documenti.`id`as id, zz_documenti.nome AS nome, zz_documenti.`data` AS `data` FROM zz_documenti WHERE zz_documenti.id = '.prepare($id_record));
