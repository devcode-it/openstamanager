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
$disableCSRF = false; // Protezione contro CSRF

// Impostazioni di debug
$debug = false;

// Personalizzazione dei gestori dei tag personalizzati
$HTMLWrapper = null;
$HTMLHandlers = [];
$HTMLManagers = [];

// Lingua del progetto (per la traduzione e la conversione numerica)
$lang = 'it';
// Personalizzazione della formattazione di timestamp, date e orari
$formatter = [
    'timestamp' => 'd/m/Y H:i',
    'date' => 'd/m/Y',
    'time' => 'H:i',
    'number' => [
        'decimals' => ',',
        'thousands' => '.',
    ],
];

// Ulteriori file CSS e JS da includere
$assets = [
    'css' => [],
    'print' => [],
    'js' => [],
];
