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

include_once __DIR__.'/../../../core.php';
use Models\Module;

switch ($resource) {
    case 'moduli_token':
        $query = 'SELECT zz_modules.id AS id, title AS descrizione FROM zz_modules INNER JOIN zz_modules_lang ON zz_modules.id = zz_modules_lang.id_record AND zz_modules_lang.id_lang = '.prepare(Models\Locale::getDefault()->id).' |where| ORDER BY title';

        $where[] = 'enabled = 1';
        $where[] = "name IN('Anagrafiche', 'Gestione documentale', 'Impianti')";
        $where[] = "IF(options2 != '', options2!='menu', options!='menu')";

        foreach ($elements as $element) {
            $filter[] = 'zz_modules.id='.prepare($element);
        }

        if (!empty($search)) {
            $search_fields[] = 'title LIKE '.prepare('%'.$search.'%');
        }

        break;

    case 'record_token':
        $superselect['id_module_target'] = $superselect['id_module_target2'] ?? $superselect['id_module_target'];

        if (isset($superselect['id_module_target'])) {
            $module = Module::find($superselect['id_module_target']);

            if ($module->name == 'Anagrafiche') {
                $query = 'SELECT idanagrafica AS id, ragione_sociale AS descrizione FROM an_anagrafiche |where| ORDER BY ragione_sociale';

                $where[] = 'deleted_at IS NULL';

                foreach ($elements as $element) {
                    $filter[] = 'idanagrafica='.prepare($element);
                }

                $where[] = 'deleted_at IS NULL';

                if (!empty($search)) {
                    $search_fields[] = 'nome LIKE '.prepare('%'.$search.'%');
                }
            }

            if ($module->name == 'Gestione documentale') {
                $query = 'SELECT id, nome AS descrizione FROM do_documenti |where| ORDER BY nome';

                foreach ($elements as $element) {
                    $filter[] = 'id='.prepare($element);
                }

                if (!empty($search)) {
                    $search_fields[] = 'nome LIKE '.prepare('%'.$search.'%');
                }
            }

            if ($module->name == 'Impianti') {
                $query = 'SELECT id, nome AS descrizione FROM my_impianti |where| ORDER BY nome';

                foreach ($elements as $element) {
                    $filter[] = 'id='.prepare($element);
                }

                if (!empty($search)) {
                    $search_fields[] = 'nome LIKE '.prepare('%'.$search.'%');
                }
            }
        }
        break;
}
