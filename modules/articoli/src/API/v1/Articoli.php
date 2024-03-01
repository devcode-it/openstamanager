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

namespace Modules\Articoli\API\v1;

use API\Interfaces\CreateInterface;
use API\Interfaces\RetrieveInterface;
use API\Interfaces\UpdateInterface;
use API\Resource;
use Modules\Articoli\Articolo;
use Modules\Articoli\Categoria;

class Articoli extends Resource implements RetrieveInterface, UpdateInterface, CreateInterface
{
    public function retrieve($request)
    {
        $table = 'mg_articoli';
        $select = [
            'mg_articoli.*',
            'categorie.nome AS categoria',
            'sottocategorie.nome AS sottocategoria',
        ];

        $joins[] = [
            'mg_articoli_lang',
            'mg_articoli.id',
            'mg_articoli_lang.id_record',
        ];

        $joins[] = [
            'mg_categorie AS categorie',
            'mg_articoli.id_categoria',
            'categorie.id',
        ];

        $joins[] = [
            'mg_categorie_lang AS categorie_lang',
            'mg_categorie.id',
            'categorie_lang.id_record',
        ];

        $joins[] = [
            'mg_categorie AS sottocategorie',
            'mg_articoli.id_sottocategoria',
            'sottocategorie.id',
        ];

        $joins[] = [
            'mg_categorie_lang AS sottocategorie_lang',
            'mg_categorie.id',
            'sottocategorie_lang.id_record',
        ];

        $where[] = ['mg_articoli.deleted_at', '=', null];

        $whereraw = [];

        $order['mg_articoli.id'] = 'ASC';

        return [
            'table' => $table,
            'select' => $select,
            'joins' => $joins,
            'where' => $where,
            'whereraw' => $whereraw,
            'order' => $order,
        ];
    }

    public function create($request)
    {
        $data = $request['data'];

        // Gestione categoria
        list($categoria, $sottocategoria) = $this->gestioneCategorie($data['categoria'], $data['sottocategoria']);

        $articolo = Articolo::build($data['codice'], $data['descrizione'], $categoria, $sottocategoria);
        $articolo->setPrezzoVendita($data['prezzo_vendita'], $articolo->idiva_vendita);
        $articolo->save();

        return [
            'id' => $articolo->id,
        ];
    }

    public function update($request)
    {
        $data = $request['data'];

        $articolo = Articolo::find($request['id']);
        list($categoria, $sottocategoria) = $this->gestioneCategorie($data['categoria'], $data['sottocategoria']);

        // Gestione categoria
        if (!empty($categoria)) {
            $articolo->categoria()->associate($categoria);
        }
        if (!empty($sottocategoria)) {
            $articolo->sottocategoria()->associate($sottocategoria);
        }

        $articolo->name = $data['descrizione'];
        $articolo->setPrezzoVendita($data['prezzo_vendita'], $articolo->idiva_vendita);

        $articolo->save();
    }

    protected function gestioneCategorie($nome_categoria, $nome_sottocategoria)
    {
        $sottocategoria = null;

        // Gestione categoria
        $categoria = Categoria::where('nome', '=', $nome_categoria)
            ->first();
        if (empty($categoria) && !empty($nome_categoria)) {
            $categoria = Categoria::build($nome_categoria);
            $categoria->save();
        }

        // Caso categoria inesistente
        if (empty($categoria)) {
            return [$categoria, $sottocategoria];
        }

        // Gestione sotto-categoria
        $sottocategoria = Categoria::where('nome', '=', $nome_sottocategoria)
            ->where('parent', '=', $categoria->id)
            ->first();
        if (empty($sottocategoria) && !empty($nome_sottocategoria)) {
            $sottocategoria = Categoria::build($nome_sottocategoria);
            $sottocategoria->parent = $categoria->id;
            $sottocategoria->save();
        }

        return [$categoria, $sottocategoria];
    }
}
