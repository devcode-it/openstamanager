<?php

// Controllo sulla versione PHP
$minimum = '5.6.0';
if (version_compare(phpversion(), $minimum) < 0) {
    echo '
<p>Stai utilizzando la versione PHP '.phpversion().', non compatibile con OpenSTAManager.</p>

<p>Aggiorna PHP alla versione >= '.$minimum.'.</p>';
    exit();
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

// Configurazione standard
$config = App::getConfig();

// Redirect al percorso HTTPS se impostato nella configurazione
if (!empty($config['redirectHTTPS']) && !isHTTPS(true)) {
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
    exit();
}

// Istanziamento della sessione
ini_set('session.use_trans_sid', '0');
ini_set('session.use_only_cookies', '1');

session_set_cookie_params(0, $rootdir, null, isHTTPS(true));
session_cache_limiter(false);
session_start();

// Inizializzazione Dependency Injection
$container = new \Slim\Container([
    'settings' => [
        'displayErrorDetails' => App::debug(),
    ],
]);
App::setContainer($container);

// Configurazione
$container['config'] = $config;

// Database
$database = new Database($config['db_host'], $config['db_username'], $config['db_password'], $config['db_name']);
$container['database'] = $database;

// Istanziamento del logging
require __DIR__.'/config/logging.php';

// Istanziamento delle dipendenze
require __DIR__.'/config/dependencies.php';

// Debugbar
if (App::debug()) {
    $debugbar = new \DebugBar\StandardDebugBar();

    $debugbar->addCollector(new \Extensions\EloquentCollector($container['database']->getCapsule()));
    $debugbar->addCollector(new \DebugBar\Bridge\MonologCollector($container['logger']));

    $paths = App::getPaths();
    $debugbarRenderer = $debugbar->getJavascriptRenderer();
    $debugbarRenderer->setIncludeVendors(false);
    $debugbarRenderer->setBaseUrl($paths['assets'].'/php-debugbar');

    $container['debugbar'] = $debugbarRenderer;
}

// Istanziamento dell'applicazione Slim
$app = new \Slim\App($container);

// Aggiunta dei percorsi
require __DIR__.'/routes/web.php';

// Aggiunta dei middleware
require __DIR__.'/config/middlewares.php';

// Run application
$app->run();
