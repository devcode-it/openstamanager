<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

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
