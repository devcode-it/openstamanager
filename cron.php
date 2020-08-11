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
use Models\Cache;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Tasks\CronExpression;
use Tasks\Task;

// Rimozione delle limitazioni sull'esecuzione
//set_time_limit(0);
//ignore_user_abort(true);

// Chiusura della richiesta alla pagina
//flush();

$skip_permissions = true;
include_once __DIR__.'/core.php';

// Disabilita della sessione
session_write_close();

// Aggiunta di un logger specifico
$logger = new Logger('Tasks');
$handler = new RotatingFileHandler(DOCROOT.'/logs/cron_Test.log', 0);
$handler->setFormatter($monologFormatter);
$logger->pushHandler($handler);

// Lettura della cache
$ultima_esecuzione = Cache::get('Ultima esecuzione del cron');
$data = $ultima_esecuzione->content;

// Impostazioni sugli slot di esecuzione
$slot_duration = 5;

// Controllo sull'ultima esecuzione
$data = $data ? new Carbon($data) : null;
$minimo_esecuzione = (new Carbon())->addMinutes($slot_duration * 5);
if (!empty($data) && $data->greaterThanOrEqualTo($minimo_esecuzione)) {
    return;
}

// Calcolo del primo slot disponibile
$adesso = new Carbon();
$slot = (new Carbon())->startOfHour();
while ($adesso->greaterThanOrEqualTo($slot)) {
    $slot->addMinutes($slot_duration);
}

// Esecuzione ricorrente
$number = 1;
while (true) {
    // Risveglio programmato tramite slot
    $timestamp = $slot->getTimestamp();
    time_sleep_until($timestamp);

    // Registrazione nei log
    $logger->info('Cron #'.$number.' (slot timestamp: '.$timestamp.')');

    // Aggiornamento dei cron disponibili
    $tasks = Task::all();
    foreach ($tasks as $task) {
        $cron = CronExpression::factory($task->expression);

        // Esecuzione diretta solo nel caso in cui sia prevista
        if ($cron->isDue()) {
            $task->execute();
        }
    }

    // Registrazione dell'esecuzione
    $adesso = new Carbon();
    $ultima_esecuzione->set($adesso->__toString());

    // Individuazione dello slot di 5 minuti per l'esecuzione successiva
    while ($adesso->greaterThanOrEqualTo($slot)) {
        $slot->addMinutes($slot_duration);
    }
    ++$number;
}
