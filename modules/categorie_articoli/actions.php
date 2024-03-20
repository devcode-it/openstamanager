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
use Modules\Articoli\Categoria;

switch (filter('op')) {
    case 'update':
        $nome = filter('nome');
        $nota = filter('nota');
        $colore = filter('colore');
        $id_original = filter('id_original') ?: null;

        if (isset($nome) && isset($nota) && isset($colore)) {
            $categoria->nota = $nota;
            $categoria->colore = $colore;
            $categoria->parent = $id_original ?: null;
            $categoria->setTranslation('name', $nome);
            $categoria->save();

            flash()->info(tr('Salvataggio completato!'));

        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio!'));
        }

        // Redirect alla categoria se si sta modificando una sottocategoria
        if ($id_original != null) {
            $database->commitTransaction();
            redirect(base_path().'/editor.php?id_module='.$id_module.'&id_record='.($id_original ?: $id_record));
            exit;
        }

        break;

    case 'add':
        $nome = filter('nome');
        $nota = filter('nota');
        $colore = filter('colore');
        $id_original = filter('id_original') ?: null;

        $categoria_new = Categoria::where('id', "=", (new Categoria())->getByField('name', $nome));
        if (!empty($id_original)) {
            $categoria_new = $categoria_new->where('parent', '=', $id_original);
        } else {
            $categoria_new = $categoria_new->whereNull('parent');
        }
        $categoria_new = $categoria_new->first();

        if (!empty($categoria_new)){
            flash()->error(tr('Questo nome è già stato utilizzato per un altra categoria.'));
        } else {
            $categoria = Categoria::build($nota, $colore);
            $id_record= $dbo->lastInsertedID();
            $categoria->parent = $id_original;
            $categoria->setTranslation('name', $nome);
            $categoria->save();

            flash()->info(tr('Aggiunta nuova tipologia di _TYPE_', [
                '_TYPE_' => 'categoria',
            ]));
        }
        
        if (isAjaxRequest()) {
            echo json_encode(['id' => $id_record, 'text' => $nome]);
        } else {
            // Redirect alla categoria se si sta aggiungendo una sottocategoria
            $database->commitTransaction();
            redirect(base_path().'/editor.php?id_module='.$id_module.'&id_record='.($id_original ?: $id_record));
            exit;
        }

        break;

    case 'delete':
        $id = filter('id');
        if (empty($id)) {
            $id = $id_record;
        }

        if ($dbo->fetchNum('SELECT * FROM `mg_articoli` WHERE (`id_categoria`='.prepare($id).' OR `id_sottocategoria`='.prepare($id).'  OR `id_sottocategoria` IN (SELECT `id` FROM `mg_categorie` WHERE `parent`='.prepare($id).')) AND `deleted_at` IS NULL') == 0) {
            $dbo->query('DELETE FROM `mg_categorie` WHERE `id`='.prepare($id));

            flash()->info(tr('Tipologia di _TYPE_ eliminata con successo!', [
                '_TYPE_' => 'categoria',
            ]));
        } else {
            flash()->error(tr('Esistono alcuni articoli collegati a questa categoria. Impossibile eliminarla.'));
        }

        break;
}
