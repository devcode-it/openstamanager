<?php

namespace Middlewares;

abstract class AuthorizationMiddleware extends Middleware
{
    public function __invoke($request, $response, $next)
    {
        if (!$this->hasAuthorization($request)) {
            $response = $this->operation($request, $response);
        } else {
            $response = $next($request, $response);
        }

        return $response;
    }

    abstract protected function operation($request, $response);

    abstract protected function hasAuthorization($request);
}
