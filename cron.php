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

/*
 * Script dedicato alla gestione delle operazioni di cron ricorrenti del gestionale.
 * Una volta attivato, questo script rimane attivo in background per gestire l'esecuzione delle diverse operazioni come pianificate nella tabella zz_tasks.
 *
 * Il file viene richiamato in automatico al login di un utente.
 * Per garantire che lo script resti attivo in ogni situazione, si consiglia di introdurre una chiamata nel relativo crontab di sistema secondo il seguente schema:
 */
// Schema crontab: "*/5 * * * * php <percorso_root>/cron.php"

use Carbon\Carbon;
use Models\Cache;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Tasks\Task;

// Rimozione delle limitazioni sull'esecuzione
$php_time_limit = $config['php_time_limit'] ?? 86400;
set_time_limit($php_time_limit);
ignore_user_abort(true);

// Chiusura della richiesta alla pagina
flush();

$skip_permissions = true;
include_once __DIR__.'/core.php';

echo "[CRON] Avvio - ".date('Y-m-d H:i:s')."\n";

// Controllo su possibili aggiornamenti per bloccare il sistema
$database_online = $database->isInstalled() && !Update::isUpdateAvailable();
if (!$database_online) {
    echo "[CRON] STOP - Database offline o aggiornamento disponibile\n";
    return;
}

// Disabilita della sessione
session_write_close();

// Aggiunta di un logger specifico
$pattern = '[%datetime%] %level_name%: %message% %context%'.PHP_EOL;
$formatter = new Monolog\Formatter\LineFormatter($pattern);

$logger = new Logger('Tasks');
$handler = new RotatingFileHandler(base_dir().'/logs/cron.log', 7);
$handler->setFormatter($formatter);
$logger->pushHandler($handler);

// Lettura della cache
$ultima_esecuzione = Cache::where('name', 'Ultima esecuzione del cron')->first();
$data = $ultima_esecuzione->content;

$in_esecuzione = Cache::where('name', 'Cron in esecuzione')->first();
$cron_id = Cache::where('name', 'ID del cron')->first();

$disattiva = Cache::where('name', 'Disabilita cron')->first();
if ($disattiva->content || (in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1']) && !$forza_cron_localhost)) {
    echo "[CRON] STOP - Cron disabilitato o localhost\n";
    return;
}

// Impostazioni sugli slot di esecuzione
$slot_duration = 5;

// Generazione e registrazione del cron
$current_id = random_string();
$cron_id->set($current_id);
echo "[CRON] ID generato: $current_id\n";

// Registrazione dell'esecuzione
$adesso = new Carbon();
$ultima_esecuzione->set($adesso->__toString());

// Prima esecuzione immediata
$slot_minimo = $adesso->copy();

// Rimozione dei log più vecchi
$database->query('DELETE FROM `zz_tasks_logs` WHERE DATE_ADD(`created_at`, INTERVAL :interval DAY) <= NOW()', [
    ':interval' => 7,
]);

// Esecuzione ricorrente
$disattiva->refresh();
$cron_id->refresh();
$in_esecuzione->refresh();

// Controllo su possibili aggiornamenti per bloccare il sistema
$database_online = $database->isInstalled() && !Update::isUpdateAvailable();
if (!$database_online || !empty($disattiva->content) || $cron_id->content != $current_id) {
    echo "[CRON] STOP - Controlli falliti (DB: ".($database_online ? 'OK' : 'KO').", Disattivato: ".($disattiva->content ? 'SI' : 'NO').", ID: ".($cron_id->content == $current_id ? 'OK' : 'KO').")\n";
    return;
}

// Risveglio programmato tramite slot
$timestamp = $slot_minimo->getTimestamp();
time_sleep_until($timestamp);
$in_esecuzione->set(true);

// Registrazione dell'iterazione nei log
$logger->info('Cron #'.$number.' iniziato', [
    'slot' => $slot_minimo->toDateTimeString(),
    'slot-unix' => $timestamp,
]);

// Calcolo del primo slot disponibile per l'esecuzione successiva
$inizio_iterazione = $slot_minimo->copy();
$slot_minimo = $inizio_iterazione->copy()->startOfHour();
while ($inizio_iterazione->greaterThanOrEqualTo($slot_minimo)) {
    $slot_minimo->addMinutes($slot_duration);
}

// Aggiornamento dei cron disponibili
$tasks = Task::all()->where('enabled', 1);
echo "[CRON] Task trovati: ".count($tasks)."\n";
foreach ($tasks as $task) {
    $adesso = new Carbon();

    // Registrazione della data per l'esecuzione se non indicata
    if (empty($task->next_execution_at)) {
        $task->registerNextExecution($inizio_iterazione);
        $task->save();

        $logger->info($task->getTranslation('title').': data mancante', [
            'timestamp' => $task->next_execution_at->toDateTimeString(),
        ]);
    }

    // Esecuzione diretta solo nel caso in cui sia prevista
    if ($task->next_execution_at->copy()->addSeconds(20)->greaterThanOrEqualTo($inizio_iterazione) && $task->next_execution_at->lessThanOrEqualTo($adesso->copy()->addseconds(20))) {
        echo "[CRON] Esecuzione task: ".$task->getTranslation('title')."\n";
        // Registrazione dell'esecuzione nei log
        $logger->info($task->getTranslation('title').': '.$task->expression);
        try {
            $task->execute();
            echo "[CRON] Task completato: ".$task->getTranslation('title')."\n";
        } catch (Exception $e) {
            echo "[CRON] ERRORE task: ".$task->getTranslation('title')." - ".$e->getMessage()."\n";
            // Registrazione del completamento nei log
            $task->log('error', 'Errore di esecuzione', [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $logger->error($task->getTranslation('title').': errore');
        }
    }
    // Esecuzione mancata
    elseif ($task->next_execution_at->lessThan($inizio_iterazione)) {
        echo "[CRON] Task mancato: ".$task->getTranslation('title')." (previsto: ".$task->next_execution_at->toDateTimeString().")\n";
        $logger->warning($task->getTranslation('title').': mancata', [
            'timestamp' => $task->next_execution_at->toDateTimeString(),
        ]);

        $task->registerMissedExecution($inizio_iterazione);
    }

    // Calcolo dello successivo slot
    if ($task->next_execution_at->lessThan($slot_minimo)) {
        $slot_minimo = $task->next_execution_at;
    }
}

// Registrazione dello slot successivo nei log
$logger->info('Cron #'.$number.' concluso', [
    'next-slot' => $slot_minimo->toDateTimeString(),
    'next-slot-unix' => $timestamp,
]);
$in_esecuzione->set(false);
echo "[CRON] Concluso - Prossimo slot: ".$slot_minimo->toDateTimeString()."\n";

// Registrazione dell'esecuzione
$adesso = new Carbon();
$ultima_esecuzione->set($adesso->__toString());