<?php

include_once __DIR__.'/core.php';

ob_end_clean();

// Assegnazione di tutte le variabile GET
foreach ($get as $key => $value) {
    ${$key} = !empty(${$key}) ? ${$key} : $value;
}

// Mostro o nascondo i costi dell'intervento...
$visualizza_costi = get_var('Visualizza i costi sulle stampe degli interventi');

// Nuovo sistema di generazione stampe
if (file_exists($docroot.'/templates/'.$ptype.'/init.php')) {
    // Impostazioni di default
    if (file_exists($docroot.'/templates/base/custom/settings.php')) {
        $default = include $docroot.'/templates/base/custom/settings.php';
    } else {
        $default = include $docroot.'/templates/base/settings.php';
    }

    // Impostazioni personalizzate della stampa
    if (file_exists($docroot.'/templates/'.$ptype.'/custom/settings.php')) {
        $custom = include $docroot.'/templates/'.$ptype.'/custom/settings.php';
    } elseif (file_exists($docroot.'/templates/'.$ptype.'/settings.php')) {
        $custom = include $docroot.'/templates/'.$ptype.'/settings.php';
    }

    // Individuazione delle impostazioni finali
    $settings = array_merge($default, (array) $custom);

    // Fix per l'altezza minima del margine in alto
    $settings['header-height'] = ($settings['header-height'] < $settings['margins']['top']) ? $settings['margins']['top'] : $settings['header-height'];

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

    // Generazione dei contenuti della stampa
    ob_start();
    if (file_exists($docroot.'/templates/'.$ptype.'/custom/body.php')) {
        include $docroot.'/templates/'.$ptype.'/custom/body.php';
    } else {
        include $docroot.'/templates/'.$ptype.'/body.php';
    }
    $report = ob_get_clean();

    // Generazione dei contenuti dell'header
    ob_start();
    if (file_exists($docroot.'/templates/'.$ptype.'/custom/header.php')) {
        include $docroot.'/templates/'.$ptype.'/custom/header.php';
    } elseif (file_exists($docroot.'/templates/'.$ptype.'/header.php')) {
        include $docroot.'/templates/'.$ptype.'/header.php';
    }
    $head = ob_get_clean();

    // Generazione dei contenuti del footer
    ob_start();
    if (file_exists($docroot.'/templates/'.$ptype.'/custom/footer.php')) {
        include $docroot.'/templates/'.$ptype.'/custom/footer.php';
    } elseif (file_exists($docroot.'/templates/'.$ptype.'/footer.php')) {
        include $docroot.'/templates/'.$ptype.'/footer.php';
    }
    $foot = ob_get_clean();

    // Footer di default
    $foot = !empty($foot) ? $foot : '$pagination$';
} else {
    $orientation = 'P';
    $body_table_params = "style='width:210mm;'";
    $table = 'margin-left:1.7mm';
    $font_size = '10pt';

    // Decido se usare la stampa personalizzata (se esiste) oppure quella standard
    if (file_exists($ptype.'/custom/pdfgen.'.$ptype.'.php')) {
        include $docroot.'/templates/'.$ptype.'/custom/pdfgen.'.$ptype.'.php';
    } else {
        include $docroot.'/templates/'.$ptype.'/pdfgen.'.$ptype.'.php';
    }

    // Sostituzione di variabili generiche
    $report = str_replace('$body$', $body, $report);
    $report = str_replace('$footer$', $footer, $report);

    $report = str_replace('$font_size$', $font_size, $report);
    $report = str_replace('$body_table_params$', $body_table_params, $report);
    $report = str_replace('$table$', $table, $report);
}

// Operazioni di sostituzione
include $docroot.'/templates/pdfgen_variables.php';

// Individuazione dellla configurazione
$directory = dirname($filename);
if (!empty($filename) && ((is_dir($directory) && !is_writable($directory)) || (!is_dir($directory) && !create_dir($directory)))) {
    $error = str_replace('_DIRECTORY_', $directory, tr('Non hai i permessi per creare directory e files in _DIRECTORY_'));

    $_SESSION['errors'][] = $error;

    echo '
        <p align="center">'.$error.'</p>';

    exit();
}

$mode = !empty($filename) ? 'F' : 'I';

$filename = !empty($filename) ? $filename : sanitizeFilename($report_name);
$title = basename($filename);

if (file_exists($docroot.'/templates/'.$ptype.'/init.php')) {
    $styles = [
        'templates/base/bootstrap.css',
        'templates/base/style.css',
    ];

    $mpdf = new mPDF(
        'c',
        $settings['dimension'],
        $settings['font-size'],
        'helvetica',
        $settings['margins']['left'],
        $settings['margins']['right'],
        $settings['header-height'],
        $settings['footer-height'],
        $settings['margins']['top'],
        $settings['margins']['bottom'],
        strtolower($settings['orientation']) == 'l' ? 'l' : 'p'
    );

    $mpdf->SetHTMLFooter($foot);
    $mpdf->SetHTMLHeader($head);

    $mpdf->SetTitle($title);

    foreach ($styles as $value) {
        $mpdf->WriteHTML(file_get_contents(__DIR__.'/'.$value), 1);
    }
    $mpdf->WriteHTML($report);

    $mpdf->Output($filename, $mode);
} else {
    $html2pdf = new Spipu\Html2Pdf\Html2Pdf($orientation, 'A4', 'it', true, 'UTF-8');

    $html2pdf->writeHTML($report);
    $html2pdf->pdf->setTitle($title);

    $html2pdf->output($filename, $mode);
}
