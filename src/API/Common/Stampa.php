<?php

namespace API\Common;

use API\Interfaces\RetrieveInterface;
use API\Resource;
use Models\PrintTemplate;
use Prints;

class Stampa extends Resource implements RetrieveInterface
{
    public function retrieve($request)
    {
        $content = '';

        $print = PrintTemplate::where('name', $request['name'])->first();
        if (!empty($print)) {
            $directory = DOCROOT.'/files/api';
            $data = Prints::render($print->id, $request['id_record'], $directory);

            download($data['path']);
            delete($data['path']);
        }

        return [
            'custom' => '',
        ];
    }
}
