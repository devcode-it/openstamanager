<?php

namespace Modules\Interventi\API\v1;

use API\Interfaces\UpdateInterface;
use API\Resource;
use Models\Upload;

class Firma extends Resource implements UpdateInterface
{
    public function update($request)
    {
        $database = database();
        $data = $request['data'];

        //$file = Upload::find($data['file_id']);
        $database->update('in_interventi', [
            'firma_file' => $data['firma_file'],
            'firma_data' => $data['firma_data'],
            'firma_nome' => $data['firma_nome'],
        ], ['id' => $data['id']]);
    }
}
