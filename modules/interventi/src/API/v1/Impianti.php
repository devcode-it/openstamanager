<?php

namespace Modules\Interventi\API\v1;

use API\Interfaces\CreateInterface;
use API\Interfaces\DeleteInterface;
use API\Interfaces\RetrieveInterface;
use API\Interfaces\UpdateInterface;
use API\Resource;
use Modules;
use Modules\Anagrafiche\Anagrafica;

class Impianti extends Resource implements RetrieveInterface, CreateInterface
{
    public function retrieve($request)
    {
        $query = 'SELECT idimpianto AS id_impianto, idintervento AS id_intervento FROM my_impianti_interventi WHERE `idintervento` = :id_intervento';

        $parameters = [
            ':id_intervento' => $request['id_intervento'],
        ];

        return [
            'query' => $query,
            'parameters' => $parameters,
        ];
    }

    public function create($request)
    {
        $data = $request['data'];
        $id_record = $data['id_intervento'];

        $database = database();
        $database->query('DELETE FROM my_impianti_interventi WHERE `idintervento` = :id_intervento',  [
            ':id_intervento' => $id_record,
        ]);

        $impianti = $data['impianti'];
        foreach ($impianti as $impianto) {
            $database->insert('my_impianti_interventi', [
                'idintervento' => $id_record,
                'idimpianto' => $impianto,
            ]);
        }
    }
}
