<?php

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    case 'anagrafiche_utenti':
        $query = 'SELECT `an_anagrafiche`.`idanagrafica` AS id, `an_anagrafiche`.`ragione_sociale` AS descrizione, `an_tipianagrafiche`.`descrizione` AS optgroup FROM `an_tipianagrafiche` INNER JOIN `an_tipianagrafiche_anagrafiche` ON `an_tipianagrafiche`.`idtipoanagrafica`=`an_tipianagrafiche_anagrafiche`.`idtipoanagrafica` INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica`=`an_tipianagrafiche_anagrafiche`.`idanagrafica` |where| ORDER BY `optgroup` ASC';

        $where[] = 'an_anagrafiche.deleted_at IS NULL';

        foreach ($elements as $element) {
            $filter[] = 'an_anagrafiche.idanagrafica='.prepare($element);
        }

        if (!empty($search)) {
            $search_fields[] = 'an_anagrafiche.ragione_sociale LIKE '.prepare('%'.$search.'%');
        }

        if (!empty($search_fields)) {
            $where[] = '('.implode(' OR ', $search_fields).')';
        }

        if (!empty($filter)) {
            $where[] = '('.implode(' OR ', $filter).')';
        }

        $wh = '';
        if (count($where) != 0) {
            $wh = 'WHERE '.implode(' AND ', $where);
        }
        $query = str_replace('|where|', $wh, $query);

        $rs = $dbo->fetchArray($query);
        foreach ($rs as $r) {
            if ($prev != $r['optgroup']) {
                $results[] = ['text' => $r['optgroup'], 'children' => []];
                $prev = $r['optgroup'];
            }

            $results[count($results) - 1]['children'][] = [
                'id' => $r['id'],
                'text' => $r['descrizione'],
                'descrizione' => $r['descrizione'],
            ];
        }

        break;

    case 'utenti':
        $query = "SELECT zz_users.id AS id, if(`an_anagrafiche`.`idanagrafica` IS NOT NULL, CONCAT(`an_anagrafiche`.`ragione_sociale`, ' (', zz_users.username, ')'), zz_users.username) AS descrizione, `an_tipianagrafiche`.`descrizione` AS optgroup
            FROM zz_users 
                LEFT JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `zz_users`.`idanagrafica`
                INNER JOIN `an_tipianagrafiche_anagrafiche` ON `an_anagrafiche`.`idanagrafica`=`an_tipianagrafiche_anagrafiche`.`idanagrafica`
                INNER JOIN `an_tipianagrafiche` ON `an_tipianagrafiche`.`idtipoanagrafica`=`an_tipianagrafiche_anagrafiche`.`idtipoanagrafica`
            |where| ORDER BY `optgroup` ASC";

        $where[] = 'an_anagrafiche.deleted_at IS NULL';

        foreach ($elements as $element) {
            $filter[] = 'zz_users.id='.prepare($element);
        }

        if (!empty($search)) {
            $search_fields[] = 'an_anagrafiche.ragione_sociale LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'zz_users.username LIKE '.prepare('%'.$search.'%');
        }

        if (!empty($search_fields)) {
            $where[] = '('.implode(' OR ', $search_fields).')';
        }

        if (!empty($filter)) {
            $where[] = '('.implode(' OR ', $filter).')';
        }

        $wh = '';
        if (count($where) != 0) {
            $wh = 'WHERE '.implode(' AND ', $where);
        }
        $query = str_replace('|where|', $wh, $query);

        $rs = $dbo->fetchArray($query);
        foreach ($rs as $r) {
            if ($prev != $r['optgroup']) {
                $results[] = ['text' => $r['optgroup'], 'children' => []];
                $prev = $r['optgroup'];
            }

            $results[count($results) - 1]['children'][] = [
                'id' => $r['id'],
                'text' => $r['descrizione'],
                'descrizione' => $r['descrizione'],
            ];
        }

        break;
}
