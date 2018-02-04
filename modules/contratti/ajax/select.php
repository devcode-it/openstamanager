<?php

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    case 'contratti':
        $query = 'SELECT co_contratti.id AS id, CONCAT(numero, " ", nome) AS descrizione FROM co_contratti INNER JOIN an_anagrafiche ON co_contratti.idanagrafica=an_anagrafiche.idanagrafica |where| ORDER BY id';

        foreach ($elements as $element) {
            $filter[] = 'id='.prepare($element);
        }

        $where[] = 'an_anagrafiche.idanagrafica='.prepare($superselect['idanagrafica']);
        $where[] = 'idstato IN (SELECT `id` FROM co_staticontratti WHERE pianificabile = 1)';

        if (!empty($search)) {
            $search_fields[] = 'nome LIKE '.prepare('%'.$search.'%');
        }

        break;
}
