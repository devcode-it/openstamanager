<?php
/**
 * Script dedicato alla gestione delle operazioni di cron ricorrenti del gestionale.
 * Una volta attivato, questo script rimane attivo in background per gestire l'esecuzione delle diverse operazioni come pianificate nella tabella zz_tasks.
 *
 * Il file viene richiamato in automatico al login di un utente.
 * Per garantire che lo script resti attivo in ogni situazione, si consiglia di introdurre una chiamata nel relativo crontab di sistema secondo il seguente schema:
*/
// Schema crontab: "*/5 * * * * php <percorso_root>/cron.php"

use Carbon\Carbon;
use Cron\CronExpression;
use Models\Cache;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Tasks\Task;

// Rimozione delle limitazioni sull'esecuzione
set_time_limit(0);
ignore_user_abort(true);

// Chiusura della richiesta alla pagina
flush();

$skip_permissions = true;
include_once __DIR__.'/core.php';

// Controllo su possibili aggiornamenti per bloccare il sistema
$database_online = $database->isInstalled() && !Update::isUpdateAvailable();
if (!$database_online) {
    return;
}

// Disabilita della sessione
session_write_close();

// Aggiunta di un logger specifico
$pattern = '[%datetime%] %level_name%: %message% %context%'.PHP_EOL;
$formatter = new Monolog\Formatter\LineFormatter($pattern);

$logger = new Logger('Tasks');
$handler = new RotatingFileHandler(DOCROOT.'/logs/cron.log', 7);
$handler->setFormatter($formatter);
$logger->pushHandler($handler);

// Lettura della cache
$ultima_esecuzione = Cache::get('Ultima esecuzione del cron');
$data = $ultima_esecuzione->content;

$riavvia = Cache::get('Riavvia cron');
$disattiva = Cache::get('Disabilita cron');
if (!empty($disattiva->content)) {
    return;
}

// Impostazioni sugli slot di esecuzione
$slot_duration = 5;

// Controllo sull'ultima esecuzione
$data = $data ? new Carbon($data) : null;
$minimo_esecuzione = (new Carbon())->addMinutes($slot_duration * 5);
if (!empty($data) && $minimo_esecuzione->greaterThanOrEqualTo($data)) {
    return;
}

// Registrazione dell'esecuzione
$adesso = new Carbon();
$ultima_esecuzione->set($adesso->__toString());

// Prima esecuzione immediata
$slot_minimo = $adesso->copy();

// Esecuzione ricorrente
$number = 1;
while (true) {
    $riavvia->refresh();
    $disattiva->refresh();

    // Controllo su possibili aggiornamenti per bloccare il sistema
    $database_online = $database->isInstalled() && !Update::isUpdateAvailable();
    if (!$database_online || !empty($disattiva->content) || !empty($riavvia->content)) {
        return;
    }

    // Risveglio programmato tramite slot
    $timestamp = $slot_minimo->getTimestamp();
    time_sleep_until($timestamp);

    // Registrazione dell'iterazione nei log
    $logger->info('Cron #'.$number.' iniziato', [
        'slot' => $slot_minimo->toDateTimeString(),
        'slot-unix' => $timestamp,
    ]);

    // Calcolo del primo slot disponibile per l'esecuzione successiva
    $inizio_iterazione = new Carbon();
    $slot_minimo = $inizio_iterazione->copy()->startOfHour();
    while ($inizio_iterazione->greaterThanOrEqualTo($slot_minimo)) {
        $slot_minimo->addMinutes($slot_duration);
    }

    // Aggiornamento dei cron disponibili
    $tasks = Task::all();
    foreach ($tasks as $task) {
        $adesso = new Carbon();

        // Individuazione delle informazioni previste dalla relativa espressione
        $cron = CronExpression::factory($task->expression);
        $data_successiva = Carbon::instance($cron->getNextRunDate($adesso));

        // Esecuzione diretta solo nel caso in cui sia prevista
        if ($cron->isDue($inizio_iterazione) || $cron->isDue($adesso)) {
            // Registrazione dell'esecuzione nei log
            $logger->info($task->name.': '.$task->expression);

            $task->execute();
        }

        // Calcolo dello successivo slot
        if ($data_successiva->lessThan($slot_minimo)) {
            $slot_minimo = $data_successiva;
        }
    }

    // Registrazione dello slot successivo nei log
    $logger->info('Cron #'.$number.' concluso', [
        'next-slot' => $slot_minimo->toDateTimeString(),
        'next-slot-unix' => $timestamp,
    ]);

    // Registrazione dell'esecuzione
    $adesso = new Carbon();
    $ultima_esecuzione->set($adesso->__toString());
    ++$number;
}
