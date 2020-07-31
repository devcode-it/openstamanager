<?php

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    case 'preventivi':
        if (isset($superselect['idanagrafica'])) {
            $query = 'SELECT co_preventivi.id AS id, an_anagrafiche.idanagrafica, CONCAT("Preventivo ", numero, " del ", DATE_FORMAT(data_bozza, "%d/%m/%Y"), " - ", co_preventivi.nome, " [", (SELECT `descrizione` FROM `co_statipreventivi` WHERE `co_statipreventivi`.`id` = `idstato`) , "]") AS descrizione, co_preventivi.idtipointervento, (SELECT descrizione FROM in_tipiintervento WHERE in_tipiintervento.idtipointervento = co_preventivi.idtipointervento) AS idtipointervento_descrizione, (SELECT tempo_standard FROM in_tipiintervento WHERE in_tipiintervento.idtipointervento = co_preventivi.idtipointervento) AS tempo_standard, (SELECT SUM(subtotale) FROM co_righe_preventivi WHERE idpreventivo=co_preventivi.id GROUP BY idpreventivo) AS totale, (SELECT SUM(sconto) FROM co_righe_preventivi WHERE idpreventivo=co_preventivi.id GROUP BY idpreventivo) AS sconto FROM co_preventivi INNER JOIN an_anagrafiche ON co_preventivi.idanagrafica=an_anagrafiche.idanagrafica |where| ORDER BY id';

            foreach ($elements as $element) {
                $filter[] = 'id='.prepare($element);
            }

            if (empty($elements)) {
                $where[] = 'an_anagrafiche.idanagrafica='.prepare($superselect['idanagrafica']);
                $where[] = 'co_preventivi.default_revision=1';

                $stato = !empty($superselect['stato']) ? $superselect['stato'] : 'is_pianificabile';
                $where[] = 'idstato IN (SELECT `id` FROM `co_statipreventivi` WHERE '.$stato.' = 1)';
            }

            if (!empty($search)) {
                $search_fields[] = 'nome LIKE '.prepare('%'.$search.'%');
            }

            $custom['idtipointervento'] = 'idtipointervento';
            $custom['tempo_standard'] = 'tempo_standard';
            $custom['idtipointervento_descrizione'] = 'idtipointervento_descrizione';
            $custom['totale'] = 'totale';
            $custom['sconto'] = 'sconto';
        }

        break;
}
