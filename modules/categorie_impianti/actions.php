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

use Modules\Checklists\Check;

$modulo_impianti = Modules::get('Impianti');

switch (filter('op')) {
    case 'update':
        $nome = filter('nome');
        $nota = filter('nota');
        $colore = filter('colore');
        $id_original = filter('id_original') ?: null;

        if (isset($nome) && isset($nota) && isset($colore)) {
            $database->table('my_impianti_categorie')
                ->where('id', '=', $id_record)
                ->update([
                    'nome' => $nome,
                    'nota' => $nota,
                    'colore' => $colore,
                ]);

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

        // Ricerca corrispondenze con stesso nome
        $corrispondenze = $database->table('my_impianti_categorie')
            ->where('nome', '=', $nome);
        if (!empty($id_original)) {
            $corrispondenze = $corrispondenze->where('parent', '=', $id_original);
        } else {
            $corrispondenze = $corrispondenze->whereNull('parent');
        }
        $corrispondenze = $corrispondenze->get();

        // Eventuale creazione del nuovo record
        if ($corrispondenze->count() == 0) {
            $id_record = $database->table('my_impianti_categorie')
                ->insertGetId([
                    'nome' => $nome,
                    'nota' => $nota,
                    'colore' => $colore,
                    'parent' => $id_original,
                ]);

            flash()->info(tr('Aggiunta nuova tipologia di _TYPE_', [
                '_TYPE_' => 'categoria',
            ]));
        } else {
            $id_record = $corrispondenze->first()->id;
            flash()->error(tr('Esiste già una categoria con lo stesso nome!'));
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

        if ($dbo->fetchNum('SELECT * FROM `my_impianti` WHERE (`id_categoria`='.prepare($id).' OR `id_sottocategoria`='.prepare($id).'  OR `id_sottocategoria` IN (SELECT id FROM `my_impianti_categorie` WHERE `parent`='.prepare($id).')) AND `deleted_at` IS NULL') == 0) {
            $dbo->query('DELETE FROM `my_impianti_categorie` WHERE `id`='.prepare($id));

            flash()->info(tr('_TYPE_ eliminata con successo!', [
                '_TYPE_' => 'categoria',
            ]));
        } else {
            flash()->error(tr('Esistono alcuni impianti collegati a questa categoria. Impossibile eliminarla.'));
        }

        break;

    case 'sync_checklist':
        $checks_categoria = $dbo->fetchArray('SELECT * FROM zz_checks WHERE id_module = '.prepare($id_module).' AND id_record = '.prepare($id_record));

        $impianti = $dbo->select('my_impianti', '*', [], ['id_categoria' => $id_record]);
        foreach ($impianti as $impianto) {
            Check::deleteLinked([
                'id_module' => $modulo_impianti['id'],
                'id_record' => $impianto['id'],
            ]);
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
            }
        }
        flash()->info(tr('Impianti sincronizzati correttamente!'));

        break;
}
