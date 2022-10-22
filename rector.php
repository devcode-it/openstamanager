<?php /** @noinspection DevelopmentDependenciesUsageInspection */

declare(strict_types=1);

use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector;
use Rector\CodingStyle\Rector\Encapsed\WrapEncapsedVariableInCurlyBracesRector;
use Rector\Config\RectorConfig;
use Rector\Laravel\Set\LaravelSetList;
use Rector\Naming\Rector\ClassMethod\RenameParamToMatchTypeRector;
use Rector\Php81\Rector\ClassConst\FinalizePublicClassConstantRector;
use Rector\Privatization\Rector\Class_\FinalizeClassesWithoutChildrenRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/database'
    ]);

    // define sets of rules
    $rectorConfig->sets([
        SetList::PHP_80,
        SetList::PHP_81,
//            SetList::PHP_82,
        LevelSetList::UP_TO_PHP_81,
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::MYSQL_TO_MYSQLI,
        SetList::PSR_4,
        SetList::PRIVATIZATION,
        SetList::TYPE_DECLARATION_STRICT,
        LaravelSetList::LARAVEL_90
    ]);

    $rectorConfig->skip([
        EncapsedStringsToSprintfRector::class,
        WrapEncapsedVariableInCurlyBracesRector::class,
        RenameParamToMatchTypeRector::class,
        FinalizeClassesWithoutChildrenRector::class,
        FinalizePublicClassConstantRector::class,
        CatchExceptionNameMatchingTypeRector::class
    ]);
};
