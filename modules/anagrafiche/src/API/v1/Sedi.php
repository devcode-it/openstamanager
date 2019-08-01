<?php

namespace Modules\Anagrafiche\API\v1;

use API\Interfaces\CreateInterface;
use API\Interfaces\DeleteInterface;
use API\Interfaces\RetrieveInterface;
use API\Interfaces\UpdateInterface;
use API\Resource;
use Modules;
use Modules\Anagrafiche\Anagrafica;

class Sedi extends Resource implements RetrieveInterface
{
    public function retrieve($request)
    {
        return [
            'table' => 'an_sedi',
        ];
    }
}
