<?php

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT *, (SELECT COUNT(id_ritenuta_contributi) FROM co_documenti WHERE co_documenti.id_ritenuta_contributi = '.prepare($id_record).') AS doc_associati FROM `co_ritenuta_contributi` WHERE id='.prepare($id_record));
}
