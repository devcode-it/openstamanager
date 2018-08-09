<?php

switch ($resource) {
    case 'updates':
        $custom_where = !empty($updated) ? ' WHERE updated_at >= '.prepare($updated) : '';

        $excluded = explode(',', setting('Tabelle escluse per la sincronizzazione API automatica'));

        // Attenzione: query specifica per MySQL
        $datas = $dbo->fetchArray("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_SCHEMA=".prepare($db_name));
        if (!empty($datas)) {
            foreach ($datas as $data) {
                if (!in_array($data['TABLE_NAME'], $excluded)) {
                    $response[$data['TABLE_NAME']] = $dbo->fetchArray('SELECT * FROM '.$data['TABLE_NAME'].$custom_where);
                }
            }
        }
        break;

    // Attualmente vengono considerate solo le tabelle che eseguono l'eliminazione fisica della riga
    case 'deleted':
        $excluded = explode(',', setting('Tabelle escluse per la sincronizzazione API automatica'));

        // Attenzione: query specifica per MySQL
        $datas = $dbo->fetchArray("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_SCHEMA=".prepare($db_name));
        if (!empty($datas)) {
            foreach ($datas as $data) {
                $table_name = $data['TABLE_NAME'];

                // Ottiene il nome della colonna di tipo AUTO_INCREMENT della tabella
                $column = $dbo->fetchArray('SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '.prepare($table_name)." AND EXTRA LIKE '%AUTO_INCREMENT%' AND TABLE_SCHEMA = ".prepare($db_name))[0]['COLUMN_NAME'];

                if (!in_array($table_name, $excluded) && !empty($column)) {
                    // Ottiene il valore successivo della colonna di tipo AUTO_INCREMENT
                    $auto_inc = $dbo->fetchArray('SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = '.prepare($table_name).' AND TABLE_SCHEMA = '.prepare($db_name))[0]['AUTO_INCREMENT'];

                    // Ottiene i vuoti all'interno della sequenza AUTO_INCREMENT
                    $steps = $dbo->fetchArray('SELECT (t1.'.$column.' + 1) as start, (SELECT MIN(t3.'.$column.') - 1 FROM '.$table_name.' t3 WHERE t3.'.$column.' > t1.'.$column.') as end FROM '.$table_name.' t1 WHERE NOT EXISTS (SELECT t2.'.$column.' FROM '.$table_name.' t2 WHERE t2.'.$column.' = t1.'.$column.' + 1) ORDER BY start');

                    $total = [];
                    foreach ($steps as $step) {
                        if ($step['end'] == null) {
                            $step['end'] = $auto_inc - 1;
                        }

                        if ($step['end'] >= $step['start']) {
                            $total = array_merge($total, range($step['start'], $step['end']));
                        }
                    }

                    $response[$table_name] = $total;
                }
            }
        }

        break;
}

/*
return [
    'updates',
    'deleted',
];
*/
