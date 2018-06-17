<?php

/**
 * Classe per la gestione delle informazioni relative alle stampe installate.
 *
 * @since 2.3
 */
class Prints
{
    /** @var array Elenco delle stampe disponibili */
    protected static $prints = [];
    /** @var array Elenco delle stampe per modulo */
    protected static $modules = [];

    /**
     * Restituisce tutte le informazioni di tutti i moduli installati.
     *
     * @return array
     */
    public static function getPrints()
    {
        if (empty(self::$prints)) {
            $database = Database::getConnection();

            $results = $database->fetchArray('SELECT * FROM zz_prints WHERE enabled = 1 ORDER BY `order`');

            $prints = [];

            foreach ($results as $result) {
                $result['full_directory'] = DOCROOT.'/templates/'.$result['directory'];

                $prints[$result['id']] = $result;
                $prints[$result['name']] = $result['id'];

                if (!isset(self::$modules[$result['id_module']])) {
                    self::$modules[$result['id_module']] = [];
                }

                self::$modules[$result['id_module']][] = $result['id'];
            }

            self::$prints = $prints;
        }

        return self::$prints;
    }

    /**
     * Restituisce le informazioni relative a una singolo stampa specificata.
     *
     * @param string|int $print
     *
     * @return array
     */
    public static function get($print)
    {
        if (!is_numeric($print) && !empty(self::getPrints()[$print])) {
            $print = self::getPrints()[$print];
        }

        return self::getPrints()[$print];
    }

    /**
     * Restituisce le informazioni relative alle stampe di un singolo modulo specificato.
     *
     * @param string|int $module
     *
     * @return array
     */
    public static function getModulePrints($module)
    {
        $module_id = Modules::get($module)['id'];

        self::getPrints();

        $result = [];

        foreach ((array) self::$modules[$module_id] as $value) {
            $print = self::get($value);

            if (!empty($print['is_record'])) {
                $result[] = $print;
            }
        }

        return $result;
    }

    /**
     * Restituisce le informazioni relative alle stampe di un singolo modulo specificato.
     *
     * @param string|int $module
     *
     * @return array
     */
    public static function getModuleMainPrint($module)
    {
        $prints = self::getModulePrints($module);

        $main = array_search(1, array_column($prints, 'main'));

        if ($main !== false) {
            return $prints[$main];
        }

        return false;
    }

    public static function render($print, $id_record, $filename = null)
    {
        ob_end_clean();

        $infos = self::get($print);

        Permissions::addModule($infos['id_module']);

        if (empty($infos) || empty($infos['enabled']) || !Permissions::check([], false)) {
            return false;
        }

        // Impostazione automatica della precisione a 2 numeri decimali
        Translator::getFormatter()->setPrecision(2);

        // Individuazione della configurazione
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

        if (self::isOldStandard($print)) {
            self::oldLoader($infos['id'], $id_record, $filename);
        } else {
            self::loader($infos['id'], $id_record, $filename);
        }
    }

    protected static function readOptions($string)
    {
        // Fix per contenuti con newline integrate
        $string = str_replace(["\n", "\r"], ['\\n', '\\r'], $string);

        $result = (array) json_decode($string, true);

        return $result;
    }

    protected static function isOldStandard($print)
    {
        $infos = self::get($print);

        return file_exists($infos['full_directory'].'/pdfgen.'.$infos['directory'].'.php') || file_exists($infos['full_directory'].'/custom/pdfgen.'.$infos['directory'].'.php');
    }

    protected static function isNewStandard($print)
    {
        return !self::isOldStandard($print);
    }

    protected static function oldLoader($id_print, $id_record, $filename = null, $format = 'A4')
    {
        $infos = self::get($id_print);
        $options = self::readOptions($infos['options']);

        $database = Database::getConnection();
        $dbo = $database;

        $docroot = DOCROOT;

        $user = Auth::user();

        $_GET[$infos['previous']] = $id_record;
        ${$infos['previous']} = $id_record;
        $ptype = $infos['directory'];

        $orientation = 'P';
        $body_table_params = "style='width:210mm;'";
        $table = 'margin-left:1.7mm';
        $font_size = '10pt';

        // Decido se usare la stampa personalizzata (se esiste) oppure quella standard
        if (file_exists($infos['full_directory'].'/custom/pdfgen.'.$infos['directory'].'.php')) {
            include $infos['full_directory'].'/custom/pdfgen.'.$infos['directory'].'.php';
        } else {
            include $infos['full_directory'].'/pdfgen.'.$infos['directory'].'.php';
        }

        // Sostituzione di variabili generiche
        $report = str_replace('$body$', $body, $report);
        $report = str_replace('$footer$', $footer, $report);

        $report = str_replace('$font_size$', $font_size, $report);
        $report = str_replace('$body_table_params$', $body_table_params, $report);
        $report = str_replace('$table$', $table, $report);

        // Footer di default
        if (!str_contains($report, '<page_footer>')) {
            $report .= '<page_footer>$default_footer$</page_footer>';
        }

        // Operazioni di sostituzione
        include DOCROOT.'/templates/replace.php';

        $mode = !empty($filename) ? 'F' : 'I';

        $filename = !empty($filename) ? $filename : sanitizeFilename($report_name);
        $title = basename($filename);

        $html2pdf = new Spipu\Html2Pdf\Html2Pdf($orientation, $format, 'it', true, 'UTF-8');

        $html2pdf->writeHTML($report);
        $html2pdf->pdf->setTitle($title);

        $html2pdf->output($filename, $mode);
    }

