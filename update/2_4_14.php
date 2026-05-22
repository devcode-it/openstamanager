<?php

// File e cartelle deprecate
$files = [
    'templates/fatturato/pdfgen.fatturato.php',
    'templates/fatturato/fatturato_body.html',
    'templates/fatturato/fatturato.html',
    'modules/interventi/widgets/interventi.pianificazionedashboard.interventi.php',
    'modules/contratti/widgets/contratti.pianificazionedashboard.php',
    'modules/contratti/widgets/contratti.pianificazionedashboard.interventi.php',
    'modules/contratti/widgets/contratti.ratecontrattuali.php',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(base_dir().'/'.$value);
}

delete($files);

// Calcolo nuovo campo "numero_revision" per ciascun preventivo
$preventivi = $dbo->fetchArray('SELECT * FROM co_preventivi ORDER BY id ASC');

foreach ($preventivi as $preventivo) {
    // Calcolo il numero preventivo in modo sequenziale in base alla creazione
    $revisioni = $dbo->fetchArray('SELECT id FROM co_preventivi WHERE master_revision = '.prepare($preventivo['id']).' AND id > '.prepare($preventivo['id']).' ORDER BY id ASC');

    $numero_revision = 1;

    foreach ($revisioni as $revisione) {
        $dbo->query('UPDATE co_preventivi SET numero_revision='.prepare($numero_revision++).' WHERE id='.$revisione['id']);
    }
}

// Rinomina foreign key dopo RENAME TABLE co_promemoria_righe -> co_righe_promemoria
$fk_rename = ['table' => 'co_righe_promemoria', 'old_fk' => 'co_promemoria_righe_ibfk_1', 'new_fk' => 'co_righe_promemoria_ibfk_1', 'column' => 'idarticolo', 'ref_table' => 'mg_articoli', 'ref_column' => 'id', 'on_delete' => 'SET NULL'];
$exists = $database->fetchOne('SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '.prepare($fk_rename['table']).' AND CONSTRAINT_NAME = '.prepare($fk_rename['old_fk']).' AND CONSTRAINT_TYPE = \'FOREIGN KEY\'');
if (!empty($exists)) {
    $database->query('ALTER TABLE `'.$fk_rename['table'].'` DROP FOREIGN KEY `'.$fk_rename['old_fk'].'`, ADD CONSTRAINT `'.$fk_rename['new_fk'].'` FOREIGN KEY (`'.$fk_rename['column'].'`) REFERENCES `'.$fk_rename['ref_table'].'`(`'.$fk_rename['ref_column'].'`) ON DELETE '.$fk_rename['on_delete']);
}
