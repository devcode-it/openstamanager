<?php

// Fix eliminazione fattura collegata a Nota di credito

$fk = $database->fetchArray('SELECT TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_SCHEMA = '.prepare($database->getDatabaseName())." AND REFERENCED_TABLE_NAME = 'co_documenti' AND CONSTRAINT_NAME = 'co_documenti_ibfk_1'");
if (!empty($fk)) {
    $database->query('ALTER TABLE `co_documenti` DROP FOREIGN KEY `co_documenti_ibfk_1`');
}

$fk = $database->fetchArray('SELECT TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_SCHEMA = '.prepare($database->getDatabaseName())." AND REFERENCED_TABLE_NAME = 'co_righe_documenti' AND CONSTRAINT_NAME = 'co_righe_documenti_ibfk_1'");
if (!empty($fk)) {
    $database->query('ALTER TABLE `co_righe_documenti` DROP FOREIGN KEY `co_righe_documenti_ibfk_1`');
}

?>