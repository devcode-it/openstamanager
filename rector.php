<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/api',
        __DIR__.'/config',
        __DIR__.'/include',
        __DIR__.'/lib',
        __DIR__.'/modules',
        __DIR__.'/plugins',
        __DIR__.'/src',
        __DIR__.'/templates',
        __DIR__.'/update',
    ]);

    // register a single rule
    $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);

    // define sets of rules
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_83,
    ]);
};
