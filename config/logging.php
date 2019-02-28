<?php

/* GESTIONE DEGLI ERRORI */
// Logger per la segnalazione degli errori
$logger = new Monolog\Logger('Logs');
$logger->pushProcessor(new Monolog\Processor\UidProcessor());
$logger->pushProcessor(new Monolog\Processor\WebProcessor());

// Registrazione globale del logger
Monolog\Registry::addLogger($logger, 'logs');

use Monolog\Handler\FilterHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;

$handlers = [];
if (!API::isAPIRequest()) {
    // File di log di base (logs/error.log, logs/setup.log)
    $handlers[] = new StreamHandler($docroot.'/logs/error.log', Monolog\Logger::ERROR);
    $handlers[] = new StreamHandler($docroot.'/logs/setup.log', Monolog\Logger::EMERGENCY);

    // Messaggi grafici per l'utente
    $handlers[] = new Extensions\MessageHandler(Monolog\Logger::ERROR);

    // File di log ordinati in base alla data
    if (App::debug()) {
        $handlers[] = new RotatingFileHandler($docroot.'/logs/error.log', 0, Monolog\Logger::ERROR);
        $handlers[] = new RotatingFileHandler($docroot.'/logs/setup.log', 0, Monolog\Logger::EMERGENCY);
    }

    // Inizializzazione Whoops
    $whoops = new Whoops\Run();

    if (App::debug()) {
        $whoops->pushHandler(new Whoops\Handler\PrettyPageHandler());
    }

    // Abilita la gestione degli errori nel caso la richiesta sia di tipo AJAX
    if (Whoops\Util\Misc::isAjaxRequest()) {
        $whoops->pushHandler(new Whoops\Handler\JsonResponseHandler());
    }

    $whoops->register();

    // Aggiunta di Monolog a Whoops
    $whoops->pushHandler(function ($exception, $inspector, $run) use ($logger) {
        $logger->addError($exception->getMessage(), [
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);
    });
} else {
    $handlers[] = new StreamHandler($docroot.'/logs/api.log', Monolog\Logger::ERROR);
}

// Disabilita i messaggi nativi di PHP
ini_set('display_errors', 0);
// Ignora gli avvertimenti e le informazioni relative alla deprecazione di componenti
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_USER_DEPRECATED & ~E_STRICT);

$pattern = '[%datetime%] %channel%.%level_name%: %message% %context%'.PHP_EOL.'%extra% '.PHP_EOL;
$monologFormatter = new Monolog\Formatter\LineFormatter($pattern);
$monologFormatter->includeStacktraces(App::debug());

// Filtra gli errori per livello preciso del gestore dedicato
foreach ($handlers as $handler) {
    $handler->setFormatter($monologFormatter);
    $logger->pushHandler(new FilterHandler($handler, [$handler->getLevel()]));
}

// Imposta Monolog come gestore degli errori
Monolog\ErrorHandler::register($logger, [], Monolog\Logger::ERROR, Monolog\Logger::ERROR);

$container['logger'] = $logger;
