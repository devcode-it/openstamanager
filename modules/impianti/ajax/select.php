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

switch ($resource) {
    case 'impianti':
        $query = 'SELECT id, CONCAT(matricola, " - ", nome) AS descrizione FROM my_impianti |where| ORDER BY id, idanagrafica';

        foreach ($elements as $element) {
            $filter[] = 'id='.prepare($element);
        }

        if (!empty($search)) {
            $search_fields[] = 'nome LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'matricola LIKE '.prepare('%'.$search.'%');
        }
        break;

        /*
         * Opzioni utilizzate:
         * - idanagrafica
         */
    case 'impianti-cliente':
        $query = 'SELECT my_impianti.id, CONCAT(my_impianti.matricola, " - ", my_impianti.nome) AS descrizione, my_impianti.idanagrafica, an_anagrafiche.ragione_sociale, my_impianti.idsede, IFNULL(an_sedi.nomesede, "Sede legale") AS nomesede FROM my_impianti LEFT JOIN an_anagrafiche ON my_impianti.idanagrafica=an_anagrafiche.idanagrafica LEFT JOIN an_sedi ON my_impianti.idsede=an_sedi.id |where| ORDER BY idsede';

        foreach ($elements as $element) {
            $filter[] = 'my_impianti.id='.prepare($element);
        }

        if (!empty($superselect['idanagrafica'])) {
            $where[] = 'my_impianti.idanagrafica='.prepare($superselect['idanagrafica']);
            $where[] = 'my_impianti.idsede='.prepare($superselect['idsede_destinazione'] ?: 0);
        }

        if (!empty($superselect['idintervento'])) {
            $where[] = 'my_impianti.id NOT IN(SELECT idimpianto FROM my_impianti_interventi WHERE idintervento='.prepare($superselect['idintervento']).')';
        }

        if (!empty($search)) {
            $search_fields[] = 'my_impianti.nome LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'my_impianti.matricola LIKE '.prepare('%'.$search.'%');
        }

        break;

        /*
         * Opzioni utilizzate:
         * - idintervento
         */
    case 'impianti-intervento':
        if (isset($superselect['idintervento'])) {
            $query = 'SELECT id, CONCAT(matricola, " - ", nome) AS descrizione FROM my_impianti INNER JOIN my_impianti_interventi ON my_impianti.id=my_impianti_interventi.idimpianto |where| ORDER BY idsede';

            foreach ($elements as $element) {
                $filter[] = 'id='.prepare($element);
            }

            $where[] = 'my_impianti_interventi.idintervento='.prepare($superselect['idintervento']);

            if (!empty($search)) {
                $search_fields[] = 'nome LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'matricola LIKE '.prepare('%'.$search.'%');
            }
        }
        break;

        /*
         * Opzioni utilizzate:
         * - matricola
         */
    case 'componenti':
        if (isset($superselect['matricola'])) {
            $query = 'SELECT 
                `my_componenti`.`id`, 
                CONCAT("#", `my_componenti`.`id`, ": ", `mg_articoli`.`codice`, " - ", `mg_articoli_lang`.`name`) AS descrizione
            FROM 
                `my_componenti`
                INNER JOIN `mg_articoli` ON `mg_articoli`.`id` = `my_componenti`.`id_articolo`
                LEFT JOIN `mg_articoli_lang` ON (`mg_articoli_lang`.`id_record` = `mg_articoli`.`id` AND `mg_articoli_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
            |where| 
            ORDER BY 
                `my_componenti`.`id`';

            foreach ($elements as $element) {
                $filter[] = '`my_componenti`.`id` = '.prepare($element);
            }

            $where = [
                '`my_componenti`.`data_sostituzione` IS NULL',
                '`my_componenti`.`data_rimozione` IS NULL',
            ];

            $impianti = $superselect['matricola'];
            if (!empty($impianti)) {
                $where[] = '`my_componenti`.`id_impianto` IN ('.$impianti.')';
            }

            if (!empty($search)) {
                $search[] = '`my_componenti`.`note` LIKE '.prepare('%'.$search.'%');
            }
        }

        break;

    case 'categorie_imp':
        $query = 'SELECT `id`, `nome` AS descrizione FROM `my_impianti_categorie` |where| ORDER BY `nome`';

        foreach ($elements as $element) {
            $filter[] = '`id`='.prepare($element);
        }

        $where[] = '`parent` IS NULL';

        if (!empty($search)) {
            $search_fields[] = '`nome` LIKE '.prepare('%'.$search.'%');
        }

        break;

        /*
         * Opzioni utilizzate:
         * - id_categoria
         */
    case 'sottocategorie_imp':
        if (isset($superselect['id_categoria'])) {
            $query = 'SELECT `id`, `nome` AS descrizione FROM `my_impianti_categorie` |where| ORDER BY `nome`';

            foreach ($elements as $element) {
                $filter[] = '`id`='.prepare($element);
            }

            $where[] = '`parent`='.prepare($superselect['id_categoria']);

            if (!empty($search)) {
                $search_fields[] = '`nome` LIKE '.prepare('%'.$search.'%');
            }
        }
        break;
}
