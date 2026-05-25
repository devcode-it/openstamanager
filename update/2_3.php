<?php

/*
* Fix
*/

// Fix per i contenuti ini inseriti all'interno del database
$database->query("UPDATE mg_articoli SET contenuto = REPLACE(REPLACE(REPLACE(contenuto, '&quot;', '\"'), '\n', ".prepare(PHP_EOL)."), '`', '\"')");
$database->query("UPDATE my_impianto_componenti SET contenuto = REPLACE(REPLACE(REPLACE(contenuto, '&quot;', '\"'), '\n', ".prepare(PHP_EOL)."), '`', '\"')");

// Il DROP PRIMARY KEY, CHANGE idintervento->codice e ADD PRIMARY KEY è stato spostato in update/2_3.sql
// Anche la rimozione della FOREIGN KEY in_interventi_tecnici_ibfk_1 è stata spostata in update/2_3.sql
$database->query('UPDATE `in_interventi_tecnici` SET `idintervento` = (SELECT `id` FROM `in_interventi` WHERE `in_interventi`.`codice` = `in_interventi_tecnici`.`idintervento`)');
$database->query('ALTER TABLE `in_interventi_tecnici` CHANGE `idintervento` `idintervento` varchar(25)');
$database->query("UPDATE `in_interventi_tecnici` SET `idintervento` = NULL WHERE `idintervento` = 0 OR `idintervento` = ''");
$database->query('ALTER TABLE `in_interventi_tecnici` CHANGE `idintervento` `idintervento` int(11), ADD CONSTRAINT `in_interventi_tecnici_ibfk_3` FOREIGN KEY (`idintervento`) REFERENCES `in_interventi`(`id`) ON DELETE CASCADE');

// Fix dei timestamp delle tabelle mg_prodotti, mg_movimenti, zz_logs e zz_files
$database->query('UPDATE `mg_prodotti` SET `created_at` = `data`');
$database->query('ALTER TABLE `mg_prodotti` DROP `data`');

$database->query('UPDATE `mg_movimenti` SET `created_at` = `data`');
$database->query('ALTER TABLE `mg_movimenti` DROP `data`');

$database->query('UPDATE `zz_logs` SET `created_at` = `timestamp`');
$database->query('ALTER TABLE `zz_logs` DROP `timestamp`');

$database->query('UPDATE `zz_files` SET `created_at` = `data`');
$database->query('ALTER TABLE `zz_files` DROP `data`');

// Fix per gli idtipointervento che non si sono copiati in in_interventi_tecnici
$database->query("UPDATE `in_interventi_tecnici` SET `idtipointervento` = (SELECT `idtipointervento` FROM `in_interventi` WHERE `in_interventi`.`id` = `in_interventi_tecnici`.`idintervento`) WHERE `idtipointervento` = '' ");

/*
* Rimozione file e cartelle deprecati [in 2.3.1 per risolvere un problema sui percorsi]
*/
