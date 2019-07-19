<?php

namespace Api\Common;

use API\Interfaces\CreateInterface;
use Modules;
use Uploads;

class Allegato implements CreateInterface
{
    public function create($request)
    {
        $module = Modules::get($request['module']);

        $upload = Uploads::upload($_FILES['upload'], [
            'name' => $request['name'],
            'id_module' => $module['id'],
            'id_record' => $request['id'],
        ]);

        return[
            'filename' => $upload,
        ];
    }
}
