<?php

/*
* Creazione dei campi per l'API (created_at e updated_at)
*/

// I record precedenti vengono impostati a 0000-00-00 00:00:00
$tables = [
    'an_anagrafiche',
    'an_anagrafiche_agenti',
    'an_nazioni',
    'an_referenti',
    'an_relazioni',
    'an_sedi',
    'an_tipianagrafiche',
    'an_tipianagrafiche_anagrafiche',
    'an_zone',
    'co_contratti',
    'co_contratti_tipiintervento',
    'co_documenti',
    'co_iva',
    'co_movimenti',
    'co_ordiniservizio',
    'co_ordiniservizio_pianificazionefatture',
    'co_ordiniservizio_vociservizio',
    'co_pagamenti',
    'co_pianodeiconti1',
    'co_pianodeiconti2',
    'co_pianodeiconti3',
    'co_preventivi',
    'co_preventivi_interventi',
    'co_righe2_contratti',
    'co_righe_contratti',
    'co_righe_documenti',
    'co_righe_preventivi',
    'co_ritenutaacconto',
    'co_rivalsainps',
    'co_scadenziario',
    'co_staticontratti',
    'co_statidocumento',
    'co_statipreventivi',
    'co_tipidocumento',
    'dt_aspettobeni',
    'dt_automezzi',
    'dt_automezzi_tagliandi',
    'dt_automezzi_tecnici',
    'dt_causalet',
    'dt_ddt',
    'dt_porto',
    'dt_righe_ddt',
    'dt_spedizione',
    'dt_statiddt',
    'dt_tipiddt',
    'in_interventi',
    'in_interventi_tecnici',
    'in_righe_interventi',
    'in_statiintervento',
    'in_tariffe',
    'in_tipiintervento',
    'in_vociservizio',
    'mg_articoli',
    'mg_articoli_automezzi',
    'mg_articoli_interventi',
    'mg_categorie',
    'mg_listini',
    'mg_movimenti',
    'mg_prodotti',
    'mg_unitamisura',
    'my_componenti_interventi',
    'my_impianti',
    'my_impianti_contratti',
    'my_impianti_interventi',
    'my_impianto_componenti',
    'or_ordini',
    'or_righe_ordini',
    'or_statiordine',
    'or_tipiordine',
    'zz_tokens',
    'zz_files',
    'zz_groups',
    'zz_group_module',
    'zz_settings',
    'zz_logs',
    'zz_modules',
    'zz_plugins',
    'zz_permissions',
    'zz_users',
    'zz_widgets',
    'zz_views',
    'zz_group_view',
    'zz_semaphores',
];

// created_at e updated_at
$latest_ver = version_compare($mysql_ver, '5.6.5') >= 0;
foreach ($tables as $table) {
    if ($latest_ver) {
        $database->query('ALTER TABLE `'.$table.'` ADD (`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)');
    } else {
        $database->query('ALTER TABLE `'.$table."` ADD (`created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00', `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)");
        $database->query('CREATE TRIGGER '.$table.'_creation BEFORE INSERT ON '.$table.' FOR EACH ROW SET NEW.created_at = CURRENT_TIMESTAMP');
    }
}

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

// Generazione delle chiavi di default per gli utenti
$utenti = $database->fetchArray('SELECT `idutente` FROM `zz_users`');

$array = [];
foreach ($utenti as $utente) {
    $array[] = [
        'id_utente' => $utente['idutente'],
        'token' => secure_random_string(),
    ];
}
if (!empty($array)) {
    $database->insert('zz_tokens', $array);
}

/*
* Fix
*/

// Fix per i contenuti ini inseriti all'interno del database
$database->query("UPDATE mg_articoli SET contenuto = REPLACE(REPLACE(REPLACE(contenuto, '&quot;', '\"'), '\n', ".prepare(PHP_EOL)."), '`', '\"')");
$database->query("UPDATE my_impianto_componenti SET contenuto = REPLACE(REPLACE(REPLACE(contenuto, '&quot;', '\"'), '\n', ".prepare(PHP_EOL)."), '`', '\"')");

// Fix dei timestamp delle tabelle zz_logs e zz_files
$database->query('UPDATE `zz_logs` SET `created_at` = `timestamp`, `updated_at` = `timestamp`');
$database->query('ALTER TABLE `zz_logs` DROP `timestamp`');

$database->query('UPDATE `zz_files` SET `created_at` = `data`, `updated_at` = `data`');
$database->query('ALTER TABLE `zz_files` DROP `data`');

/*
* Rimozione file e cartelle deprecati
*/

// Cartelle deprecate
$dirs = [
    'lib/jscripts',
    'lib/html2pdf',
    'widgets',
    'share',
];

foreach ($dirs as $dir) {
    $dir = realpath($docroot.'/'.$dir);
    if (is_dir($dir)) {
        deltree($dir);
    }
}

// File deprecati
$files = [
    'lib/class.phpmailer.php',
    'lib/class.pop3.php',
    'lib/class.smtp.php',
    'lib/PHPMailerAutoload.php',
    'lib/dbo.class.php',
    'lib/html-helpers.class.php',
    'lib/photo.class.php',
    'lib/widgets.class.php',
    'templates/pdfgen.php',
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

foreach ($files as $file) {
    $file = realpath($docroot.'/'.$file);
    if (file_exists($file)) {
        unlink($file);
    }
}

// File .html dei moduli di default
// Per un problema sulla lunghezza massima del path su glob è necessario dividere le cartelle dei moduli di default da pulire
$dirs = [
    'aggiornamenti,anagrafiche,articoli,automezzi,backup',
    'beni,categorie,causali,contratti,dashboard',
    'ddt,fatture,gestione_componenti,interventi,iva',
    'listini,misure,my_impianti,opzioni,ordini,pagamenti',
    'partitario,porti,preventivi,primanota,scadenzario',
    'stati_intervento,tecnici_tariffe,tipi_anagrafiche,tipi_intervento',
    'utenti,viste,voci_servizio,zone',
];

foreach ($dirs as $dir) {
    $files = glob($docroot.'/modules/{'.$dir.'}/*.html', GLOB_BRACE);
    foreach ($files as $file) {
        unlink($file);
    }
}
