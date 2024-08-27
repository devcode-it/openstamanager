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

namespace Modules\DDT\API\v1;

use API\Interfaces\CreateInterface;
use API\Interfaces\RetrieveInterface;
use API\Resource;
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\DDT\Components\Articolo;
use Modules\DDT\Components\Descrizione;
use Modules\DDT\Components\Riga;
use Modules\DDT\DDT;

class Righe extends Resource implements RetrieveInterface, CreateInterface
{
    public function retrieve($request)
    {
        $table = 'dt_righe_ddt';

        $select = [
            'dt_righe_ddt.id',
            'dt_righe_ddt.idarticolo AS id_articolo',
            'dt_righe_ddt.idddt AS id_ddt',
            'dt_righe_ddt.descrizione',
            'dt_righe_ddt.qta',
            'dt_righe_ddt.created_at as data',
        ];

        $where = [['dt_righe_ddt.idddt', '=', $request['id_ddt']]];

        return [
            'table' => $table,
            'select' => $select,
            'where' => $where,
        ];
    }

    public function create($request)
    {
        $data = $request['data'];
        $data['qta'] = ($data['qta'] > 0 ? $data['qta'] : 1);

        $originale = ArticoloOriginale::find($data['id_articolo']);
        $ddt = DDT::find($data['id_ddt']);

        if (!empty($data['is_descrizione'])) {
            $riga = Descrizione::build($ddt);
            $riga->descrizione = ($data['descrizione'] ?: '-');
            $riga->qta = 0;
        } elseif (!empty($data['id_articolo']) && !empty($originale)) {
            $riga = Articolo::build($ddt, $originale);

            if ($originale->prezzo_vendita > 0) {
                $idiva = ($originale->idiva_vendita ?: setting('Iva predefinita'));
                $riga->setPrezzoUnitario($originale->prezzo_vendita, $idiva);
            } else {
                $riga->prezzo_unitario = 0;
            }
            $riga->costo_unitario = $originale->prezzo_acquisto;
            $riga->qta = $data['qta'];
            $riga->descrizione = (!empty($data['descrizione']) ? $data['descrizione'] : $originale->descrizione);
        } else {
            $riga = Riga::build($ddt);
            $riga->qta = $data['qta'];
            $riga->descrizione = ($data['descrizione'] ?: '-');
            $riga->costo_unitario = 0;
            $riga->setPrezzoUnitario(0, setting('Iva predefinita'));
        }

        $riga->um = $data['um'] ?: null;
        $riga->save();
    }

    public function update($request)
    {
        $data = $request['data'];
        $data['qta'] = ($data['qta'] > 0 ? $data['qta'] : 1);

        $originale = ArticoloOriginale::find($data['id_articolo']);

        $riga = Articolo::find($data['id_riga']) ?: Riga::find($data['id_riga']);
        $riga = $riga ?: Descrizione::find($data['id_riga']);

        $riga->is_descrizione = 0;
        $riga->qta = $data['qta'];
        if (!empty($data['is_descrizione'])) {
            $riga->qta = 0;
            $riga->is_descrizione = 1;
            $riga->descrizione = ($data['descrizione'] ?: '-');
        } elseif (!empty($data['id_articolo']) && !empty($originale)) {
            $descrizione = (!empty($data['descrizione']) ? $data['descrizione'] : $originale->descrizione);
            $descrizione = ($descrizione ?: '-');

            $riga->descrizione = ($descrizione ?: '-');
            $riga->costo_unitario = $originale->prezzo_acquisto;
            $riga->idarticolo = $originale->id;
            $idiva = ($originale->idiva_vendita ?: setting('Iva predefinita'));
            $riga->setPrezzoUnitario($originale->prezzo_vendita, $idiva);
        } else {
            $riga->descrizione = ($data['descrizione'] ?: '-');
            $riga->costo_unitario = 0;
            $riga->setPrezzoUnitario(0, setting('Iva predefinita'));
        }

        $riga->um = $data['um'] ?: null;
        $riga->save();
    }

    public function delete($request)
    {
        $riga = Articolo::find($request['id']) ?: Riga::find($request['id']);
        $riga = $riga ?: Descrizione::find($request['id']);
        $riga->delete();

        ricalcola_costiagg_ddt($riga->idddt);
    }
}
