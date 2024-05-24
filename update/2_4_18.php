<?php

// File e cartelle deprecate
$files = [
    'plugins/fornitori_articolo',
    'templates/partitario_mastrino/partitario.html',
    'templates/partitario_mastrino/partitario_body.html',
    'templates/partitario_mastrino/pdfgen.partitario_mastrino.php',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(base_dir().'/'.$value);
}

delete($files);

/* Porting modifica UNIQUE con riduzione dei campi per versioni di MySQL < 5.7 */
// Riduzione lunghezza campo nome zz_settings per problema compatibilità mysql 5.6 con UNIQUE
$impostazioni = $database->fetchArray('SELECT `nome`, COUNT(`nome`) AS numero_duplicati FROM `zz_settings` GROUP BY `nome` HAVING COUNT(`nome`) > 1');
foreach ($impostazioni as $impostazione) {
    $limit = intval($impostazione['numero_duplicati']) - 1;

    $database->query('DELETE FROM `zz_settings` WHERE `nome` = '.prepare($impostazione['nome']).' LIMIT '.$limit);
}

// Rimozione dell'indice precedente
try {
    $database->query('ALTER TABLE `zz_settings` DROP INDEX `nome`');
} catch (PDOException) {
}
$database->query('ALTER TABLE `zz_settings` CHANGE `nome` `nome` VARCHAR(150) NOT NULL');
$database->query('ALTER TABLE `zz_settings` ADD UNIQUE(`nome`)');

// Riduzione lunghezza campo username zz_users per problema compatibilità mysql 5.6 con UNIQUE
// Rimozione dell'indice precedente
try {
    $database->query('ALTER TABLE `zz_users` DROP INDEX `username`');
} catch (PDOException) {
}
$database->query('ALTER TABLE `zz_users` CHANGE `username` `username` VARCHAR(150) NOT NULL');
$database->query('ALTER TABLE `zz_users` ADD UNIQUE(`username`)');
