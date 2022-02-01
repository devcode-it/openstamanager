<?php

namespace App\JsonApi\V1;

use App\JsonApi\V1\Users\UserSchema;
use LaravelJsonApi\Core\Server\Server as BaseServer;

class Server extends BaseServer
{
    /**
     * The base URI namespace for this server.
     */
    protected string $baseUri = '/api/v1';

    /**
     * Bootstrap the server when it is handling an HTTP request.
     */
    public function serving(): void
    {
        // no-op
    }

    /**
     * Get the server's list of schemas.
     */
    protected function allSchemas(): array
    {
        return [
            UserSchema::class,
        ];
    }

    /**
     * @inheritdoc
     * TODO: Temporary: it must be added authentication to API routes
     *
     * @return bool
     */
    public function authorizable(): bool
    {
        return false;
    }
}
