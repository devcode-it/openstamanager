<?php

namespace API\Common;

use API\Interfaces\CreateInterface;
use API\Interfaces\RetrieveInterface;
use API\Resource;
use Models\Upload;
use Modules;

class Allegato extends Resource implements RetrieveInterface, CreateInterface
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

        return [
            'id' => $upload->id,
            'filename' => $upload->filename,
        ];
    }

    public function retrieve($request)
    {
        $upload = Upload::where('name', $request['name'])
            ->where('id', $request['id'])
            ->where('id_record', $request['id_record'])
            ->first();
        if (!empty($upload)) {
            download(DOCROOT.'/'.$upload->filepath, $upload->original_name);
        }

        return [
            'custom' => '',
        ];
    }
}
