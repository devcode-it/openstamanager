<?php

namespace App\Restify;

use Binaryk\LaravelRestify\Filters\Filter;
use Binaryk\LaravelRestify\Filters\MatchFilter;
use Binaryk\LaravelRestify\Repositories\Repository as RestifyRepository;

abstract class Repository extends RestifyRepository
{
    public static array $sort = ['id'];

    public static array $match = ['id'];

    public static function matches(): array
    {
        return array_map(static fn (string $type): Filter => MatchFilter::make()->setType($type)->partial(), static::$match);
    }
}
