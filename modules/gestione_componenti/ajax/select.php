<?php

use Util\Ini;

include_once __DIR__.'/../../../core.php';

switch ($resource) {
    /*
     * Opzioni utilizzate:
     * - matricola
     */
    case 'componenti':
        if (isset($superselect['matricola'])) {
            $query = 'SELECT id, nome AS descrizione, contenuto FROM my_impianto_componenti |where| ORDER BY id';

            foreach ($elements as $element) {
                $filter[] = 'id='.prepare($element);
            }

            $temp = [];
            $impianti = explode(',', $superselect['matricola']);
            foreach ($impianti as $key => $idimpianto) {
                $temp[] = 'idimpianto='.prepare($idimpianto);
            }
            $where[] = '('.implode(' OR ', $temp).')';

            if (!empty($search)) {
                $search_fields[] = 'nome LIKE '.prepare('%'.$search.'%');
            }

            $results = AJAX::selectResults($query, $where, $filter, $search, $limit, $custom);
            $data = $results['results'];
            foreach ($data as $key => $value) {
                $matricola = Ini::getValue($value['contenuto'], 'Matricola');

                $data[$key]['text'] = (empty($matricola) ? '' : $matricola.' - ').$data[$key]['text'];

                unset($data[$key]['contenuto']);
            }

            $results['results'] = $data;
        }

        break;
}
