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
use Modules\Checklists\Check;
use Modules\Impianti\Categoria;

$modulo_impianti = Module::where('name', 'Impianti')->first()->id;

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
            $categoria->setTranslation('title', $nome);
            $categoria->save();

            flash()->info(tr('Salvataggio completato!'));
        } else {
            flash()->error(tr('Ci sono stati alcuni errori durante il salvataggio!'));
        }

        // Redirect alla categoria se si sta modificando una sottocategoria
        if (!empty($id_original)) {
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
            $categoria = Categoria::build($nota, $colore);
            $id_record = $dbo->lastInsertedID();
            $categoria->parent = $id_original;
            $categoria->setTranslation('title', $nome);
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

        if (empty($dbo->fetchArray('SELECT * FROM `my_impianti` WHERE (`id_categoria`='.prepare($id).' OR `id_sottocategoria`='.prepare($id).'  OR `id_sottocategoria` IN (SELECT `id` FROM `my_impianti_categorie` WHERE `parent`='.prepare($id).'))'))) {
            $dbo->query('DELETE FROM `my_impianti_categorie` WHERE `id`='.prepare($id));

            flash()->info(tr('_TYPE_ eliminata con successo!', [
                '_TYPE_' => 'categoria',
            ]));
        } else {
            flash()->error(tr('Esistono alcuni impianti collegati a questa categoria. Impossibile eliminarla.'));
        }

        break;

    case 'sync_checklist':
        // Azzeramento checklist impianti della categoria
        database()->query('DELETE FROM `zz_checks` WHERE `id_module` = '.prepare($modulo_impianti['id']).' AND `id_record` IN(SELECT `id` FROM `my_impianti` WHERE `id_categoria` = '.prepare($id_record).')');

        $checks_categoria = $dbo->fetchArray('SELECT * FROM zz_checks WHERE id_module = '.prepare($id_module).' AND id_record = '.prepare($id_record));

        $impianti = $dbo->select('my_impianti', '*', [], ['id_categoria' => $id_record]);
        foreach ($impianti as $impianto) {
            foreach ($checks_categoria as $check_categoria) {
                $id_parent_new = null;
                if ($check_categoria['id_parent']) {
                    $parent = $dbo->selectOne('zz_checks', '*', ['id' => $check_categoria['id_parent']]);
                    $id_parent_new = $dbo->selectOne('zz_checks', '*', ['content' => $parent['content'], 'id_module' => $modulo_impianti['id'], 'id_record' => $impianto['id']])['id'];
                }
                $check = Check::build($user, $structure, $impianto['id'], $check_categoria['content'], $id_parent_new, $check_categoria['is_titolo'], $check_categoria['order']);
                $check->id_module = $modulo_impianti['id'];
                $check->id_plugin = null;
                $check->note = $check_categoria['note'];
                $check->save();

                // Riporto anche i permessi della check
                $users = [];
                $utenti = $dbo->table('zz_check_user')->where('id_check', $check_categoria['id'])->get();
                foreach ($utenti as $utente) {
                    $users[] = $utente->id_utente;
                }
                $check->setAccess($users, null);
            }
        }
        flash()->info(tr('Impianti sincronizzati correttamente!'));

        break;
}
