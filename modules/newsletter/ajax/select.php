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
    case 'destinatari_newsletter':
        // Gestione campi di ricerca
        if (!empty($search)) {
            $search_fields[] = '|nome| LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '|table|.citta LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '|table|.provincia LIKE '.prepare('%'.$search.'%');
            $search_fields[] = '|table|.email LIKE '.prepare('%'.$search.'%');
        }

        // Aggiunta filtri di ricerca
        $where = empty($search_fields) ? '1=1' : '('.implode(' OR ', $search_fields).')';

        $destinatari = collect();

        // Gestione anagrafiche come destinatari
        $query = "SELECT CONCAT('anagrafica_', `an_anagrafiche`.`idanagrafica`) AS id,
           CONCAT(`an_anagrafiche`.`ragione_sociale`, IF(`an_anagrafiche`.`citta` != '' OR `an_anagrafiche`.`provincia` != '', CONCAT(' (', `an_anagrafiche`.`citta`, IF(`an_anagrafiche`.`provincia` != '', `an_anagrafiche`.`provincia`, ''), ')'), ''), ' [', `email`, ']') AS text,
           `an_tipianagrafiche_lang`.`name` AS optgroup
        FROM 
            `an_anagrafiche`
            INNER JOIN `an_tipianagrafiche_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `an_tipianagrafiche_anagrafiche`.`idanagrafica`
            INNER JOIN `an_tipianagrafiche` ON `an_tipianagrafiche_anagrafiche`.`idtipoanagrafica` = `an_tipianagrafiche`.`id`
            LEFT JOIN `an_tipianagrafiche_lang` ON (`an_tipianagrafiche_lang`.`id_record` = `an_tipianagrafiche`.id` AND `an_tipianagrafiche_lang`.`id_lang` = ".prepare(setting('Lingua')).')
        WHERE 
            `an_anagrafiche`.`deleted_at` IS NULL AND `an_anagrafiche`.`enable_newsletter` = 1 AND 1=1
        ORDER BY
            `optgroup` ASC, `ragione_sociale` ASC';

        $query = str_replace('1=1', !empty($where) ? replace($where, [
            '|nome|' => 'ragione_sociale',
            '|table|' => 'an_anagrafiche',
        ]) : '', $query);
        $anagrafiche = $database->fetchArray($query);
        $destinatari = $destinatari->concat($anagrafiche);

        // Gestione sedi come destinatari
        $query = "SELECT CONCAT('sede_', `an_sedi`.`id`) AS id,
           CONCAT(`an_anagrafiche`.`ragione_sociale`, ' (', `an_sedi`.`nomesede`, IF(`an_sedi`.`citta` != '' OR `an_sedi`.`provincia` != '', CONCAT(' :', `an_sedi`.`citta`, IF(`an_sedi`.`provincia` != '', `an_sedi`.`provincia`, ''), ''), ''), ')', ' [', `an_sedi`.`email`, ']') AS text,
           'Sedi' AS optgroup
        FROM 
            `an_sedi`
            INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `an_sedi`.`idanagrafica`
        WHERE 
            `an_anagrafiche`.`deleted_at` IS NULL AND `an_anagrafiche`.`enable_newsletter` = 1 AND 1=1
        ORDER BY 
            `optgroup` ASC, `ragione_sociale` ASC";

        $query = str_replace('1=1', !empty($where) ? replace($where, [
            '|nome|' => '`nomesede` LIKE '.prepare('%'.$search.'%').' OR `ragione_sociale`',
            '|table|' => '`an_sedi`',
        ]) : '', $query);
        $sedi = $database->fetchArray($query);
        $destinatari = $destinatari->concat($sedi);

        // Gestione referenti come destinatari
        $query = "SELECT CONCAT('referente_', `an_referenti`.`id`) AS id,
           CONCAT(`an_anagrafiche`.`ragione_sociale`, ' (', `an_referenti`.`nome`, ') [', `an_referenti`.`email`, ']') AS text,
           'Referenti' AS optgroup
        FROM 
            `an_referenti`
            INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `an_referenti`.`idanagrafica`
        WHERE 
            `an_anagrafiche`.`deleted_at` IS NULL AND `an_anagrafiche`.`enable_newsletter` = 1 AND 1=1
        ORDER BY 
            `optgroup` ASC, `ragione_sociale` ASC";

        $query = str_replace('1=1', !empty($where) ? replace($where, [
            '|nome|' => '`an_referenti`.`nome` LIKE '.prepare('%'.$search.'%').' OR ragione_sociale',
            '|table|' => '`an_anagrafiche`',
        ]) : '', $query);
        $referenti = $database->fetchArray($query);
        $destinatari = $destinatari->concat($referenti);

        $results = $destinatari->toArray();

        break;

    case 'liste_newsletter':
        $query = "SELECT `em_lists`.`id`, CONCAT(`em_lists_lang`.`name`, ' (', COUNT(*), ' `destinatari`)') AS descrizione FROM `em_lists` LEFT JOIN `em_lists_lang` ON (`em_lists_lang`.`id_record` = `em_lists`.`id` AND `em_lists_lang`.`id_lang` = ".prepare(setting('Lingua')).") INNER JOIN `em_list_receiver` ON `em_lists`.`id` = `em_list_receiver`.`id_list` WHERE 1=1 |where| ORDER BY `name` ASC";

        foreach ($elements as $element) {
            $filter[] = '`id`='.prepare($element);
        }

        if (empty($filter)) {
            $where[] = '`deleted_at` IS NULL';
        }

        if (!empty($search)) {
            $search_fields[] = '`name` LIKE '.prepare('%'.$search.'%');
        }

        // Aggiunta filtri di ricerca
        if (!empty($search_fields)) {
            $where[] = '('.implode(' OR ', $search_fields).')';
        }

        if (!empty($filter)) {
            $where[] = '('.implode(' OR ', $filter).')';
        }

        break;
}
