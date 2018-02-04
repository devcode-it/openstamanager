<?php

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    case 'tipiintervento':
        $query = 'SELECT idtipointervento AS id, descrizione FROM in_tipiintervento |where| ORDER BY idtipointervento';

        foreach ($elements as $element) {
            $filter[] = 'idtipointervento='.prepare($element);
        }
        if (!empty($search)) {
            $search_fields[] = 'descrizione LIKE '.prepare('%'.$search.'%');
        }

        break;
}
