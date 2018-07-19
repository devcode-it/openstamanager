<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'import':
        $first_row = !post('first_row');
        $selected = post('fields');

        // Pulizia dei campi inutilizzati
        foreach ($selected as $key => $value) {
            if (!is_numeric($value)) {
                unset($selected[$key]);
            }
        }

        $fields = Import::getFields($id_record);

        $csv = Import::getFile($id_record, $record['id'], [
            'headers' => $first_row,
        ]);

        // Interpretazione dei dati
        $data = [];
        foreach ($csv as $row) {
            $data_row = [];

            foreach ($row as $key => $value) {
                $field = $fields[$selected[$key]];

                if (isset($selected[$key])) {
                    $name = $field['field'];

                    $query = $field['query'];
                    if (!empty($query)) {
                        $query = str_replace('|value|', prepare($value), $query);

                        $value = $dbo->fetchArray($query)[0]['result'];
                    }

                    $data_row[$name] = $value;
                }
            }

            $data[] = $data_row;
        }

        $primary_key = post('primary_key');

        // Richiamo delle operazioni specifiche
        include $imports[$id_record]['import'];

        flash()->info(tr('Importazione completata. '.count($csv).' righe processate.'));

        break;
}
