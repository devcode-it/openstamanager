<?php

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    case 'clienti':
        $query = "SELECT an_anagrafiche.idanagrafica AS id, CONCAT(ragione_sociale, IF(citta IS NULL OR citta = '', '', CONCAT(' (', citta, ')')), IF(deleted_at IS NULL, '', ' (".tr('eliminata').")')) AS descrizione, idtipointervento_default FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica |where| ORDER BY ragione_sociale";

        foreach ($elements as $element) {
            $filter[] = 'an_anagrafiche.idanagrafica='.prepare($element);
        }

        $where[] = "descrizione='Cliente'";
        if (empty($filter)) {
            $where[] = 'deleted_at IS NULL';
        }

        if (!empty($search)) {
            $search_fields[] = 'ragione_sociale LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'citta LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'provincia LIKE '.prepare('%'.$search.'%');
        }

        $custom['idtipointervento'] = 'idtipointervento_default';

        break;

    case 'fornitori':
        $query = "SELECT an_anagrafiche.idanagrafica AS id, CONCAT(ragione_sociale, IF(citta IS NULL OR citta = '', '', CONCAT(' (', citta, ')')), IF(deleted_at IS NULL, '', ' (".tr('eliminata').")')) AS descrizione, idtipointervento_default FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica |where| ORDER BY ragione_sociale";

        foreach ($elements as $element) {
            $filter[] = 'an_anagrafiche.idanagrafica='.prepare($element);
        }

        $where[] = "descrizione='Fornitore'";
        if (empty($filter)) {
            $where[] = 'deleted_at IS NULL';
        }

        if (!empty($search)) {
            $search_fields[] = 'ragione_sociale LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'citta LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'provincia LIKE '.prepare('%'.$search.'%');
        }

        $custom['idtipointervento'] = 'idtipointervento_default';

        break;

    case 'vettori':
        $query = "SELECT an_anagrafiche.idanagrafica AS id, CONCAT(ragione_sociale, IF(citta IS NULL OR citta = '', '', CONCAT(' (', citta, ')')), IF(deleted_at IS NULL, '', ' (".tr('eliminata').")')) AS descrizione, idtipointervento_default FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica |where| ORDER BY ragione_sociale";

        foreach ($elements as $element) {
            $filter[] = 'an_anagrafiche.idanagrafica='.prepare($element);
        }

        $where[] = "descrizione='Vettore'";
        if (empty($filter)) {
            $where[] = 'deleted_at IS NULL';
        }

        if (!empty($search)) {
            $search_fields[] = 'ragione_sociale LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'citta LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'provincia LIKE '.prepare('%'.$search.'%');
        }

        $custom['idtipointervento'] = 'idtipointervento_default';

        break;

    case 'agenti':
        $query = "SELECT an_anagrafiche.idanagrafica AS id, CONCAT(ragione_sociale, IF(citta IS NULL OR citta = '', '', CONCAT(' (', citta, ')')), IF(deleted_at IS NULL, '', ' (".tr('eliminata').")')) AS descrizione, idtipointervento_default FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica |where| ORDER BY ragione_sociale";

        foreach ($elements as $element) {
            $filter[] = 'an_anagrafiche.idanagrafica='.prepare($element);
        }

        $where[] = "descrizione='Agente'";
        if (empty($filter)) {
            $where[] = 'deleted_at IS NULL';
        }

        if (!empty($search)) {
            $search_fields[] = 'ragione_sociale LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'citta LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'provincia LIKE '.prepare('%'.$search.'%');
        }

        $results = AJAX::selectResults($query, $where, $filter, $search, $limit, $custom);

        // Evidenzia l'agente di default
        if ($superselect['idanagrafica']) {
            $rsa = $dbo->fetchArray('SELECT idagente FROM an_anagrafiche WHERE idanagrafica='.prepare($superselect['idanagrafica']));
            $idagente_default = $rsa[0]['idagente'];
        } else {
            $idagente_default = 0;
        }

        $ids = array_column($results, 'idanagrafica');
        $pos = array_search($idagente_default, $ids);
        if ($pos !== false) {
            $results[$pos]['_bgcolor_'] = '#ff0';
        }
        break;

    case 'tecnici':
        $query = "SELECT an_anagrafiche.idanagrafica AS id, CONCAT(ragione_sociale, IF(citta IS NULL OR citta = '', '', CONCAT(' (', citta, ')')), IF(deleted_at IS NULL, '', ' (".tr('eliminata').")')) AS descrizione, idtipointervento_default FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica |where| ORDER BY ragione_sociale";

        foreach ($elements as $element) {
            $filter[] = 'an_anagrafiche.idanagrafica='.prepare($element);
        }

        $where[] = "descrizione='Tecnico'";
        if (empty($filter)) {
            $where[] = 'deleted_at IS NULL';

            if (setting('Permetti inserimento sessioni degli altri tecnici')) {
            } else {
                //come tecnico posso aprire attività solo a mio nome
                $user = Auth::user();
                if ($user['gruppo'] == 'Tecnici' && !empty($user['idanagrafica'])) {
                    $where[] = 'an_anagrafiche.idanagrafica='.$user['idanagrafica'];
                }
            }
        }

        if (!empty($search)) {
            $search_fields[] = 'ragione_sociale LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'citta LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'provincia LIKE '.prepare('%'.$search.'%');
        }

        // $custom['idtipointervento'] = 'idtipointervento_default';
        break;

    case 'clienti_fornitori':
        $query = "SELECT `an_anagrafiche`.`idanagrafica` AS id, CONCAT_WS('', ragione_sociale, IF(citta !='' OR provincia != '', CONCAT(' (', citta, IF(provincia!='', CONCAT(' ', provincia), ''), ')'), ''), IF(deleted_at IS NULL, '', ' (".tr('eliminata').")')) AS descrizione, `an_tipianagrafiche`.`descrizione` AS optgroup, idtipointervento_default, an_tipianagrafiche.idtipoanagrafica FROM `an_tipianagrafiche` INNER JOIN `an_tipianagrafiche_anagrafiche` ON `an_tipianagrafiche`.`idtipoanagrafica`=`an_tipianagrafiche_anagrafiche`.`idtipoanagrafica` INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica`=`an_tipianagrafiche_anagrafiche`.`idanagrafica` |where| ORDER BY `optgroup` ASC, ragione_sociale ASC";

        foreach ($elements as $element) {
            $filter[] = 'an_anagrafiche.idanagrafica='.prepare($element);
        }

        $where = [];
        if (empty($filter)) {
            $where[] = 'deleted_at IS NULL';
            $where[] = "an_tipianagrafiche_anagrafiche.idtipoanagrafica IN (SELECT idtipoanagrafica FROM an_tipianagrafiche WHERE descrizione = 'Cliente' OR descrizione = 'Fornitore')";
        }

        if (!empty($search)) {
            $search_fields[] = 'ragione_sociale LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'citta LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'provincia LIKE '.prepare('%'.$search.'%');
        }

        // Aggiunta filtri di ricerca
        if (!empty($search_fields)) {
            $where[] = '('.implode(' OR ', $search_fields).')';
        }

        if (!empty($filter)) {
            $where[] = '('.implode(' OR ', $filter).')';
        }

        $query = str_replace('|where|', !empty($where) ? 'WHERE '.implode(' AND ', $where) : '', $query);

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

    // Nota Bene: nel campo id viene specificato idtipoanagrafica-idanagrafica -> modulo Utenti e permessi, creazione nuovo utente
    case 'anagrafiche':
        $query = "SELECT an_anagrafiche.idanagrafica AS id, CONCAT_WS('', ragione_sociale, IF(citta !='' OR provincia != '', CONCAT(' (', citta, IF(provincia!='', CONCAT(' ', provincia), ''), ')'), ''), IF(deleted_at IS NULL, '', ' (".tr('eliminata').")')) AS descrizione, `an_tipianagrafiche`.`descrizione` AS optgroup FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica |where| ORDER BY `optgroup` ASC, ragione_sociale ASC";

        foreach ($elements as $element) {
            $filter[] = 'an_anagrafiche.idanagrafica='.prepare($element);
        }

        if (empty($filter)) {
            $where[] = 'deleted_at IS NULL';
        }

        if (!empty($search)) {
            $search_fields[] = 'ragione_sociale LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'citta LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'provincia LIKE '.prepare('%'.$search.'%');
        }

        // Aggiunta filtri di ricerca
        if (!empty($search_fields)) {
            $where[] = '('.implode(' OR ', $search_fields).')';
        }

        if (!empty($filter)) {
            $where[] = '('.implode(' OR ', $filter).')';
        }

        $query = str_replace('|where|', !empty($where) ? 'WHERE '.implode(' AND ', $where) : '', $query);

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

    case 'sedi':
        if (isset($superselect['idanagrafica'])) {
            $query = "SELECT * FROM (SELECT '0' AS id, CONCAT_WS(' - ', 'Sede legale' , (SELECT CONCAT (citta, ' (', ragione_sociale,')') FROM an_anagrafiche |where|)) AS descrizione UNION SELECT id, CONCAT_WS(' - ', nomesede, citta) FROM an_sedi |where|) AS tab HAVING descrizione LIKE ".prepare('%'.$search.'%').' ORDER BY descrizione';

            foreach ($elements as $element) {
                $filter[] = 'id='.prepare($element);
            }

            $where[] = 'idanagrafica='.prepare($superselect['idanagrafica']);

            if (!empty($search)) {
                $search_fields[] = 'citta LIKE '.prepare('%'.$search.'%');
            }
        }
        break;

    case 'sedi_azienda':
        if (isset($superselect['idanagrafica'])) {
            $user = Auth::user();
            $id_azienda = setting('Azienda predefinita');

            $query = "SELECT * FROM (SELECT '0' AS id, CONCAT_WS(' - ', 'Sede legale' , (SELECT CONCAT (citta, ' (', ragione_sociale,')') FROM an_anagrafiche |where|)) AS descrizione UNION SELECT id, CONCAT_WS(' - ', nomesede, citta) FROM an_sedi |where|) AS tab |filter| ORDER BY descrizione";

            foreach ($elements as $element) {
                $filter[] = 'id='.prepare($element);
            }

            $where[] = 'idanagrafica='.prepare($id_azienda);
            //admin o utente senza una sede prefissata, avrà accesso a tutte le sedi
            if (!empty($user->sedi) and !$user->is_admin) {
                $where[] = 'id IN('.implode(',', $user->sedi).')';
            }

            if (!empty($search)) {
                $search_fields[] = 'nomesede LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'citta LIKE '.prepare('%'.$search.'%');
            }
        }

        break;

    case 'referenti':
        if (isset($superselect['idanagrafica'])) {
            $query = 'SELECT id, nome AS descrizione FROM an_referenti |where| ORDER BY nome';

            foreach ($elements as $element) {
                $filter[] = 'id='.prepare($element);
            }

            $where[] = 'idanagrafica='.prepare($superselect['idanagrafica']);

            if (!empty($search)) {
                $search_fields[] = 'nome LIKE '.prepare('%'.$search.'%');
            }
        }
        break;

    case 'nazioni':
        $query = 'SELECT id AS id, iso2, CONCAT_WS(\' - \', iso2, nome) AS descrizione FROM an_nazioni |where| ORDER BY CASE WHEN iso2=\'IT\' THEN -1 ELSE iso2 END';

        foreach ($elements as $element) {
            $filter[] = 'id='.prepare($element);
        }

        if (!empty($search)) {
            $search_fields[] = 'nome LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'iso2 LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'CONCAT_WS(\' - \', iso2, nome) LIKE '.prepare('%'.$search.'%');
        }

        break;

    case 'relazioni':
        $query = 'SELECT id, descrizione, colore AS bgcolor FROM an_relazioni |where| ORDER BY descrizione';

        foreach ($elements as $element) {
            $filter[] = 'id='.prepare($element);
        }

        if (!empty($search)) {
            $search_fields[] = 'descrizione LIKE '.prepare('%'.$search.'%');
        }

        break;

    case 'dichiarazioni_intento':
        $query = "SELECT id, CONCAT_WS(' - ', numero_protocollo, numero_progressivo) as descrizione FROM co_dichiarazioni_intento |where| ORDER BY data";

        foreach ($elements as $element) {
            $filter[] = 'id='.prepare($element);
        }

        $where[] = 'data_inizio < NOW()';
        $where[] = 'data_fine > NOW()';
        if (empty($filter)) {
            $where[] = 'deleted_at IS NULL';
        }

        $where[] = 'id_anagrafica='.prepare($superselect['idanagrafica']);

        if (!empty($search)) {
            $search_fields[] = 'numero_protocollo LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'numero_progressivo LIKE '.prepare('%'.$search.'%');
        }

        break;
}
