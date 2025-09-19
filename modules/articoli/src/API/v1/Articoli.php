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
            'categorie_lang.title AS categoria',
            'sottocategorie_lang.title AS sottocategoria',
        ];

        $joins[] = [
            'mg_articoli_lang',
            'mg_articoli_lang.id_record',
            'mg_articoli.id',
            'mg_articoli_lang.id_lang',
            \Models\Locale::getDefault()->id,
        ];

        $joins[] = [
            'zz_categorie AS categorie',
            'mg_articoli.id_categoria',
            'categorie.id',
        ];

        $joins[] = [
            'zz_categorie_lang AS categorie_lang',
            'categorie_lang.id_record',
            'categorie.id',
            'categorie_lang.id_lang',
            \Models\Locale::getDefault()->id,
        ];

        $joins[] = [
            'zz_categorie AS sottocategorie',
            'mg_articoli.id_sottocategoria',
            'sottocategorie.id',
        ];

        $joins[] = [
            'zz_categorie_lang AS sottocategorie_lang',
            'sottocategorie_lang.id_record',
            'sottocategorie.id',
            'sottocategorie_lang.id_lang',
            \Models\Locale::getDefault()->id,
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
        [$categoria, $sottocategoria] = $this->gestioneCategorie($data['categoria'], $data['sottocategoria']);

        $articolo = Articolo::build($data['codice'], $categoria, $sottocategoria);
        $articolo->setPrezzoVendita($data['prezzo_vendita'], $articolo->idiva_vendita);
        $articolo->setTranslation('title', $data['descrizione']);
        $articolo->save();

        return [
            'id' => $articolo->id,
        ];
    }

    public function update($request)
    {
        $data = $request['data'];

        $articolo = Articolo::find($data['id']);
        [$categoria, $sottocategoria] = $this->gestioneCategorie($data['categoria'], $data['sottocategoria']);

        // Gestione categoria
        if (!empty($categoria)) {
            $articolo->id_categoria = post('categoria_edit') ?: post('categoria');
        }
        if (!empty($sottocategoria)) {
            $articolo->sottocategoria()->associate($sottocategoria);
        }

        $articolo->setTranslation('title', $data['descrizione']);
        $articolo->setPrezzoVendita($data['prezzo_vendita'], $articolo->idiva_vendita);

        $articolo->save();

        return [
            'id' => $articolo->id,
        ];
    }

    protected function gestioneCategorie($nome_categoria, $nome_sottocategoria)
    {
        $sottocategoria = null;

        // Gestione categoria
        $categoria = (new Categoria())->getByField('title', $nome_categoria);
        $categoria = Categoria::find($categoria);
        if (empty($categoria) && !empty($nome_categoria)) {
            $categoria = Categoria::build();
            $categoria->setTranslation('title', $nome_categoria);
            $categoria->save();
        }

        // Caso categoria inesistente
        if (empty($categoria)) {
            return [$categoria, $sottocategoria];
        }

        // Gestione sotto-categoria
        $sottocategoria = (new Categoria())->getByField('title', $nome_sottocategoria);
        $sottocategoria = Categoria::find($sottocategoria);
        if ( (empty($sottocategoria) && !empty($nome_sottocategoria)) || (!empty($nome_sottocategoria) && $sottocategoria->parent != $categoria->id) ){
            $sottocategoria = Categoria::build();
            $sottocategoria->setTranslation('title', $nome_sottocategoria);
            $sottocategoria->parent = $categoria->id;
            $sottocategoria->save();
        }

        return [$categoria, $sottocategoria];
    }
}
