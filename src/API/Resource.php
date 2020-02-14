<?php

namespace API;

class Resource
{
    public function open($request)
    {
    }

    public function close($request, $response)
    {
    }

    public function getUser()
    {
        return auth()->getUser();
    }
}
