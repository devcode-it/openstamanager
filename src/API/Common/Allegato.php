<?php

namespace API\Common;

use API\Interfaces\CreateInterface;
use API\Resource;
use Models\Upload;
use Modules;

class Allegato extends Resource implements CreateInterface
{
    public function create($request)
    {
        $module = Modules::get($request['module']);

        $name = !empty($request['name']) ? $request['name'] : null;
        $category = !empty($request['category']) ? $request['category'] : null;

        $upload = Upload::build($_FILES['upload'], [
            'id_module' => $module['id'],
            'id_record' => $request['id'],
        ], $name, $category);

        return[
            'id' => $upload->id,
            'filename' => $upload->filename,
        ];
    }
}
