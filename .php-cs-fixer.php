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

$config = new PhpCsFixer\Config();
$config->setRules([
    '@Symfony' => true,
    'array_syntax' => ['syntax' => 'short'],
    'yoda_style' => false,
    'echo_tag_syntax' => ['format' => 'long'],
    'ordered_imports' => true,
    'no_alternative_syntax' => true,
    'ordered_class_elements' => true,
    'phpdoc_order' => true,
])
    ->setFinder($finder);

return $config;
