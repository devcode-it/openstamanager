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
];

foreach ($files as $key => $value) {
    $files[$key] = realpath($docroot.'/'.$value);
}

delete($files);

// File .html dei moduli di default
// Per un problema sulla lunghezza massima del path su glob è necessario dividere le cartelle dei moduli di default da pulire
$dirs = [
    'aggiornamenti',
    'anagrafiche',
    'articoli',
    'automezzi',
    'backup',
    'beni',
    'categorie',
    'causali',
    'contratti',
    'dashboard',
    'ddt',
    'fatture',
    'gestione_componenti',
    'interventi',
    'iva',
    'listini',
    'misure',
    'my_impianti',
    'opzioni',
    'ordini',
    'pagamenti',
    'partitario',
    'porti',
    'preventivi',
    'primanota',
    'scadenzario',
    'stati_intervento',
    'tecnici_tariffe',
    'tipi_anagrafiche',
    'tipi_intervento',
    'utenti',
    'viste',
    'voci_servizio',
    'zone',
];

$pieces = array_chunk($dirs, 5);

foreach ($pieces as $piece) {
    $files = glob($docroot.'/modules/{'.implode(',', $piece).'}/*.html', GLOB_BRACE);
    delete($files);
}
