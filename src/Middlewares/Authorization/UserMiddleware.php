<?php

namespace Middlewares\Authorization;

class UserMiddleware extends \Middlewares\AuthorizationMiddleware
{
    protected function operation($request, $response)
    {
        throw new \Slim\Exception\NotFoundException($request, $response);
    }

    protected function hasAuthorization($request)
    {
        $auth = $this->auth->check();

        $permission = true;
        if ($request == null) {
            $permission = false;
        }

        return $auth && $permission;
    }
}
