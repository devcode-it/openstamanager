<?php

namespace App\Restify;

use Binaryk\LaravelRestify\Filters\MatchFilter;
use Binaryk\LaravelRestify\Repositories\Repository as RestifyRepository;

/**
 * @phpstan-type MatchType 'text'|'string'|'bool'|'int'|'integer'|'datetime'|'between'|'array'
 *
 * @psalm-suppress NonInvariantDocblockPropertyType
 */
abstract class Repository extends RestifyRepository
{
    /**
     * @var string[] The list of fields to be sortable
     *
     * @psalm-suppress NonInvariantDocblockPropertyType
     */
    public static array $sort = ['id'];

    /** @var array<string, MatchType> The list of fields to be partially matchable */
    public static array $match = [];

    /** @var array<string, MatchType> The list of fields to be matchable */
    public static array $fullMatch = ['id' => 'int'];

    /** @noinspection MissingParentCallInspection */
    public static function matches(): array
    {
        $matches = static::$fullMatch;
        foreach (static::$match as $field => $type) {
            $matches[$field] = MatchFilter::make()->setType($type)->partial();
        }

        return $matches;
    }
}
