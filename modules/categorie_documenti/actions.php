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

use Modules\CategorieDocumentali\Categoria;

switch (post('op')) {
    case 'update':
        $descrizione = post('descrizione');
        $categoria_new = Categoria::where('id', '=', (new Categoria())->getByField('title', $descrizione))->where('deleted_at', '=', null)->first();

        if (!empty($categoria_new) && $categoria_new->id != $id_record) {
            flash()->error(tr('Categoria _NAME_ già esistente!', [
                '_NAME_' => $descrizione,
            ]));
        } else {
            if (Models\Locale::getDefault()->id == Models\Locale::getPredefined()->id) {
                $categoria->name = $descrizione;
            }
            $categoria->save();

            $categoria->setTranslation('title', $descrizione);
            $categoria->syncPermessi(post('permessi') ?: []);

            flash()->info(tr('Informazioni salvate correttamente!'));
        }

        break;

    case 'add':
        $descrizione = post('descrizione');
        $categoria_new = Categoria::where('id', '=', (new Categoria())->getByField('title', $descrizione))->where('deleted_at', '=', null)->first();

        if (!empty($categoria_new) && $categoria_new->id != $id_record) {
            flash()->error(tr('Categoria _NAME_ già esistente!', [
                '_NAME_' => $descrizione,
            ]));
        } else {
            $categoria = Categoria::build();
            $categoria->name = $descrizione;
            $id_record = $dbo->lastInsertedID();
            $categoria->save();

            if (isAjaxRequest()) {
                echo json_encode(['id' => $id_record, 'text' => $descrizione]);
            }

            flash()->info(tr('Nuova categoria documenti aggiunta!'));
        }

        break;

    case 'delete':
        $dbo->query('UPDATE `do_categorie` SET `deleted_at` = NOW() WHERE `id` = '.prepare($id_record));

        flash()->info(tr('Categoria documenti eliminata!'));

        break;
}
