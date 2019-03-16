<?php

namespace Middlewares\Authorization;

/**
 * @since 2.5
 */
class UserMiddleware extends \Middlewares\AuthorizationMiddleware
{
    protected function operation($request, $response)
    {
        throw new \Slim\Exception\NotFoundException($request, $response);
    }

    protected function hasAuthorization($request)
    {
        return $this->auth->check();
    }
}
