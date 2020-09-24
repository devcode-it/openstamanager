<?php

/*
* Rimozione file e cartelle deprecati
*/

// File e cartelle deprecate
$files = [
    'ajax_autocomplete.php',
    'assets/dist/viewerjs',
    'assets/dist/js/adapters',
    'assets/dist/js/lang',
    'assets/dist/js/skins',
    'assets/dist/js/ckeditor.js',
    'assets/dist/js/styles.js',
    'lib/actions.php',
    'lib/htmlbuilder.php',
    'lib/modulebuilder.php',
    'lib/permissions_check.php',
    'lib/user_check.php',
    'src/Widgets.php',
    'templates/fatture_accompagnatorie/fattura_body.html',
    'templates/fatture_accompagnatorie/fattura.html',
    'templates/fatture_accompagnatorie/pdfgen.fatture_accompagnatorie.php',
    'templates/fatture_accompagnatorie/logo_azienda.jpg',
    'templates/riepilogo_contratti/contratto_body.html',
    'templates/riepilogo_contratti/contratto.html',
    'templates/riepilogo_contratti/pdfgen.riepilogo_contratti.php',
    'templates/riepilogo_contratti/logo_azienda.jpg',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(base_dir().'/'.$value);
}

delete($files);
