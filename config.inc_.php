<?php

// Impostazioni di base per l'accesso al database
$db_host = 'localhost';
$db_username = 'root';
$db_password = '';
$db_name = 'osm_23';

// Percorso della cartella di backup
$backup_dir = __DIR__.'/backup/';

// Tema selezionato per il front-end
$theme = 'default';

// Redirect automatico delle richieste da HTTP a HTTPS
$redirectHTTPS = false;

// Impostazioni di debug
$debug = true;
$operations_log = true;

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
