<?php

$finder = PhpCsFixer\Finder::create()
    ->files()
    ->exclude('.couscous')
    ->exclude('node_modules')
    ->exclude('vendor')
    ->exclude('tests')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true)
    ->in(__DIR__);

$config = PhpCsFixer\Config::create()
    ->setRules([
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
    ])
    ->setFinder($finder);

return $config;
