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
