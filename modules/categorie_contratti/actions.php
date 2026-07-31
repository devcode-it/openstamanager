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

include_once __DIR__.'/../../core.php';
use Modules\Contratti\CategoriaContratto as Categoria;
use Modules\Articoli\Articolo;

switch (filter('op')) {
    case 'update':
        $nome = filter('nome');
        $nota = filter('nota');
        $colore = filter('colore');
        $id_original = filter('id_original') ?: null;

        if (isset($nome) && isset($nota) && isset($colore)) {
            $categoria->colore = $colore;
            $categoria->parent = $id_original ?: null;
            if (Models\Locale::getDefault()->id == Models\Locale::getPredefined()->id) {
                $categoria->name = $nome;
            }
            $categoria->setTranslation('note', $nota);
            $categoria->save();

            $categoria->setTranslation('title', $nome);
            flash()->info(tr('Salvataggio completato!'));
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio!'));
        }

        // Redirect alla categoria se si sta modificando una sottocategoria
        if (!empty($id_original)) {
            $database->commitTransaction();
            redirect_url(base_path_osm().'/editor.php?id_module='.$id_module.'&id_record='.($id_original ?: $id_record));
            exit;
        }

        break;

    case 'add':
        $nome = filter('nome');
        $nota = filter('nota');
        $colore = filter('colore');
        $id_original = filter('id_original') ?: null;

        $categoria_new = Categoria::where('id', '=', (new Categoria())->getByField('title', $nome));
        if (!empty($id_original)) {
            $categoria_new = $categoria_new->where('parent', '=', $id_original);
        } else {
            $categoria_new = $categoria_new->whereNull('parent');
        }
        $categoria_new = $categoria_new->first();

        if (!empty($categoria_new)) {
            flash()->error(tr('Questo nome è già stato utilizzato per un altra categoria.'));
        } else {
            $categoria = Categoria::build($colore);
            $id_record = $dbo->lastInsertedID();
            $categoria->name = $nome;
            $categoria->parent = $id_original;
            $categoria->save();

            $categoria->setTranslation('note', $nota);
            flash()->info(tr('Aggiunta nuova tipologia di _TYPE_', [
                '_TYPE_' => 'categoria',
            ]));
        }

        if (isAjaxRequest()) {
            echo json_encode(['id' => $id_record, 'text' => $nome]);
        } else {
            // Redirect alla categoria se si sta aggiungendo una sottocategoria
            $database->commitTransaction();
            redirect_url(base_path_osm().'/editor.php?id_module='.$id_module.'&id_record='.($id_original ?: $id_record));
            exit;
        }

        break;

    case 'delete':
        $id = filter('id');
        if (empty($id)) {
            $id = $id_record;
        }

        $subcategories_ids = Categoria::where('parent', $id)->pluck('id')->toArray();
        $has_articoli = Articolo::where(function ($query) use ($id, $subcategories_ids) {
            $query->where('id_categoria', $id)
                ->orWhere('id_sottocategoria', $id);
            if (!empty($subcategories_ids)) {
                $query->orWhereIn('id_sottocategoria', $subcategories_ids);
            }
        })->whereNull('deleted_at')->exists();

        if (!$has_articoli) {
            Categoria::find($id)->delete();

            flash()->info(tr('Tipologia di _TYPE_ eliminata con successo!', [
                '_TYPE_' => 'categoria',
            ]));
        } else {
            flash()->error(tr('Esistono alcuni contratti collegati a questa categoria. Impossibile eliminarla.'));
        }

        break;
}
