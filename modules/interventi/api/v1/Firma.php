<?php

namespace Modules\Interventi\API\v1;

use API\Interfaces\UpdateInterface;

class Firma implements UpdateInterface
{
    public function update($request)
    {
        $database = database();
        $data = $request['data'];

        $database->update('in_interventi', [
            'firma_file' => $data['firma_file'],
            'firma_data' => $data['firma_data'],
            'firma_nome' => $data['firma_nome'],
        ], ['id' => $data['id']]);
    }
}
