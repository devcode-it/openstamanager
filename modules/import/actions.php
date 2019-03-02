<?php

switch (post('op')) {
    case 'example':
        $content = include $imports[$id_record]['import'];

        echo ROOTDIR.'/'.Import::createExample($id_record, $content);
        exit();

        break;

    case 'import':
        $include_first_row = post('include_first_row');
        $selected_fields = post('fields');
        $page = post('page');

        $limit = 500;

        // Pulizia dei campi inutilizzati
        foreach ($selected as $key => $value) {
            if (!is_numeric($value)) {
                unset($selected[$key]);
            }
        }

        $fields = Import::getFields($id_record);

        $csv = Import::getCSV($id_record, $record['id']);

        $offset = isset($page) ? $page * $limit : 0;

        // Ignora la prima riga se composta da header
        if ($offset == 0 && empty($include_first_row)) {
            ++$offset;
        }

        $csv = $csv->setOffset($offset)
                    ->setLimit($limit);

        // Chiavi per la lettura CSV
        $keys = [];
        foreach ($selected_fields as $id => $field_id) {
            if (is_numeric($field_id)) {
                $value = $fields[$field_id]['field'];
            } else {
                $value = -($id + 1);
            }

            $keys[] = $value;
        }

        // Query dei campi selezionati
        $queries = [];
        foreach ($fields as $key => $field) {
            if (!empty($field['query'])) {
                $queries[$field['field']] = $field['query'];
            }
        }

        // Lettura dei record
        $rows = $csv->fetchAssoc($keys, function ($row) use ($queries, $dbo) {
            foreach ($row as $key => $value) {
                if (is_int($key)) {
                    unset($row[$key]);
                } elseif (isset($queries[$key])) {
                    $query = str_replace('|value|', prepare($value), $queries[$key]);

                    $value = $dbo->fetchOne($query)['result'];

                    $row[$key] = $value;
                }
            }

            return $row;
        });

        // Gestione automatica dei valori convertiti
        $rows = iterator_to_array($rows);
        $data = Filter::parse($rows);

        $primary_key = post('primary_key');

        // Richiamo delle operazioni specifiche
        include $imports[$id_record]['import'];

        $count = count($rows);
        $more = $count == $limit;

        echo json_encode([
            'more' => $more,
            'count' => $count,
        ]);

        break;
}
