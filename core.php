<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

// Rimozione header X-Powered-By
header_remove('X-Powered-By');

// Impostazioni di configurazione PHP
date_default_timezone_set('Europe/Rome');

// Controllo sulla versione PHP
$minimum = '5.6.0';
if (version_compare(phpversion(), $minimum) < 0) {
    echo '
<p>Stai utilizzando la versione PHP '.phpversion().', non compatibile con OpenSTAManager.</p>

<p>Aggiorna PHP alla versione >= '.$minimum.'.</p>';
    exit();
}

// Caricamento delle impostazioni personalizzabili
if (file_exists(__DIR__.'/config.inc.php')) {
    include_once __DIR__.'/config.inc.php';
}

// Caricamento delle dipendenze e delle librerie del progetto
$loader = require_once __DIR__.'/vendor/autoload.php';

$namespaces = require_once __DIR__.'/config/namespaces.php';
foreach ($namespaces as $path => $namespace) {
    $loader->addPsr4($namespace.'\\', __DIR__.'/'.$path.'/custom/src');
    $loader->addPsr4($namespace.'\\', __DIR__.'/'.$path.'/src');
}

// Individuazione dei percorsi di base
App::definePaths(__DIR__);

$docroot = DOCROOT;
$rootdir = ROOTDIR;
$baseurl = BASEURL;

$config = App::getConfig();

