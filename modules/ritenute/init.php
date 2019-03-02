<?php

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT *, (SELECT COUNT(idritenutaacconto) FROM co_righe_documenti WHERE co_righe_documenti.idritenutaacconto = '.prepare($id_record).') AS doc_associati FROM `co_ritenutaacconto` WHERE id='.prepare($id_record));
}
