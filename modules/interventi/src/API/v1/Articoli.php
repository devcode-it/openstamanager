<?php

namespace Modules\Interventi\API\v1;

use API\Interfaces\CreateInterface;
use API\Interfaces\RetrieveInterface;
use API\Resource;
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Interventi\Components\Articolo;
use Modules\Interventi\Intervento;

class Articoli extends Resource implements RetrieveInterface, CreateInterface
{
    public function retrieve($request)
    {
        $query = 'SELECT id, idarticolo AS id_articolo, idintervento AS id_intervento, qta, created_at as data FROM mg_articoli_interventi WHERE `idintervento` = :id_intervento';

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

        $originale = ArticoloOriginale::find($data['id_articolo']);
        $intervento = Intervento::find($data['id_intervento']);
        $articolo = Articolo::build($intervento, $originale);

        $articolo->qta = $data['qta'];
        $articolo->um = $data['um'];

        $articolo->save();
    }

    public function delete($request)
    {
        $database = database();

        $database->query('DELETE FROM `mg_articoli_interventi` WHERE `idintervento` = :id_intervento', [
            ':id_intervento' => $request['id_intervento'],
        ]);
    }
}
