<?php

namespace API;

class Resource
{
    /**
     * @param $request
     *
     * @return bool se true, la richiesta di apertura di considera fallita e viene restituito 404
     */
    public function open($request)
    {
        return false;
    }

    /**
     * @param $request
     * @param $response
     *
     * @retrun void
     */
    public function close($request, $response)
    {
    }

    public function getUser()
    {
        return auth()->getUser();
    }
}