// Redirect al percorso HTTPS se impostato nella configurazione
if (!empty($config['redirectHTTPS']) && !isHTTPS(true)) {
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
    exit();
}

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
if (!API\Response::isAPIRequest()) {
    // File di log di base (logs/error.log, logs/setup.log)
    $handlers[] = new StreamHandler(base_dir().'/logs/error.log', Monolog\Logger::ERROR);
    $handlers[] = new StreamHandler(base_dir().'/logs/setup.log', Monolog\Logger::EMERGENCY);

    // Messaggi grafici per l'utente
    $handlers[] = new Extensions\MessageHandler(Monolog\Logger::ERROR);

    // File di log ordinati in base alla data
    if (App::debug()) {
        $handlers[] = new RotatingFileHandler(base_dir().'/logs/error.log', 0, Monolog\Logger::ERROR);
        $handlers[] = new RotatingFileHandler(base_dir().'/logs/setup.log', 0, Monolog\Logger::EMERGENCY);
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
    $handlers[] = new StreamHandler(base_dir().'/logs/api.log', Monolog\Logger::ERROR);
}

// Sicurezza della sessioni
ini_set('session.cookie_samesite', 'strict');
ini_set('session.use_trans_sid', '0');
ini_set('session.use_only_cookies', '1');

session_set_cookie_params(0, base_path(), null, isHTTPS(true));
session_start();

// Disabilita i messaggi nativi di PHP
ini_set('display_errors', 0);
// Ignora gli avvertimenti e le informazioni relative alla deprecazione di componenti
error_reporting(E_ALL & ~E_WARNING & ~E_CORE_WARNING & ~E_NOTICE & ~E_USER_DEPRECATED & ~E_STRICT);

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

// Database
$dbo = $database = database();

/* INTERNAZIONALIZZAZIONE */
// Istanziamento del gestore delle traduzioni del progetto
$lang = !empty($config['lang']) ? $config['lang'] : (isset($_GET['lang']) ? $_GET['lang'] : null);
$formatter = !empty($config['formatter']) ? $config['formatter'] : [];
$translator = trans();
$translator->addLocalePath(base_dir().'/locale');
$translator->addLocalePath(base_dir().'/modules/*/locale');
$translator->setLocale($lang, $formatter);

// Individuazione di versione e revisione del progetto
$version = Update::getVersion();
$revision = Update::getRevision();

/* ACCESSO E INSTALLAZIONE */
// Controllo sulla presenza dei permessi di accesso basilari
$continue = $dbo->isInstalled() && !Update::isUpdateAvailable() && (Auth::check() || API\Response::isAPIRequest());

if (!empty($skip_permissions)) {
    Permissions::skip();
}

if (!$continue && getURLPath() != slashes(base_path().'/index.php') && !Permissions::getSkip()) {
    if (Auth::check()) {
        Auth::logout();
    }

    redirect(base_path().'/index.php');
    exit();
}

/* INIZIALIZZAZIONE GENERALE */
// Operazione aggiuntive (richieste non API)
if (!API\Response::isAPIRequest()) {
    // Impostazioni di Content-Type e Charset Header
    header('Content-Type: text/html; charset=UTF-8');

    // Controllo CSRF
    if (empty($config['disableCSRF'])) {
        csrfProtector::init();
    }

    // Aggiunta del wrapper personalizzato per la generazione degli input
    if (!empty($config['HTMLWrapper'])) {
        HTMLBuilder\HTMLBuilder::setWrapper($config['HTMLWrapper']);
    }

    // Aggiunta dei gestori personalizzati per la generazione degli input
    foreach ((array) $config['HTMLHandlers'] as $key => $value) {
        HTMLBuilder\HTMLBuilder::setHandler($key, $value);
    }

    // Aggiunta dei gestori per componenti personalizzate
    foreach ((array) $config['HTMLManagers'] as $key => $value) {
        HTMLBuilder\HTMLBuilder::setManager($key, $value);
    }

    // Registrazione globale del template per gli input HTML
    register_shutdown_function('translateTemplate');
    ob_start();

    // Retrocompatibilità
    $_SESSION['infos'] = isset($_SESSION['infos']) ? array_unique($_SESSION['infos']) : [];
    $_SESSION['warnings'] = isset($_SESSION['warnings']) ? array_unique($_SESSION['warnings']) : [];
    $_SESSION['errors'] = isset($_SESSION['errors']) ? array_unique($_SESSION['errors']) : [];

    // Impostazione del tema grafico di default
    $theme = !empty($config['theme']) ? $config['theme'] : 'default';

    if ($continue) {
        // Periodo di visualizzazione dei record
        // Personalizzato
        if (!empty($_GET['period_start'])) {
            $_SESSION['period_start'] = $_GET['period_start'];
            $_SESSION['period_end'] = $_GET['period_end'];
        }
        // Dal 01-01-yyy al 31-12-yyyy
        elseif (!isset($_SESSION['period_start'])) {
            $_SESSION['period_start'] = date('Y').'-01-01';
            $_SESSION['period_end'] = date('Y').'-12-31';
        }

        $id_record = filter('id_record');
        $id_parent = filter('id_parent');

        Modules::setCurrent(filter('id_module'));
        Plugins::setCurrent(filter('id_plugin'));

        // Variabili fondamentali
        $module = Modules::getCurrent();
        $plugin = Plugins::getCurrent();
        $structure = isset($plugin) ? $plugin : $module;

        $id_module = $module ? $module['id'] : null;
        $id_plugin = $plugin ? $plugin['id'] : null;

        $user = Auth::user();

        if (!empty($id_module)) {
            // Segmenti
            if (!isset($_SESSION['module_'.$id_module]['id_segment'])) {
                $segments = Modules::getSegments($id_module);
                $_SESSION['module_'.$id_module]['id_segment'] = isset($segments[0]['id']) ? $segments[0]['id'] : null;
            }

            Permissions::addModule($id_module);
        }

        Permissions::check();
    }

    // Retrocompatibilità
    $post = Filter::getPOST();
    $get = Filter::getGET();
}

// Inclusione dei file modutil.php
// TODO: sostituire * con lista module dir {aggiornamenti,anagrafiche,articoli}
// TODO: sostituire tutte le funzioni dei moduli con classi Eloquent relative
$files = glob(__DIR__.'/{modules,plugins}/*/modutil.php', GLOB_BRACE);
$custom_files = glob(__DIR__.'/{modules,plugins}/*/custom/modutil.php', GLOB_BRACE);
foreach ($custom_files as $key => $value) {
    $index = array_search(str_replace('custom/', '', $value), $files);
    if ($index !== false) {
        unset($files[$index]);
    }
}

$list = array_merge($files, $custom_files);
foreach ($list as $file) {
    include_once $file;
}

// Inclusione dei file vendor/autoload.php di Composer
$files = glob(__DIR__.'/{modules,plugins}/*/vendor/autoload.php', GLOB_BRACE);
$custom_files = glob(__DIR__.'/{modules,plugins}/*/custom/vendor/autoload.php', GLOB_BRACE);
foreach ($custom_files as $key => $value) {
    $index = array_search(str_replace('custom/', '', $value), $files);
    if ($index !== false) {
        unset($files[$index]);
    }
}

$list = array_merge($files, $custom_files);
foreach ($list as $file) {
    include_once $file;
}
