<?php

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
require_once __DIR__.'/vendor/autoload.php';

// Individuazione dei percorsi di base
App::definePaths(__DIR__);

$docroot = DOCROOT;
$rootdir = ROOTDIR;
$baseurl = BASEURL;

// Redirect al percorso HTTPS se impostato nella configurazione
if (!empty($redirectHTTPS) && !isHTTPS(true)) {
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
    exit();
}

// Forza l'abilitazione del debug
// $debug = App::debug(true);

// Logger per la segnalazione degli errori
$logger = new Monolog\Logger('Logs');
$logger->pushProcessor(new Monolog\Processor\UidProcessor());
$logger->pushProcessor(new Monolog\Processor\WebProcessor());

// Registrazione globale del logger
Monolog\Registry::addLogger($logger, 'logs');

use Monolog\Handler\StreamHandler;
use Monolog\Handler\FilterHandler;
use Monolog\Handler\RotatingFileHandler;

$handlers = [];
if (!API::isAPIRequest()) {
    // File di log di base (logs/error.log)
    $handlers[] = new StreamHandler($docroot.'/logs/error.log', Monolog\Logger::ERROR);
    $handlers[] = new StreamHandler($docroot.'/logs/setup.log', Monolog\Logger::EMERGENCY);

    // Impostazioni di debug
    if (!empty($debug)) {
        // Ignora gli avvertimenti e le informazioni relative alla deprecazione di componenti
        error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_USER_DEPRECATED);

        // File di log ordinato in base alla data
        $handlers[] = new RotatingFileHandler($docroot.'/logs/error.log', 0, Monolog\Logger::ERROR);
        $handlers[] = new RotatingFileHandler($docroot.'/logs/setup.log', 0, Monolog\Logger::EMERGENCY);

        $prettyPageHandler = new Whoops\Handler\PrettyPageHandler();

        // Imposta Whoops come gestore delle eccezioni di default
        $whoops = new Whoops\Run();
        $whoops->pushHandler($prettyPageHandler);

        // Abilita la gestione degli errori nel caso la richiesta sia di tipo AJAX
        if (Whoops\Util\Misc::isAjaxRequest()) {
            $whoops->pushHandler(new Whoops\Handler\JsonResponseHandler());
        }

        $whoops->register();
    }
} else {
    $handlers[] = new StreamHandler($docroot.'/logs/api.log', Monolog\Logger::ERROR);
}

// Disabilita la segnalazione degli errori (se il debug è disabilitato)
if (empty($debug)) {
    error_reporting(0);
}

// Imposta il formato di salvataggio dei log
$pattern = '[%datetime%] %channel%.%level_name%: %message%';
if (!empty($debug)) {
    $pattern .= ' %context%';
}
$pattern .= PHP_EOL.'%extra% '.PHP_EOL;

$monologFormatter = new Monolog\Formatter\LineFormatter($pattern);

if (!empty($debug)) {
    $monologFormatter->includeStacktraces(true);
}

foreach ($handlers as $handler) {
    $handler->setFormatter($monologFormatter);
    $logger->pushHandler(new FilterHandler($handler, [$handler->getLevel()]));
}

// Imposta Monolog come gestore degli errori
Monolog\ErrorHandler::register($logger);

// Inizializzazione della sessione
if (!API::isAPIRequest()) {
    // Sicurezza della sessioni
    ini_set('session.use_trans_sid', '0');
    ini_set('session.use_only_cookies', '1');

    session_set_cookie_params(0, $rootdir, null, isHTTPS(true));
    session_start();
}

// Istanziamento del gestore delle traduzioni del progetto
$lang = !empty($lang) ? $lang : 'it';
$formatter = !empty($formatter) ? $formatter : [];
$translator = Translator::getInstance();
$translator->addLocalePath($docroot.'/locale');
$translator->addLocalePath($docroot.'/modules/*/locale');
$translator->setLocale($lang, $formatter);

// Individuazione di versione e revisione del progetto
$version = Update::getVersion();
$revision = Update::getRevision();

$dbo = Database::getConnection();

// Controllo sulla presenza dei permessi di accesso basilari
$continue = $dbo->isInstalled() && !Update::isUpdateAvailable() && (Auth::check() || API::isAPIRequest());

if (!empty($skip_permissions)) {
    Permissions::skip();
}

if (!$continue && getURLPath() != slashes(ROOTDIR.'/index.php') && !Permissions::getSkip()) {
    if (Auth::check()) {
        Auth::logout();
    }

    redirect(ROOTDIR.'/index.php');
    exit();
}

// Operazione aggiuntive (richieste non API)
if (!API::isAPIRequest()) {
    // Impostazioni di Content-Type e Charset Header
    header('Content-Type: text/html; charset=UTF-8');

    // Barra di debug
    if (App::debug()) {
        $debugbar = new DebugBar\DebugBar();

        $debugbar->addCollector(new DebugBar\DataCollector\MemoryCollector());
        $debugbar->addCollector(new DebugBar\DataCollector\PhpInfoCollector());

        $debugbar->addCollector(new DebugBar\DataCollector\RequestDataCollector());
        $debugbar->addCollector(new DebugBar\DataCollector\TimeDataCollector());

        $debugbar->addCollector(new DebugBar\Bridge\MonologCollector($logger));
        $debugbar->addCollector(new Extension\EloquentCollector($dbo->getCapsule()));
    }

    // Controllo CSRF
    csrfProtector::init();

    // Aggiunta del wrapper personalizzato per la generazione degli input
    if (!empty($HTMLWrapper)) {
        HTMLBuilder\HTMLBuilder::setWrapper($HTMLWrapper);
    }

    // Aggiunta dei gestori personalizzati per la generazione degli input
    foreach ((array) $HTMLHandlers as $key => $value) {
        HTMLBuilder\HTMLBuilder::setHandler($key, $value);
    }

    // Aggiunta dei gestori per componenti personalizzate
    foreach ((array) $HTMLManagers as $key => $value) {
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
    $theme = !empty($theme) ? $theme : 'default';

    if ($continue) {
        $id_module = filter('id_module');
        $id_record = filter('id_record');
        $id_plugin = filter('id_plugin');
        $id_parent = filter('id_parent');

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

        $user = Auth::user();

        if (!empty($id_module)) {
            $module = Modules::get($id_module);

            $pageTitle = $module['title'];

            // Segmenti
            if (!isset($_SESSION['module_'.$id_module]['id_segment'])) {
                $segments = Modules::getSegments($id_module);
                $_SESSION['module_'.$id_module]['id_segment'] = isset($segments[0]['id']) ? $segments[0]['id'] : null;
            }

            Permissions::addModule($id_module);
        }

        if (!empty($skip_permissions)) {
            Permissions::skip();
        }

        Permissions::check();
    }

    // Retrocompatibilità
    $post = Filter::getPOST();
    $get = Filter::getGET();
}

$f = Modules\Fatture\Fattura::find(7);

$result = Modules\Fatture\Fattura::create([
    'idanagrafica' => 1,
    'data' => '2018-11-11 12:13:21',
    'id_segment' => 1,
    'idtipodocumento' => 1,
]);
