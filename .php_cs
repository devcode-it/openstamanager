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
        'yoda_style' => false,
        'no_short_echo_tag' => true,
        'ordered_imports' => true,
        'no_alternative_syntax' => true,
        'ordered_class_elements' => true,
        'phpdoc_order' => true,
        //'no_superfluous_phpdoc_tags' => [
        //    'allow_mixed' => true,
        //    'allow_unused_params' => true,
        //],
        //'phpdoc_add_missing_param_annotation' => [
        //    'only_untyped' => false,
        //],
    ])
    ->setFinder($finder);

return $config;
