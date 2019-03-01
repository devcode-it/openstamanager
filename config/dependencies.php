<?php

// Auth manager
$container['auth'] = function ($container) {
    return new Auth();
};

// Language manager
$container['translator'] = function ($container) {
    $config = $container->settings['$config'];

    $lang = !empty($config['lang']) ? $config['lang'] : 'it';
    $formatter = !empty($config['formatter']) ? $config['formatter'] : [];

    $translator = new Translator();
    $translator->addLocalePath(DOCROOT.'/resources/locale');
    $translator->addLocalePath(DOCROOT.'/modules/*/locale');

    $translator->setLocale($lang, $formatter);

    return $translator;
};

// I18n manager
$container['formatter'] = function ($container) {
    return $container['translator']->getFormatter();
};

// Flash messages
$container['flash'] = function ($container) {
    return new \Util\Messages();
};

use Slim\Views\PhpRenderer;

// Templating PHP
$container['view'] = function ($container) {
    $renderer = new PhpRenderer('./');

    $renderer->setAttributes([
        'database' => $container['database'],
        'dbo' => $container['database'],
        'config' => $container['config'],
        'router' => $container['router'],

        'rootdir' => ROOTDIR,
        'docroot' => DOCROOT,
        'baseurl' => BASEURL,
    ]);

    if (!empty($container['debugbar'])) {
        $renderer->addAttribute('debugbar', $container['debugbar']);
    }

    // Inclusione dei file modutil.php
    // TODO: sostituire * con lista module dir {aggiornamenti,anagrafiche,articoli}
    // TODO: sostituire tutte le funzioni dei moduli con classi Eloquent relative
    $files = glob(DOCROOT.'/{modules,plugins}/*/modutil.php', GLOB_BRACE);
    $custom_files = glob(DOCROOT.'/{modules,plugins}/*/custom/modutil.php', GLOB_BRACE);
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

    return $renderer;
};

// Templating Twig
$container['twig-view'] = function ($container) {
    $settings = $container->settings['views'];

    $view = new \Slim\Views\Twig($settings['templates'], $settings['config']);

    // Instantiate and add Slim specific extension
    $basePath = rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new \Slim\Views\TwigExtension($container['router'], $basePath));
    $view->addExtension(new \Symfony\Bridge\Twig\Extension\TranslationExtension($container['translator']->getTranslator()));

    $view->offsetSet('auth', $container['auth']);
    $view->offsetSet('flash', $container['flash']);
    $view->offsetSet('translator', $container['translator']);
    $view->offsetSet('router', $container['router']);

    if (!empty($container['debugbar'])) {
        $view->offsetSet('debugbar', $container['debugbar']);
    }

    return $view;
};
