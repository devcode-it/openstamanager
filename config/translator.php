<?php

declare(strict_types=1);

use Translator\Framework\LaravelConfigLoader;
use Translator\Infra\LaravelJsonTranslationRepository;

return [
    'languages' => ['en'],
    'directories' => [
        app_path(),
        resource_path('ts'),
        resource_path('views'),
    ],
    'output' => lang_path(),
    'extensions' => ['php', 'ts', 'tsx'],
    'functions' => ['__', '_s', '_v', '@lang'],
    'container' => [
        'config_loader' => LaravelConfigLoader::class,
        'translation_repository' => LaravelJsonTranslationRepository::class,
    ],
];
