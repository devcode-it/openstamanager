<?php

// Impostazioni di base per l'accesso al database
$db_host = '|host|';
$db_username = '|username|';
$db_password = '|password|';
$db_name = '|database|';
//$port = '|port|';

// Percorso della cartella di backup
$backup_dir = __DIR__.'/backup/';

// Tema selezionato per il front-end
$theme = 'default';

// Impostazioni di sicurezza
$redirectHTTPS = false; // Redirect automatico delle richieste da HTTP a HTTPS
$disableCSRF = true; // Protezione contro CSRF

// Impostazioni di debug
$debug = false;

// Personalizzazione dei gestori dei tag personalizzati
$HTMLWrapper = null;
$HTMLHandlers = [];
$HTMLManagers = [];

// Lingua del progetto (per la traduzione e la conversione numerica)
$lang = '|lang|';
// Personalizzazione della formattazione di timestamp, date e orari
$formatter = [
    'timestamp' => '|timestamp|',
    'date' => '|date|',
    'time' => '|time|',
    'number' => [
        'decimals' => '|decimals|',
        'thousands' => '|thousands|',
    ],
];

// Ulteriori file CSS e JS da includere
$assets = [
    'css' => [],
    'print' => [],
    'js' => [],
];
