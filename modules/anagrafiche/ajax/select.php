<?php

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    case 'clienti':
            //$citta_cliente = ", IF(citta IS NULL OR citta = '', '', CONCAT(' (', citta, ')'))";

            $query = "SELECT an_anagrafiche.idanagrafica AS id, CONCAT(ragione_sociale $citta_cliente) AS descrizione, idtipointervento_default FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica |where| ORDER BY ragione_sociale";

            foreach ($elements as $element) {
                $filter[] = 'an_anagrafiche.idanagrafica='.prepare($element);
            }

            if (empty($filter)) {
                $where[] = "descrizione='Cliente'";
                $where[] = 'deleted=0';
            }

            if (!empty($search)) {
                $search_fields[] = 'ragione_sociale LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'citta LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'provincia LIKE '.prepare('%'.$search.'%');
            }

            $custom['idtipointervento'] = 'idtipointervento_default';

        break;

    case 'fornitori':
            $query = "SELECT an_anagrafiche.idanagrafica AS id, CONCAT(ragione_sociale, IF(citta IS NULL OR citta = '', '', CONCAT(' (', citta, ')'))) AS descrizione, idtipointervento_default FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica |where| ORDER BY ragione_sociale";

            foreach ($elements as $element) {
                $filter[] = 'an_anagrafiche.idanagrafica='.prepare($element);
            }

            if (empty($filter)) {
                $where[] = "descrizione='Fornitore'";
                $where[] = 'deleted=0';
            }

            if (!empty($search)) {
                $search_fields[] = 'ragione_sociale LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'citta LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'provincia LIKE '.prepare('%'.$search.'%');
            }

            $custom['idtipointervento'] = 'idtipointervento_default';

        break;

    case 'agenti':
            $query = "SELECT an_anagrafiche.idanagrafica AS id, CONCAT(ragione_sociale, IF(citta IS NULL OR citta = '', '', CONCAT(' (', citta, ')'))) AS descrizione, idtipointervento_default FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica |where| ORDER BY ragione_sociale";

            foreach ($elements as $element) {
                $filter[] = 'an_anagrafiche.idanagrafica='.prepare($element);
            }

            if (empty($filter)) {
                $where[] = "descrizione='Agente'";
                $where[] = 'deleted=0';
            }

            if (!empty($search)) {
                $search_fields[] = 'ragione_sociale LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'citta LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'provincia LIKE '.prepare('%'.$search.'%');
            }

            $results = AJAX::completeResults($query, $where, $filter, $search, $custom);

            // Evidenzia l'agente di default
            if ($superselect['idanagrafica']) {
                $rsa = $dbo->fetchArray('SELECT idagente FROM an_anagrafiche WHERE idanagrafica='.prepare($superselect['idanagrafica']));
                $idagente_default = $rsa[0]['idagente'];
            } else {
                $idagente_default = 0;
            }

            $ids = array_column($results, $id);
            $pos = array_search($idagente_default, $ids);
            if ($pos !== false) {
                $results[$pos]['_bgcolor_'] = '#ff0';
            }
        break;

    case 'tecnici':
            $query = "SELECT an_anagrafiche.idanagrafica AS id, CONCAT(ragione_sociale, IF(citta IS NULL OR citta = '', '', CONCAT(' (', citta, ')'))) AS descrizione, idtipointervento_default FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica |where| ORDER BY ragione_sociale";

            foreach ($elements as $element) {
                $filter[] = 'an_anagrafiche.idanagrafica='.prepare($element);
            }

            if (empty($filter)) {
                $where[] = "descrizione='Tecnico'";
                $where[] = 'deleted=0';
            }

            if (!empty($search)) {
                $search_fields[] = 'ragione_sociale LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'citta LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'provincia LIKE '.prepare('%'.$search.'%');
            }

            // $custom['idtipointervento'] = 'idtipointervento_default';
        break;

    // Nota Bene: nel campo id viene specificato idtipoanagrafica-idanagrafica -> modulo Utenti e permessi, creazione nuovo utente
    case 'anagrafiche':
            $query = "SELECT CONCAT(an_tipianagrafiche.idtipoanagrafica, '-', an_anagrafiche.idanagrafica) AS id, CONCAT_WS('', ragione_sociale, ' (', citta, ' ', provincia, ')') AS descrizione idtipointervento_default FROM an_anagrafiche INNER JOIN (an_tipianagrafiche_anagrafiche INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica) ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica |where| ORDER BY ragione_sociale";

            foreach ($elements as $element) {
                $filter[] = 'an_anagrafiche.idanagrafica='.prepare($element);
            }

            if (empty($filter)) {
                $where[] = 'deleted=0';
            }

            if (!empty($search)) {
                $search_fields[] = 'ragione_sociale LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'citta LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'provincia LIKE '.prepare('%'.$search.'%');
            }

            // $custom['idtipointervento'] = 'idtipointervento_default';
        break;

    case 'sedi':
        if (isset($superselect['idanagrafica'])) {
            $query = "SELECT * FROM (SELECT 0 AS id, 'Sede legale' AS descrizione UNION SELECT id, CONCAT_WS(' - ', nomesede, citta) FROM an_sedi |where|) AS tab |filter| ORDER BY id";

            foreach ($elements as $element) {
                $filter[] = 'id='.prepare($element);
            }

            $where[] = 'idanagrafica='.prepare($superselect['idanagrafica']);

            if (!empty($search)) {
                $search_fields[] = 'nomesede LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'citta LIKE '.prepare('%'.$search.'%');
            }
        }
        break;

    case 'referenti':
        if (isset($superselect['idanagrafica'])) {
            $query = 'SELECT id, nome AS descrizione FROM an_referenti |where| ORDER BY id';

            foreach ($elements as $element) {
                $filter[] = 'id='.prepare($element);
            }

            $where[] = 'idanagrafica='.prepare($superselect['idanagrafica']);

            if (!empty($search)) {
                $search_fields[] = 'nome LIKE '.prepare('%'.$search.'%');
            }
        }
        break;
}
