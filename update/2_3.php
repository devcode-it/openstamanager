<?php

/*
* Inserimento valori di default
*/

// Permessi di default delle viste
$gruppi = $database->fetchArray('SELECT `id` FROM `zz_groups`');
$results = $database->fetchArray('SELECT `id` FROM `zz_views` WHERE `id` NOT IN (SELECT `id_vista` FROM `zz_group_view`)');

$array = [];
foreach ($results as $result) {
    foreach ($gruppi as $gruppo) {
        $array[] = [
            'id_gruppo' => $gruppo['id'],
            'id_vista' => $result['id'],
        ];
    }
}
if (!empty($array)) {
    $database->insert('zz_group_view', $array);
}

/*
* Fix
*/

// Fix per i contenuti ini inseriti all'interno del database
$database->query("UPDATE mg_articoli SET contenuto = REPLACE(REPLACE(REPLACE(contenuto, '&quot;', '\"'), '\n', ".prepare(PHP_EOL)."), '`', '\"')");
$database->query("UPDATE my_impianto_componenti SET contenuto = REPLACE(REPLACE(REPLACE(contenuto, '&quot;', '\"'), '\n', ".prepare(PHP_EOL)."), '`', '\"')");

// Fix per la presenza della Foreign Key in in_interventi_tecnici
$fk = $database->fetchArray('SELECT TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_SCHEMA = '.prepare($database->getDatabaseName())." AND REFERENCED_TABLE_NAME = 'in_interventi' AND CONSTRAINT_NAME = 'in_interventi_tecnici_ibfk_1'");
if (!empty($fk)) {
    $database->query('ALTER TABLE `in_interventi_tecnici` DROP FOREIGN KEY `in_interventi_tecnici_ibfk_1`');
}

$database->query('ALTER TABLE `in_interventi` DROP PRIMARY KEY, CHANGE `idintervento` `codice` varchar(25) NOT NULL UNIQUE, ADD PRIMARY KEY (`id`)');
$database->query('DROP INDEX primary_key ON `in_interventi`');
$database->query('UPDATE `in_interventi_tecnici` SET `idintervento` = (SELECT `id` FROM `in_interventi` WHERE `in_interventi`.`codice` = `in_interventi_tecnici`.`idintervento`)');
$database->query('ALTER TABLE `in_interventi_tecnici` CHANGE `idintervento` `idintervento` varchar(25)');
$database->query("UPDATE `in_interventi_tecnici` SET `idintervento` = NULL WHERE `idintervento` = 0 OR `idintervento` = ''");
$database->query('ALTER TABLE `in_interventi_tecnici` CHANGE `idintervento` `idintervento` int(11), ADD FOREIGN KEY (`idintervento`) REFERENCES `in_interventi`(`id`) ON DELETE CASCADE');

// Fix dei timestamp delle tabelle mg_prodotti, mg_movimenti, zz_logs e zz_files
$database->query('UPDATE `mg_prodotti` SET `created_at` = `data`');
$database->query('ALTER TABLE `mg_prodotti` DROP `data`');

$database->query('UPDATE `mg_movimenti` SET `created_at` = `data`');
$database->query('ALTER TABLE `mg_movimenti` DROP `data`');

$database->query('UPDATE `zz_logs` SET `created_at` = `timestamp`');
$database->query('ALTER TABLE `zz_logs` DROP `timestamp`');

$database->query('UPDATE `zz_files` SET `created_at` = `data`');
$database->query('ALTER TABLE `zz_files` DROP `data`');

/*
* Rimozione file e cartelle deprecati
*/

// File e cartelle deprecate
$files = [
    'lib/jscripts',
    'lib/html2pdf',
    'widgets',
    'share',
    'lib/class.phpmailer.php',
    'lib/class.pop3.php',
    'lib/class.smtp.php',
    'lib/PHPMailerAutoload.php',
    'lib/dbo.class.php',
    'lib/html-helpers.class.php',
    'lib/photo.class.php',
    'lib/widgets.class.php',
    'modules/anagrafiche/plugins/sedi.php',
    'modules/anagrafiche/plugins/referenti.php',
    'modules/ddt/plugins/ddt.anagrafiche.php',
    'modules/my_impianti/plugins/my_impianti.anagrafiche.php',
    'templates/pdfgen.php',
    'templates/interventi/intervento_body.html',
    'templates/interventi/intervento.html',
    'templates/interventi/pdfgen.interventiphp',
    'templates/ddt/ddt_body.html',
    'templates/ddt/ddt.html',
    'templates/ddt/pdfgen.ddt',
    'templates/ordini/ordini_body.html',
    'templates/ordini/ordini.html',
    'templates/ordini/pdfgen.ordini',
    'templates/fatture/fatture_body.html',
    'templates/fatture/fatture.html',
    'templates/fatture/pdfgen.fatture.php',
    'templates/contratti/contratti_body.html',
    'templates/contratti/contratti.html',
    'templates/contratti/pdfgen.contratti.php',
    'templates/preventivi/preventivi_body.html',
    'templates/preventivi/preventivi.html',
    'templates/preventivi/pdfgen.preventivi.php',
    'update/install_2.0.sql',
    'update/update_2.1.sql',
    'update/update_2.1.php',
    'update/update_2.2.sql',
    'update/update_2.2.php',
    'update/update_checker.php',
    'permissions.php',
    'settings.php',
    'addgroup.php',
    'adduser.php',
    'change_pwd.php',
    'README',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath($docroot.'/'.$value);
}

delete($files);

// File .html dei moduli di default
// Per un problema sulla lunghezza massima del path su glob è necessario dividere le cartelle dei moduli di default da pulire
$dirs = [
    'aggiornamenti',
    'anagrafiche',
    'articoli',
    'automezzi',
    'backup',
    'beni',
    'categorie',
    'causali',
    'contratti',
    'dashboard',
    'ddt',
    'fatture',
    'gestione_componenti',
    'interventi',
    'iva',
    'listini',
    'misure',
    'my_impianti',
    'opzioni',
    'ordini',
    'pagamenti',
    'partitario',
    'porti',
    'preventivi',
    'primanota',
    'scadenzario',
    'stati_intervento',
    'tecnici_tariffe',
    'tipi_anagrafiche',
    'tipi_intervento',
    'utenti',
    'viste',
    'voci_servizio',
    'zone',
];

$pieces = array_chunk($dirs, 5);

foreach ($pieces as $piece) {
    $files = glob($docroot.'/modules/{'.implode(',', $piece).'}/*.html', GLOB_BRACE);
    delete($files);
}
