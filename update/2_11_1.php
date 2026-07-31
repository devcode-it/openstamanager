<?php

$fk_renames = [
    ['table' => 'my_impianti', 'old_fk' => 'fk_my_impianti_stato', 'new_fk' => 'my_impianti_ibfk_3', 'column' => 'id_stato', 'ref_table' => 'my_stati_impianti', 'ref_column' => 'id', 'on_delete' => 'SET NULL', 'on_update' => 'CASCADE'],
];

foreach ($fk_renames as $fk) {
    $exists = $database->fetchOne('SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '.prepare($fk['table']).' AND CONSTRAINT_NAME = '.prepare($fk['old_fk']).' AND CONSTRAINT_TYPE = \'FOREIGN KEY\'');
    if (!empty($exists)) {
        $on_update = !empty($fk['on_update']) ? ' ON UPDATE '.$fk['on_update'] : '';
        $database->query('ALTER TABLE `'.$fk['table'].'` DROP FOREIGN KEY `'.$fk['old_fk'].'`, ADD CONSTRAINT `'.$fk['new_fk'].'` FOREIGN KEY (`'.$fk['column'].'`) REFERENCES `'.$fk['ref_table'].'`(`'.$fk['ref_column'].'`) ON DELETE '.$fk['on_delete'].$on_update);
    }
}
