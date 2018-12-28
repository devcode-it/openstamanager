<?php

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    case 'iva':
        $query = 'SELECT id, IF(codice IS NULL, descrizione, CONCAT(codice, " - ", descrizione)) AS descrizione FROM co_iva |where| ORDER BY descrizione ASC';
		
        foreach ($elements as $element) {
            $filter[] = 'id='.prepare($element);
        }
        if (!empty($search)) {
            $search_fields[] = 'descrizione LIKE '.prepare('%'.$search.'%');
            $search_fields[] = 'codice LIKE '.prepare('%'.$search.'%');
        }

        if (empty($filter)) {
            $where[] = 'deleted_at IS NULL';
			
			//se sto valorizzando un documento con lo split payment impedisco la selezione delle aliquote iva con natura N6 (reverse charge)
			if (isset($superselect['split_payment']) and !empty($superselect['split_payment'])) {
				$where[] = '(codice_natura_fe IS NULL OR codice_natura_fe != "N6")';
			}
        }

        break;
}
