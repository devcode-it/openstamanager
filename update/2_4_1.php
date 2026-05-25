<?php

// Rinomina FK per tabella rinominata co_righe_contratti -> co_contratti_promemoria
$fk_renames = [
    [
        'table' => 'co_contratti_promemoria',
        'old_fk' => 'co_righe_contratti_ibfk_1',
        'new_fk' => 'co_contratti_promemoria_ibfk_1',
        'column' => 'idintervento',
        'ref_table' => 'in_interventi',
        'ref_column' => 'id',
        'on_delete' => 'CASCADE',
        'on_update' => 'RESTRICT',
    ],
];

foreach ($fk_renames as $fk) {
    $exists = $dbo->fetchOne('SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '.prepare($fk['table']).' AND CONSTRAINT_NAME = '.prepare($fk['old_fk']).' AND CONSTRAINT_TYPE = \'FOREIGN KEY\'');
    if (!empty($exists)) {
        $on_update = !empty($fk['on_update']) ? ' ON UPDATE '.$fk['on_update'] : '';
        $dbo->query('ALTER TABLE `'.$fk['table'].'` DROP FOREIGN KEY `'.$fk['old_fk'].'`, ADD CONSTRAINT `'.$fk['new_fk'].'` FOREIGN KEY (`'.$fk['column'].'`) REFERENCES `'.$fk['ref_table'].'`(`'.$fk['ref_column'].'`) ON DELETE '.$fk['on_delete'].$on_update);
    }
}