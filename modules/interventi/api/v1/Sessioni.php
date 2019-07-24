<?php

namespace Modules\Interventi\API\v1;

use API\Interfaces\CreateInterface;
use API\Interfaces\DeleteInterface;
use API\Interfaces\RetrieveInterface;
use API\Resource;

class Sessioni extends Resource implements RetrieveInterface, CreateInterface, DeleteInterface
{
    public function retrieve($request)
    {
        $user = $this->getUser();

        $query = 'SELECT id, idtecnico AS id_tecnico, idintervento AS id_intervento, orario_inizio, orario_fine FROM in_interventi_tecnici WHERE `idintervento` = :id_intervento';

        $parameters = [
            ':id_intervento' => $request['id_intervento'],
        ];

        if ($user['gruppo'] == 'Tecnici') {
            $query .= ' AND `idtecnico` = :id_tecnico';
            $parameters[':id_tecnico'] = $user['idanagrafica'];
        }

        return [
            'query' => $query,
            'parameters' => $parameters,
        ];
    }

    public function create($request)
    {
        $user = $this->getUser();
        $data = $request['data'];

        add_tecnico($data['id_intervento'], $user['idanagrafica'], $data['orario_inizio'], $data['orario_fine']);
    }

    public function delete($request)
    {
        $database = database();
        $user = $this->getUser();

        $database->query('DELETE FROM `in_interventi_tecnici` WHERE `idintervento` = :id_intervento AND `idtecnico` = :id_tecnico', [
            ':id_intervento' => $request['id_intervento'],
            ':id_tecnico' => $user['idanagrafica'],
        ]);
    }
}
