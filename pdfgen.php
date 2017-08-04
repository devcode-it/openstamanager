<?php

include_once __DIR__.'/core.php';

$orientation = 'P';
$body_table_params = "style='width:210mm;'";
$font_size = '10pt';

// Assegnazione di tutte le variabile GET
foreach ($get as $key => $value) {
    ${$key} = !empty(${$key}) ? ${$key} : $value;
}

// Mostro o nascondo i costi dell'intervento...
$visualizza_costi = get_var('Visualizza i costi sulle stampe degli interventi');

// Nuovo sistema di generazione stampe
if (file_exists($docroot.'/templates/'.$ptype.'/init.php')) {
    // Impostazione della stampa
    if (file_exists($docroot.'/templates/'.$ptype.'/custom/layout.html')) {
        $report = file_get_contents($docroot.'/templates/'.$ptype.'/custom/layout.html');
    } else {
        $report = file_get_contents($docroot.'/templates/'.$ptype.'/layout.html');
    }

    // Individuazione delle variabili fondamentali per la sostituzione dei contenuti
    if (file_exists($docroot.'/templates/'.$ptype.'/custom/init.php')) {
        include $docroot.'/templates/'.$ptype.'/custom/init.php';
    } else {
        include $docroot.'/templates/'.$ptype.'/init.php';
    }

    if (!empty($id_module)) {
        Permissions::addModule($id_module);
    }
    Permissions::check();

    // Operazioni di sostituzione
    include $docroot.'/templates/pdfgen_variables.php';

    // Azioni sui contenuti della stampa
    if (file_exists($docroot.'/templates/'.$ptype.'/custom/actions.php')) {
        include $docroot.'/templates/'.$ptype.'/custom/actions.php';
    } else {
        include $docroot.'/templates/'.$ptype.'/actions.php';
    }
} else {
    // Decido se usare la stampa personalizzata (se esiste) oppure quella standard
    if (file_exists($ptype.'/custom/pdfgen.'.$ptype.'.php')) {
        include $docroot.'/templates/'.$ptype.'/custom/pdfgen.'.$ptype.'.php';
    } else {
        include $docroot.'/templates/'.$ptype.'/pdfgen.'.$ptype.'.php';
    }
}

// Sostituzione di variabili generiche
$report = str_replace('$body$', $body, $report);
$report = str_replace('$footer$', $footer, $report);

$report = str_replace('$font_size$', $font_size, $report);
$report = str_replace('$body_table_params$', $body_table_params, $report);

$report = str_replace('$docroot$', $docroot, $report);
$report = str_replace('$rootdir$', $rootdir, $report);

// Individuazione dellla configurazione
$directory = dirname($filename);
if (!empty($filename) && ((is_dir($directory) && !is_writable($directory)) || (!is_dir($directory) && !mkdir($directory)))) {
    $error = str_replace('_DIRECTORY_', $directory, _('Non hai i permessi per creare directory e files in _DIRECTORY_'));

    $_SESSION['errors'][] = $error;

    echo '
        <p align="center">'.$error.'</p>';

    exit();
}

$mode = !empty($filename) ? 'F' : 'I';

$filename = !empty($filename) ? $filename : sanitizeFilename($report_name);
$title = basename($filename);

// HTML
$html = (get_var('Formato report') == 'html');

try {
    ob_end_clean();
    $html2pdf = new HTML2PDF($orientation, 'A4', 'it', true, 'UTF-8');

    $html2pdf->writeHTML($report, $html);
    $html2pdf->pdf->setTitle($title);

    $html2pdf->output($filename, $mode);
} catch (HTML2PDF_exception $e) {
    echo $e;
    exit();
}
