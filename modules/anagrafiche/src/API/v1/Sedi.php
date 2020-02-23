<?php

namespace Modules\Anagrafiche\API\v1;

use API\Interfaces\RetrieveInterface;
use API\Resource;

class Sedi extends Resource implements RetrieveInterface
{
    public function retrieve($request)
    {
        return [
            'table' => 'an_sedi',
        ];
    }
}
