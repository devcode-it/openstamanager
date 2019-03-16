<?php

namespace Middlewares\Authorization;

/**
 * @since 2.5
 */
class GuestMiddleware extends UserMiddleware
{
    protected function hasAuthorization($request)
    {
        return !parent::hasAuthorization($request);
    }
}