    protected static function loader($id_print, $id_record, $filename = null)
    {
        $infos = self::get($id_print);
        $options = self::readOptions($infos['options']);

        $database = Database::getConnection();
        $dbo = $database;

        $docroot = DOCROOT;

        $user = Auth::user();

        // Impostazioni di default
        if (file_exists(DOCROOT.'/templates/base/custom/settings.php')) {
            $default = include DOCROOT.'/templates/base/custom/settings.php';
        } else {
            $default = include DOCROOT.'/templates/base/settings.php';
        }

        // Impostazioni personalizzate della stampa
        if (file_exists($infos['full_directory'].'/custom/settings.php')) {
            $custom = include $infos['full_directory'].'/custom/settings.php';
        } elseif (file_exists($infos['full_directory'].'/settings.php')) {
            $custom = include $infos['full_directory'].'/settings.php';
        }

        // Individuazione delle impostazioni finali
        $settings = array_merge($default, (array) $custom);

        // Individuazione delle variabili fondamentali per la sostituzione dei contenuti
        if (file_exists($infos['full_directory'].'/custom/init.php')) {
            include $infos['full_directory'].'/custom/init.php';
        } elseif (file_exists($infos['full_directory'].'/init.php')) {
            include $infos['full_directory'].'/init.php';
        }

        // Individuazione delle variabili per la sostituzione
        include DOCROOT.'/templates/info.php';

        // Generazione dei contenuti della stampa
        ob_start();
        if (file_exists($infos['full_directory'].'/custom/body.php')) {
            include $infos['full_directory'].'/custom/body.php';
        } else {
            include $infos['full_directory'].'/body.php';
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
        if (file_exists($infos['full_directory'].'/custom/header.php')) {
            include $infos['full_directory'].'/custom/header.php';
        } elseif (file_exists($infos['full_directory'].'/header.php')) {
            include $infos['full_directory'].'/header.php';
        }
        $head = ob_get_clean();

        // Generazione dei contenuti del footer
        ob_start();
        if (file_exists($infos['full_directory'].'/custom/footer.php')) {
            include $infos['full_directory'].'/custom/footer.php';
        } elseif (file_exists($infos['full_directory'].'/footer.php')) {
            include $infos['full_directory'].'/footer.php';
        }
        $foot = ob_get_clean();

        // Header di default
        $head = !empty($head) ? $head : '$default_header$';

        // Footer di default
        $foot = !empty($foot) ? $foot : '$default_footer$';

        // Operazioni di sostituzione
        include DOCROOT.'/templates/replace.php';

        $mode = !empty($filename) ? 'F' : 'I';

        $filename = !empty($filename) ? $filename : sanitizeFilename($report_name);
        $title = basename($filename);

        $styles = [
            'templates/base/bootstrap.css',
            'templates/base/style.css',
        ];

        $settings['orientation'] = strtoupper($settings['orientation']) == 'L' ? 'L' : 'P';
        $settings['format'] = is_string($settings['format']) ? $settings['format'].($settings['orientation'] == 'L' ? '-L' : '') : $settings['format'];

        // Instanziamento dell'oggetto mPDF
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => $settings['format'],
            'orientation' => $settings['orientation'],
            'font-size' => $settings['font-size'],
            'margin_left' => $settings['margins']['left'],
            'margin_right' => $settings['margins']['right'],
            'margin_top' => $settings['margins']['top'] + $settings['header-height'],
            'margin_bottom' => $settings['margins']['bottom'] + $settings['footer-height'],
            'margin_header' => $settings['margins']['top'],
            'margin_footer' => $settings['margins']['bottom'],

            // Abilitazione per lo standard PDF/A
            //'PDFA' => true,
            //'PDFAauto' => true,
        ]);

        // Impostazione di header e footer
        $mpdf->SetHTMLFooter($foot);
        $mpdf->SetHTMLHeader($head);

        // Impostazione del titolo del PDF
        $mpdf->SetTitle($title);

        // Inclusione dei fogli di stile CSS
        foreach ($styles as $value) {
            $mpdf->WriteHTML(file_get_contents(DOCROOT.'/'.$value), 1);
        }

        // Impostazione del font-size
        $mpdf->WriteHTML('body {font-size: '.$settings['font-size'].'pt;}', 1);

        // Aggiunta dei contenuti
        $mpdf->WriteHTML($report);

        // Creazione effettiva del PDF
        $mpdf->Output($filename, $mode);
    }

    public static function getHref($print, $id_record, $get = '')
    {
        $infos = self::get($print);

        if (empty($infos)) {
            return false;
        }

        $link = ROOTDIR.'/pdfgen.php?';

        if (self::isOldStandard($infos['id'])) {
            $link .= 'ptype='.$infos['directory'];

            $link .= !empty($infos['previous']) && !empty($id_record) ? '&'.$infos['previous'].'='.$id_record : '';
        } else {
            $link .= 'id_print='.$infos['id'];

            $link .= !empty($id_record) ? '&id_record='.$id_record : '';
        }

        $link .= !empty($get) ? '&'.$get : '';

        return $link;
    }

    public static function getLink($print, $id_record, $btn = null, $title = null, $icon = null, $get = '')
    {
        return '{( "name": "button", "type": "print", "id": "'.$print.'", "id_record": "'.$id_record.'", "label": "'.$title.'", "icon": "'.$icon.'", "parameters": "'.$get.'", "class": "'.$btn.'" )}';
    }

    public static function getPreviewLink($print, $id_record, $filename)
    {
        self::render($print, $id_record, $filename);

        return ROOTDIR.'/assets/dist/pdfjs/web/viewer.html?file=../../../../'.ltrim(str_replace(DOCROOT, '', $filename), '/');
    }
}
