<?php

include __DIR__.'/../config.inc.php';

// Ottiene tutte le foreign key con ON DELETE CASCADE
$foreign_keys_query = "
    SELECT
        kcu.TABLE_NAME,
        kcu.COLUMN_NAME,
        kcu.REFERENCED_TABLE_NAME,
        kcu.REFERENCED_COLUMN_NAME,
        kcu.CONSTRAINT_NAME
    FROM
        information_schema.KEY_COLUMN_USAGE kcu
    INNER JOIN
        information_schema.REFERENTIAL_CONSTRAINTS rc
        ON kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
        AND kcu.CONSTRAINT_SCHEMA = rc.CONSTRAINT_SCHEMA
    WHERE
        kcu.CONSTRAINT_SCHEMA = DATABASE()
        AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
        AND rc.DELETE_RULE = 'CASCADE'
    ORDER BY
        kcu.TABLE_NAME, kcu.COLUMN_NAME
";

$foreign_keys = $dbo->fetchArray($foreign_keys_query);

// Per ogni foreign key, esegue la pulizia dei record orfani
foreach ($foreign_keys as $fk) {
    $table_name = $fk['TABLE_NAME'];
    $column_name = $fk['COLUMN_NAME'];
    $referenced_table = $fk['REFERENCED_TABLE_NAME'];
    $referenced_column = $fk['REFERENCED_COLUMN_NAME'];

    // Query per eliminare i record orfani
    $delete_query = "
        DELETE FROM `{$table_name}`
        WHERE `{$column_name}` IS NOT NULL
        AND `{$column_name}` NOT IN (
            SELECT `{$referenced_column}`
            FROM `{$referenced_table}`
        )
    ";

    try {
        $dbo->query($delete_query);
    } catch (Exception $e) {
        // Continua con la prossima foreign key in caso di errore
        continue;
    }
}
