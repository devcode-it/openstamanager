<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Modules\Interventi\API\v1;

use API\Interfaces\CreateInterface;
use API\Interfaces\RetrieveInterface;
use API\Resource;
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Interventi\Components\Articolo;
use Modules\Interventi\Components\Riga;
use Modules\Interventi\Components\Descrizione;
use Modules\Interventi\Intervento;

class Righe extends Resource implements RetrieveInterface, CreateInterface
{
    public function retrieve($request)
    {
        $table = 'in_righe_interventi';

        $select = [
            'in_righe_interventi.id',
            'in_righe_interventi.idarticolo AS id_articolo',
            'in_righe_interventi.idintervento AS id_intervento',
            'in_righe_interventi.descrizione',
            'in_righe_interventi.qta',
            'in_righe_interventi.created_at as data',
        ];

        $where = [['in_righe_interventi.idintervento', '=', $request['id_intervento']]];

        return [
            'table' => $table,
            'select' => $select,
            'where' => $where,
        ];
    }

    public function create($request)
    {
        $data = $request['data'];
        $data['qta'] = ($data['qta'] ? $data['qta'] : 1);

        $intervento = Intervento::find($data['id_intervento']);
        $originale = ArticoloOriginale::find($data['id_articolo']);
        if ($data['is_articolo'] && !empty($originale)) {
            $riga = Articolo::build($intervento, $originale);

            $riga->descrizione = (!empty($data['descrizione']) ? $data['descrizione'] : $originale->descrizione);
            $riga->qta = $data['qta'];
            $riga->um = $data['um'];
            $riga->costo_unitario = $originale->prezzo_acquisto;
            if( $originale->prezzo_vendita>0 ){
                $idiva = ($originale->idiva_vendita ? $originale->idiva_vendita : setting('Iva predefinita'));
                $riga->setPrezzoUnitario($originale->prezzo_vendita, $idiva);
            }else{
                $riga->prezzo_unitario = 0;
            }
        } elseif ($data['is_descrizione']) {
            $riga = Descrizione::build($intervento);

            $riga->qta = 0;
            $riga->descrizione = ($data['descrizione'] ? $data['descrizione'] : '-');
        } else {
            $riga = Riga::build($intervento);

            $riga->qta = $data['qta'];
            $riga->um = $data['um'];
            $riga->descrizione = ($data['descrizione'] ? $data['descrizione'] : '-');
            $riga->costo_unitario = 0;
            $riga->setPrezzoUnitario(0, setting('Iva predefinita'));
        }

        $riga->save();
    }

    public function delete($request)
    {
        $riga = Articolo::find($request['id']) ?: Riga::find($request['id']);
        $riga = $riga ?: Descrizione::find($request['id']);
        $riga->delete();
    }

    public function update($request)
    {
        $data = $request['data'];
        $data['qta'] = ($data['qta'] ? $data['qta'] : 1);

        $originale = ArticoloOriginale::find($data['id_articolo']);
        $riga = Articolo::find($data['id_riga']) ?: Riga::find($data['id_riga']);

        $riga->qta = $data['qta'];
        if(!empty($data['id_articolo']) && !empty($originale)){
            $descrizione = (!empty($data['descrizione']) ? $data['descrizione'] : $originale->descrizione);
            $descrizione = ($descrizione ? $descrizione : '-');

            $riga->descrizione  = $descrizione;
            $riga->idarticolo = $originale->id;
            $riga->costo_unitario = $originale->prezzo_acquisto;
            $idiva = ($originale->idiva_vendita ? $originale->idiva_vendita : setting('Iva predefinita'));
            $riga->setPrezzoUnitario($originale->prezzo_vendita, $idiva);
        }else{
            $riga->descrizione = ($data['descrizione'] ? $data['descrizione'] : '-');
            $riga->costo_unitario = 0;
            $riga->setPrezzoUnitario(0, setting('Iva predefinita'));
        }
        
        $riga->um = $data['um'] ?: null;
        $riga->save();
    }

}
