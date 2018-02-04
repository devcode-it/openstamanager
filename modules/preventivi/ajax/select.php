<?php

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    case 'preventivi':
        if (isset($superselect['idanagrafica'])) {
            $query = 'SELECT co_preventivi.id AS id, an_anagrafiche.idanagrafica, CONCAT(numero, " ", nome) AS descrizione, co_preventivi.idtipointervento, (SELECT descrizione descrizione FROM in_tipiintervento WHERE in_tipiintervento.idtipointervento = co_preventivi.idtipointervento) AS idtipointervento_descrizione FROM co_preventivi INNER JOIN an_anagrafiche ON co_preventivi.idanagrafica=an_anagrafiche.idanagrafica |where| ORDER BY id';

            foreach ($elements as $element) {
                $filter[] = 'id='.prepare($element);
            }

            $where[] = 'an_anagrafiche.idanagrafica='.prepare($superselect['idanagrafica']);
            $where[] = "idstato NOT IN (SELECT `id` FROM co_statipreventivi WHERE descrizione='Bozza' OR descrizione='Rifiutato' OR descrizione='Pagato')";

            if (!empty($search)) {
                $search_fields[] = 'nome LIKE '.prepare('%'.$search.'%');
            }

            $custom['idtipointervento'] = 'idtipointervento';
            $custom['idtipointervento_descrizione'] = 'idtipointervento_descrizione';
        }

        break;

    case 'preventivi_aperti':
        $query = 'SELECT co_preventivi.id AS id, CONCAT(numero, " ", nome, " (", ragione_sociale, ")") AS descrizione FROM co_preventivi INNER JOIN an_anagrafiche ON co_preventivi.idanagrafica=an_anagrafiche.idanagrafica |where| ORDER BY id';

        foreach ($elements as $element) {
            $filter[] = 'idpreventivo='.prepare($element);
        }
        $where[] = 'idstato IN (1)';
        if (!empty($search)) {
            $search_fields[] = 'nome LIKE '.prepare('%'.$search.'%');
        }

        break;
}
