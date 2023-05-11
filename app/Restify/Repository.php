<?php

namespace App\Restify;

use Binaryk\LaravelRestify\Filters\Filter;
use Binaryk\LaravelRestify\Filters\MatchFilter;
use Binaryk\LaravelRestify\Http\Requests\RestifyRequest;
use Binaryk\LaravelRestify\Repositories\Repository as RestifyRepository;
use Illuminate\Support\Arr;

abstract class Repository extends RestifyRepository
{
    public static array $sort = ['id'];

    public static array $match = ['id'];

    public static function matches(): array
    {
        return array_map(static fn (string $type): Filter => MatchFilter::make()->setType($type)->partial(), static::$match);
    }

    /**
     * @psalm-suppress MissingParamType
     */
    public function allowToStore(RestifyRequest $request, $payload = null): RestifyRepository
    {
        $this->adaptJsonApiRequest($request);

        return parent::allowToStore($request, $payload);
    }

    /**
     * @psalm-suppress MissingParamType
     */
    public function allowToPatch(RestifyRequest $request, $payload = null): RestifyRepository
    {
        $this->adaptJsonApiRequest($request);

        return parent::allowToPatch($request, $payload);
    }

    /**
     * Adapt JSON:API request to Restify request.
     */
    protected function adaptJsonApiRequest(RestifyRequest $request): void
    {
        /** @var array<string, mixed> $attributes */
        $attributes = $request->input('attributes') ?? [];
        $relationships = $request->input('relationships') ?? [];

        // Get relationships in form of "relationshipName" => "relationshipId"
        $relationships = array_map(static fn (array $relationship): int => Arr::get($relationship, 'data.id'), $relationships);

        $request->replace([
            ...$attributes,
            ...$relationships,
        ]);
    }
}
