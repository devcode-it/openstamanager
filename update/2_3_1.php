<?php

/*
* Rimozione file e cartelle deprecati [da 2.3]
*/

// File e cartelle deprecate
$files = [
    'permissions.php',
    'settings.php',
    'addgroup.php',
    'adduser.php',
    'change_pwd.php',
    'README',
    'widgets',
    'share',
    'lib/jscripts',
    'lib/html2pdf',
    'lib/class.phpmailer.php',
    'lib/class.pop3.php',
    'lib/class.smtp.php',
    'lib/PHPMailerAutoload.php',
    'lib/dbo.class.php',
    'lib/html-helpers.class.php',
    'lib/photo.class.php',
    'lib/widgets.class.php',
    'modules/anagrafiche/plugins/sedi.php',
    'modules/anagrafiche/plugins/referenti.php',
    'modules/ddt/plugins/ddt.anagrafiche.php',
    'modules/my_impianti/plugins/my_impianti.anagrafiche.php',
    'templates/pdfgen.php',
    'templates/interventi/intervento_body.html',
    'templates/interventi/intervento.html',
    'templates/ddt/ddt_body.html',
    'templates/ddt/ddt.html',
    'templates/ordini/ordini_body.html',
    'templates/ordini/ordini.html',
    'templates/fatture/pdfgen.fatture.php',
    'templates/contratti/pdfgen.contratti.php',
    'templates/preventivi/pdfgen.preventivi.php',
    'templates/preventivi_cons/preventivo_body.html',
    'templates/preventivi_cons/preventivo.html',
    'templates/preventivi_cons/pdfgen.preventivi_cons.php',
    'templates/contratti_cons/contratto_body.html',
    'templates/contratti_cons/contratto.html',
    'templates/contratti_cons/pdfgen.contratti_cons.php',
    'update/install_2.0.sql',
    'update/update_2.1.sql',
    'update/update_2.1.php',
    'update/update_2.2.sql',
    'update/update_2.2.php',
    'update/update_checker.php',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(base_dir().'/'.$value);
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
    $files = glob(base_dir().'/modules/{'.implode(',', $piece).'}/*.html', GLOB_BRACE);
    delete($files);
}

/*
* Rimozione file e cartelle deprecati [2.3.1]
*/

// File e cartelle deprecate
$files = [
    'templates/interventi/pdfgen.interventi.php',
    'templates/ddt/pdfgen.ddt.php',
    'templates/ordini/pdfgen.ordini.php',
    'templates/fatture/fattura_body.html',
    'templates/fatture/fattura.html',
    'templates/contratti/contratto_body.html',
    'templates/contratti/contratto.html',
    'templates/preventivo/preventivo_body.html',
    'templates/preventivo/preventivo.html',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(base_dir().'/'.$value);
}

delete($files);
