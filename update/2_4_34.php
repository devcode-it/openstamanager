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

// Controllo se le colonne per la gestione Provvigioni sono già state aggiunte nella versione 2_4_20
$has_column = null;
$col_righe_contratti = $database->fetchArray('SHOW COLUMNS FROM `co_righe_contratti`');
$has_column = array_search('provvigione', array_column($col_righe_contratti, 'Field'));
if (empty($has_column)) {
    $database->query('ALTER TABLE `co_righe_contratti` ADD `provvigione` DECIMAL(12,6) NOT NULL AFTER `prezzo_unitario_ivato`, ADD `provvigione_unitaria` DECIMAL(12,6) NOT NULL AFTER `provvigione`, ADD `provvigione_percentuale` DECIMAL(12,6) NOT NULL AFTER `provvigione_unitaria`, ADD `tipo_provvigione` ENUM("UNT","PRC") NOT NULL DEFAULT "UNT" AFTER `provvigione_percentuale`');
}

$has_column = null;
$col_righe_preventivi = $database->fetchArray('SHOW COLUMNS FROM `co_righe_preventivi`');
$has_column = array_search('provvigione', array_column($col_righe_preventivi, 'Field'));
if (empty($has_column)) {
    $database->query('ALTER TABLE `co_righe_preventivi` ADD `provvigione` DECIMAL(12,6) NOT NULL AFTER `prezzo_unitario_ivato`, ADD `provvigione_unitaria` DECIMAL(12,6) NOT NULL AFTER `provvigione`, ADD `provvigione_percentuale` DECIMAL(12,6) NOT NULL AFTER `provvigione_unitaria`, ADD `tipo_provvigione` ENUM("UNT","PRC") NOT NULL DEFAULT "UNT" AFTER `provvigione_percentuale`');
}

$has_column = null;
$col_righe_ddt = $database->fetchArray('SHOW COLUMNS FROM `dt_righe_ddt`');
$has_column = array_search('provvigione', array_column($col_righe_ddt, 'Field'));
if (empty($has_column)) {
    $database->query('ALTER TABLE `dt_righe_ddt` ADD `provvigione` DECIMAL(12,6) NOT NULL AFTER `prezzo_unitario_ivato`, ADD `provvigione_unitaria` DECIMAL(12,6) NOT NULL AFTER `provvigione`, ADD `provvigione_percentuale` DECIMAL(12,6) NOT NULL AFTER `provvigione_unitaria`, ADD `tipo_provvigione` ENUM("UNT","PRC") NOT NULL DEFAULT "UNT" AFTER `provvigione_percentuale`');
}

$has_column = null;
$col_righe_ordini = $database->fetchArray('SHOW COLUMNS FROM `or_righe_ordini`');
$has_column = array_search('provvigione', array_column($col_righe_ordini, 'Field'));
if (empty($has_column)) {
    $database->query('ALTER TABLE `or_righe_ordini` ADD `provvigione` DECIMAL(12,6) NOT NULL AFTER `prezzo_unitario_ivato`, ADD `provvigione_unitaria` DECIMAL(12,6) NOT NULL AFTER `provvigione`, ADD `provvigione_percentuale` DECIMAL(12,6) NOT NULL AFTER `provvigione_unitaria`, ADD `tipo_provvigione` ENUM("UNT","PRC") NOT NULL DEFAULT "UNT" AFTER `provvigione_percentuale`');
}
