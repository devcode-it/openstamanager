<?php

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    case 'contratti':
        $query = 'SELECT co_contratti.id AS id, CONCAT("Contratto ", numero, " del ", DATE_FORMAT(data_bozza, "%d/%m/%Y"), " - ", co_contratti.nome, " [", (SELECT `descrizione` FROM `co_staticontratti` WHERE `co_staticontratti`.`id` = `idstato`) , "]") AS descrizione, (SELECT SUM(subtotale) FROM co_righe_contratti WHERE idcontratto=co_contratti.id) AS totale, (SELECT SUM(sconto) FROM co_righe_contratti WHERE idcontratto=co_contratti.id) AS sconto, (SELECT COUNT(id) FROM co_righe_contratti WHERE idcontratto=co_contratti.id) AS n_righe FROM co_contratti INNER JOIN an_anagrafiche ON co_contratti.idanagrafica=an_anagrafiche.idanagrafica |where| ORDER BY id';

        foreach ($elements as $element) {
            $filter[] = 'id='.prepare($element);
        }

        $where[] = 'an_anagrafiche.idanagrafica='.prepare($superselect['idanagrafica']);

        $stato = !empty($superselect['stato']) ? $superselect['stato'] : 'pianificabile';
        $where[] = 'idstato IN (SELECT `id` FROM co_staticontratti WHERE '.$stato.' = 1)';

        if (!empty($superselect['non_fatturato'])) {
            $where[] = 'id NOT IN (SELECT idcontratto FROM co_righe_documenti WHERE idcontratto IS NOT NULL)';
        }

        if (!empty($search)) {
            $search_fields[] = 'nome LIKE '.prepare('%'.$search.'%');
        }

        $custom['totale'] = 'totale';
        $custom['sconto'] = 'sconto';
        $custom['n_righe'] = 'n_righe';

        break;
}
