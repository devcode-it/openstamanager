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

namespace Modules\Preventivi\API\v1;

use API\Interfaces\CreateInterface;
use API\Interfaces\DeleteInterface;
use API\Interfaces\RetrieveInterface;
use API\Interfaces\UpdateInterface;
use API\Resource;
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Preventivi\Components\Articolo;
use Modules\Preventivi\Components\Descrizione;
use Modules\Preventivi\Components\Riga;
use Modules\Preventivi\Preventivo;

class Righe extends Resource implements RetrieveInterface, CreateInterface, UpdateInterface, DeleteInterface
{
    public function retrieve($request)
    {
        $table = 'co_righe_preventivi';

        $select = [
            'co_righe_preventivi.id',
            'co_righe_preventivi.id_articolo AS id_articolo',
            'co_righe_preventivi.id_preventivo AS id_preventivo',
            'co_righe_preventivi.descrizione',
            'co_righe_preventivi.qta',
            'co_righe_preventivi.created_at as data',
        ];

        $where = [['co_righe_preventivi.id_preventivo', '=', $request['id_preventivo']]];

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

        $originale = ArticoloOriginale::find($data['id_articolo'] ?? null);
        $preventivo = Preventivo::find($data['id_preventivo']);

        if (!empty($data['is_descrizione'])) {
            $riga = Descrizione::build($preventivo);
            $riga->descrizione = ($data['descrizione'] ?: '-');
            $riga->qta = 0;
        } elseif (!empty($data['id_articolo']) && !empty($originale)) {
            $riga = Articolo::build($preventivo, $originale);

            if ($originale->prezzo_vendita > 0) {
                $id_iva = ($originale->id_iva_vendita ?: setting('Iva predefinita'));
                $riga->setPrezzoUnitario($originale->prezzo_vendita, $id_iva);
            } else {
                $riga->prezzo_unitario = 0;
            }
            $riga->costo_unitario = $originale->prezzo_acquisto;
            $riga->qta = $data['qta'];
            $riga->descrizione = (!empty($data['descrizione']) ? $data['descrizione'] : $originale->descrizione);
            $riga->confermato = setting('Conferma automaticamente le quantità nei preventivi');
        } else {
            $riga = Riga::build($preventivo);
            $riga->qta = $data['qta'];
            $riga->descrizione = ($data['descrizione'] ?: '-');
            $riga->costo_unitario = 0;
            $riga->setPrezzoUnitario(0, setting('Iva predefinita'));
            $riga->confermato = setting('Conferma automaticamente le quantità nei preventivi');
        }

        $riga->um = $data['um'] ?? null;
        $riga->save();

        return [
            'id' => $riga->id_preventivo,
            'module' => 'Preventivi',
            'op' => 'manage_riga',
        ];
    }

    public function update($request)
    {
        $data = $request['data'];
        $data['qta'] = ($data['qta'] > 0 ? $data['qta'] : 1);

        $originale = ArticoloOriginale::find($data['id_articolo'] ?? null);

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
            $riga->id_articolo = $originale->id;
            $id_iva = ($originale->id_iva_vendita ?: setting('Iva predefinita'));
            $riga->setPrezzoUnitario($originale->prezzo_vendita, $id_iva);
            $riga->confermato = setting('Conferma automaticamente le quantità nei preventivi');
        } else {
            $riga->descrizione = ($data['descrizione'] ?: '-');
            $riga->costo_unitario = 0;
            $riga->setPrezzoUnitario(0, setting('Iva predefinita'));
            $riga->confermato = setting('Conferma automaticamente le quantità nei preventivi');
        }

        $riga->um = $data['um'] ?? null;
        $riga->save();

        return [
            'id' => $riga->id_preventivo,
            'module' => 'Preventivi',
            'op' => 'manage_riga',
        ];
    }

    public function delete($request)
    {
        $data = $request['data'];
        $riga = Articolo::find($data['id_riga']) ?: Riga::find($data['id_riga']);
        $riga = $riga ?: Descrizione::find($data['id_riga']);

        $preventivo = Preventivo::find($riga->id_preventivo);
        $riga->delete();

        return [
            'id' => $riga->id_preventivo,
            'module' => 'Preventivi',
            'op' => 'delete_riga',
        ];
    }
}
