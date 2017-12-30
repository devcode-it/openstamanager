<?php

/*
* Inserimento valori di default
*/

// Permessi di default delle viste
$gruppi = $database->fetchArray('SELECT `id` FROM `zz_groups`');
$results = $database->fetchArray('SELECT `id` FROM `zz_views` WHERE `id` NOT IN (SELECT `id_vista` FROM `zz_group_view`)');

$array = [];
foreach ($results as $result) {
    foreach ($gruppi as $gruppo) {
        $array[] = [
            'id_gruppo' => $gruppo['id'],
            'id_vista' => $result['id'],
        ];
    }
}
if (!empty($array)) {
    $database->insert('zz_group_view', $array);
}

/*
* Rimozione file e cartelle deprecati
*/

// File e cartelle deprecate
$files = [
    'templates/fatture_accompagnatorie/fattura_body.html',
    'templates/fatture_accompagnatorie/fattura.html',
    'templates/fatture_accompagnatorie/pdfgen.fatture_accompagnatorie.php',
    'templates/fatture_accompagnatorie/logo_azienda.jpg',
    'templates/riepilogo_contratti/contratto_body.html',
    'templates/riepilogo_contratti/contratto.html',
    'templates/riepilogo_contratti/pdfgen.riepilogo_contratti.php',
    'templates/riepilogo_contratti/logo_azienda.jpg',
    'assets/dist/viewerjs',
    'assets/dist/js/adapters',
    'assets/dist/js/lang',
    'assets/dist/js/skins',
    'assets/dist/js/ckeditor.js',
    'assets/dist/js/styles.js',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath($docroot.'/'.$value);
}

delete($files);
