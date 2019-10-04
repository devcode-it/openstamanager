<?php

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT *, do_documenti.`id`as id, do_documenti.nome AS nome, do_documenti.`data` AS `data` FROM do_documenti WHERE do_documenti.id = '.prepare($id_record));
}
