<?php

// Impostazioni di base per l'accesso al database
$db_host = '|host|';
$db_username = '|username|';
$db_password = '|password|';
$db_name = '|database|';

// Percorso della cartella di backup
$backup_dir = __DIR__.'/backup/';

// Tema selezionato per il front-end
$theme = 'default';

// Redirect automatico delle richieste da HTTP a HTTPS
$redirectHTTPS = false;

// Impostazioni di debug
$debug = false;
$strict = false;

// Personalizzazione dei gestori dei tag personalizzati
$HTMLWrapper = null;
$HTMLHandlers = [];
$HTMLManagers = [];

// Lingua del progetto
$lang = 'it';
// Personalizzazione della formattazione di date e numeri
$formatter = [];
