<?php

include_once __DIR__.'/../../../core.php';

switch ($resource) {
	
    case 'aspetto-beni':
        $query = 'SELECT id, descrizione FROM dt_aspettobeni |where| ORDER BY descrizione ASC';

        foreach ($elements as $element) {
            $filter[] = 'id='.prepare($element);
        }
        if (!empty($search)) {
            $search_fields[] = 'descrizione LIKE '.prepare('%'.$search.'%');
        }
		
        break;
}
