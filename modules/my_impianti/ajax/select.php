<?php

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    case 'impianti':
            $query = 'SELECT id, CONCAT(matricola, " - ", nome) AS descrizione FROM my_impianti |where| ORDER BY id, idanagrafica';

            foreach ($elements as $element) {
                $filter[] = 'id='.prepare($element);
            }

            if (!empty($search)) {
                $search_fields[] = 'nome LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'matricola LIKE '.prepare('%'.$search.'%');
            }
        break;

    case 'impianti-cliente':
        if (isset($superselect['idanagrafica'])) {
            $query = 'SELECT id, CONCAT(matricola, " - ", nome) AS descrizione FROM my_impianti |where| ORDER BY idsede';

            foreach ($elements as $element) {
                $filter[] = 'id='.prepare($element);
            }

            $where[] = 'idanagrafica='.prepare($superselect['idanagrafica']);
            if (!empty($superselect['idsede_destinazione'])) {
                $where[] = 'idsede='.prepare($superselect['idsede_destinazione']);
            }

            if (!empty($search)) {
                $search_fields[] = 'nome LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'matricola LIKE '.prepare('%'.$search.'%');
            }
        }
        break;

    case 'impianti-intervento':
        if (isset($superselect['idintervento'])) {
            $query = 'SELECT id, CONCAT(matricola, " - ", nome) AS descrizione FROM my_impianti INNER JOIN my_impianti_interventi ON my_impianti.id=my_impianti_interventi.idimpianto |where| ORDER BY idsede';

            foreach ($elements as $element) {
                $filter[] = 'id='.prepare($element);
            }

            $where[] = 'my_impianti_interventi.idintervento='.prepare($superselect['idintervento']);

            if (!empty($search)) {
                $search_fields[] = 'nome LIKE '.prepare('%'.$search.'%');
                $search_fields[] = 'matricola LIKE '.prepare('%'.$search.'%');
            }
        }
        break;
}
