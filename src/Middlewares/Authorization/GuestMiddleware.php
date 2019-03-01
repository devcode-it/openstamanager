<?php

namespace Middlewares\Authorization;

class GuestMiddleware extends UserMiddleware
{
    protected function operation($request, $response)
    {
        throw new \Slim\Exception\NotFoundException($request, $response);
    }

    protected function hasAuthorization($request)
    {
        return !$this->auth->check();
    }
}
