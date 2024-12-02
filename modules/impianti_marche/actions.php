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

use Models\Module;
use Modules\Impianti\Marca;

$id_modulo_marca_impianti = Module::where('name', 'Marche impianti')->first()->id;

switch (filter('op')) {
    case 'update':
        $title = filter('title');
        $id_original = filter('id_original') ?: 0;

        if (isset($title)) {
            $marca->parent = $id_original;
            $marca->setTranslation('title', $title);
            $marca->save();

            flash()->info(tr('Salvataggio completato!'));
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio!'));
        }

        // Redirect alla marca se si sta modificando una sottomarca
        if (!empty($id_original)) {
            $database->commitTransaction();
            redirect(base_path().'/editor.php?id_module='.$id_module.'&id_record='.($id_original ?: $id_record));
            exit;
        }

        break;

    case 'add':
        $title = filter('title');
        $id_original = filter('id_original') ?: null;

        $marca_new = Marca::where('id', '=', (new Marca())->getByField('title', $title));
        if (!empty($id_original)) {
            $marca_new = $marca_new->where('parent', '=', $id_original);
        } else {
            $marca_new = $marca_new->whereNull('parent');
        }
        $marca_new = $marca_new->first();

        if (!empty($marca_new)) {
            flash()->error(tr('Questo nome è già stato utilizzato per un altra marca.'));
        } else {
            $marca = Marca::build();
            $id_record = $dbo->lastInsertedID();
            $marca->parent = $id_original ?: 0;
            $marca->setTranslation('title', $title);
            $marca->save();

            flash()->info(tr('Aggiunta nuova tipologia di _TYPE_', [
                '_TYPE_' => 'marca',
            ]));
        }

        if (isAjaxRequest()) {
            echo json_encode(['id' => $id_record, 'text' => $title]);
        } else {
            // Redirect alla marca se si sta aggiungendo un modello
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

        if (empty($dbo->fetchArray('SELECT * FROM `my_impianti` WHERE (`id_marca`='.prepare($id).' OR `id_modello`='.prepare($id).'  OR `id_modello` IN (SELECT `id` FROM `my_impianti_marche` WHERE `parent`='.prepare($id).'))'))) {
            $dbo->query('DELETE FROM `my_impianti_marche` WHERE `id`='.prepare($id));

            flash()->info(tr('_TYPE_ eliminata con successo!', [
                '_TYPE_' => 'marca',
            ]));
        } else {
            flash()->error(tr('Esistono alcuni impianti collegati a questa marca. Impossibile eliminarla.'));
        }

        break;
}
