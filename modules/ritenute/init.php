<?php

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT *, (SELECT COUNT(idritenutaacconto) FROM co_documenti WHERE co_documenti.idritenutaacconto = '.prepare($id_record).') AS doc_associati FROM `co_ritenutaacconto` WHERE id='.prepare($id_record));
}
