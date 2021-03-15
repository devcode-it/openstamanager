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
        $query = 'SELECT *,
            (SELECT nome FROM mg_categorie WHERE id = mg_articoli.id_categoria) AS categoria,
            (SELECT nome FROM mg_categorie WHERE id = mg_articoli.id_sottocategoria) AS sottocategoria
        FROM mg_articoli WHERE attivo = 1 AND deleted_at IS NULL';

        return [
            'query' => $query,
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

        $articolo->descrizione = $data['descrizione'];
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
