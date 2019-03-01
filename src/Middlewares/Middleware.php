<?php

namespace Middlewares;

abstract class Middleware
{
    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function __get($property)
    {
        if (isset($this->container[$property])) {
            return $this->container[$property];
        }
    }

    abstract public function __invoke($request, $response, $next);
}
