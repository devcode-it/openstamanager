<?php

include_once __DIR__.'/core.php';

ob_end_clean();

// Assegnazione di tutte le variabile GET
foreach ($get as $key => $value) {
    ${$key} = !empty(${$key}) ? ${$key} : $value;
}

// Impostazione automatica della precisione a 2 numeri decimali
Translator::getFormatter()->setPrecision(2);

// Individuazione del formato della stampa
$old_format = file_exists($docroot.'/templates/'.$ptype.'/pdfgen.'.$ptype.'.php') || file_exists($docroot.'/templates/'.$ptype.'/custom/pdfgen.'.$ptype.'.php');

// Nuovo sistema di generazione stampe
if (!$old_format) {
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

    // Individuazione delle variabili fondamentali per la sostituzione dei contenuti
    if (file_exists($docroot.'/templates/'.$ptype.'/custom/init.php')) {
        include $docroot.'/templates/'.$ptype.'/custom/init.php';
    } elseif (file_exists($docroot.'/templates/'.$ptype.'/init.php')) {
        include $docroot.'/templates/'.$ptype.'/init.php';
    }

    // Individuazione delle variabili per la sostituzione
    include_once __DIR__.'/templates/info.php';

    if (!empty($id_module)) {
        Permissions::addModule($id_module);
    }
    Permissions::check();

    // Operazioni di sostituzione
    include $docroot.'/templates/info.php';

    // Generazione dei contenuti della stampa
    ob_start();
    if (file_exists($docroot.'/templates/'.$ptype.'/custom/body.php')) {
        include $docroot.'/templates/'.$ptype.'/custom/body.php';
    } else {
        include $docroot.'/templates/'.$ptype.'/body.php';
    }
    $report = ob_get_clean();

    if (!empty($autofill)) {
        $result = '';

        // max($autofill['additional']) = $autofill['rows'] - 1
        for ($i = (floor($autofill['count']) % $autofill['rows']); $i < $autofill['additional']; ++$i) {
            $result .= '
            <tr>';
            for ($c = 0; $c < $autofill['columns']; ++$c) {
                $result .= '
                <td>&nbsp;</td>';
            }
            $result .= '
            </tr>';
        }

        $report = str_replace('|autofill|', $result, $report);
    }

    // Generazione dei contenuti dell'header
    ob_start();
    if (file_exists($docroot.'/templates/'.$ptype.'/custom/header.php')) {
        include $docroot.'/templates/'.$ptype.'/custom/header.php';
    } elseif (file_exists($docroot.'/templates/'.$ptype.'/header.php')) {
        include $docroot.'/templates/'.$ptype.'/header.php';
    }
    $head = ob_get_clean();

    // Footer di default
    $head = !empty($head) ? $head : '$default_header$';

    // Generazione dei contenuti del footer
    ob_start();
    if (file_exists($docroot.'/templates/'.$ptype.'/custom/footer.php')) {
        include $docroot.'/templates/'.$ptype.'/custom/footer.php';
    } elseif (file_exists($docroot.'/templates/'.$ptype.'/footer.php')) {
        include $docroot.'/templates/'.$ptype.'/footer.php';
    }
    $foot = ob_get_clean();
} else {
    $orientation = 'P';
    $body_table_params = "style='width:210mm;'";
    $table = 'margin-left:1.7mm';
    $font_size = '10pt';

    // Decido se usare la stampa personalizzata (se esiste) oppure quella standard
    if (file_exists($docroot.'/templates/'.$ptype.'/custom/pdfgen.'.$ptype.'.php')) {
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

// Footer di default
$foot = !empty($foot) ? $foot : '$default_footer$';

// Operazioni di sostituzione
include $docroot.'/templates/replace.php';

// Individuazione dellla configurazione
$directory = dirname($filename);
if (!empty($filename) && !directory($directory)) {
    $error = tr('Non hai i permessi per creare directory e files in _DIRECTORY_', [
        '_DIRECTORY_' => $directory,
    ]);

    $_SESSION['errors'][] = $error;

    echo '
        <p align="center">'.$error.'</p>';

    exit();
}

$mode = !empty($filename) ? 'F' : 'I';

$filename = !empty($filename) ? $filename : sanitizeFilename($report_name);
$title = basename($filename);

if (!$old_format) {
    $styles = [
        'templates/base/bootstrap.css',
        'templates/base/style.css',
    ];

    $settings['orientation'] = strtoupper($settings['orientation']) == 'L' ? 'L' : 'P';
    $settings['format'] = is_string($settings['format']) ? $settings['format'].($settings['orientation'] == 'L' ? '-L' : '') : $settings['format'];

    // Instanziamento dell'oggetto mPDF
    $mpdf = new mPDF(
        'c',
        $settings['format'],
        $settings['font-size'],
        'helvetica',
        $settings['margins']['left'],
        $settings['margins']['right'],
        $settings['margins']['top'] + $settings['header-height'],
        $settings['margins']['bottom'] + $settings['footer-height'],
        $settings['margins']['top'],
        $settings['margins']['bottom'],
        $settings['orientation']
    );

    // Impostazione di header e footer
    $mpdf->SetHTMLFooter($foot);
    $mpdf->SetHTMLHeader($head);

    // Impostazione del titolo del PDF
    $mpdf->SetTitle($title);

    // Inclusione dei fogli di stile CSS
    foreach ($styles as $value) {
        $mpdf->WriteHTML(file_get_contents(__DIR__.'/'.$value), 1);
    }

    // Impostazione del font-size
    $mpdf->WriteHTML('body {font-size: '.$settings['font-size'].'pt;}', 1);

    $mpdf->shrink_tables_to_fit = 1;
    // Aggiunta dei contenuti
    $mpdf->WriteHTML($report);

    // Creazione effettiva del PDF
    $mpdf->Output($filename, $mode);
} else {
    if (!str_contains($report, '<page_footer>')) {
        $report .= '<page_footer>'.$foot.'</page_footer>';
    }

    $html2pdf = new Spipu\Html2Pdf\Html2Pdf($orientation, 'A4', 'it', true, 'UTF-8');

    $html2pdf->writeHTML($report);
    $html2pdf->pdf->setTitle($title);

    $html2pdf->output($filename, $mode);
}
