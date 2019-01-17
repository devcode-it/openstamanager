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
            $database = database();

            $results = $database->fetchArray('SELECT * FROM zz_prints WHERE enabled = 1 ORDER BY `order`');

            $prints = [];

            // Inizializzazione dei riferimenti
            $modules = Modules::getModules();
            foreach ($modules as $module) {
                self::$modules[$module['id']] = [];
            }

            foreach ($results as $result) {
                $result['full_directory'] = DOCROOT.'/templates/'.$result['directory'];

                $prints[$result['id']] = $result;
                $prints[$result['name']] = $result['id'];

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
    public static function getModulePredefinedPrint($module)
    {
        $prints = self::getModulePrints($module);

        $predefined = array_search(1, array_column($prints, 'predefined'));

        if ($predefined !== false) {
            return $prints[$predefined];
        } elseif (!empty($prints)) {
            return $prints[0];
        }

        return false;
    }

    /**
     * Genera la stampa in PDF richiesta.
     *
     * @param string|int $print
     * @param int        $id_record
     * @param string     $filename
     */
    public static function render($print, $id_record, $filename = null)
    {
        //ob_end_clean(); // Compatibilità con versioni vecchie delle stampe

        $infos = self::get($print);

        Permissions::addModule($infos['id_module']);

        if (empty($infos) || empty($infos['enabled']) || !Permissions::check([], false)) {
            return false;
        }

        // Impostazione automatica della precisione a 2 numeri decimali
        formatter()->setPrecision(2);

        // Individuazione della configurazione
        $directory = dirname($filename);
        if (!empty($filename) && !directory($directory)) {
            $error = tr('Non hai i permessi per creare directory e files in _DIRECTORY_', [
                '_DIRECTORY_' => $directory,
            ]);

            flash()->error($error);

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

    /**
     * Individua il link per la stampa.
     *
     * @param string|int $print
     * @param int        $id_record
     * @param string     $get
     *
     * @return string
     */
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

    /**
     * Restituisce il codice semplificato per il link alla stampa.
     *
     * @deprecated 2.4.1
     *
     * @param string|int $print
     * @param int        $id_record
     * @param string     $btn
     * @param string     $title
     * @param string     $icon
     * @param string     $get
     *
     * @return string
     */
    public static function getLink($print, $id_record, $btn = null, $title = null, $icon = null, $get = '')
    {
        return '{( "name": "button", "type": "print", "id": "'.$print.'", "id_record": "'.$id_record.'", "label": "'.$title.'", "icon": "'.$icon.'", "parameters": "'.$get.'", "class": "'.$btn.'" )}';
    }

    /**
     * Restituisce il link per la visualizzazione della stampa.
     *
     * @param string|int $print
     * @param int        $id_record
     * @param string     $filename
     *
     * @return string
     */
    public static function getPreviewLink($print, $id_record, $filename)
    {
        self::render($print, $id_record, $filename);

        return self::getPDFLink($filename);
    }

    /**
     * Restituisce il link per la visualizzazione del PDF.
     *
     * @param string|int $print
     * @param int        $id_record
     * @param string     $filename
     *
     * @return string
     */
    public static function getPDFLink($filename)
    {
        return ROOTDIR.'/assets/dist/pdfjs/web/viewer.html?file=../../../../'.ltrim(str_replace(DOCROOT, '', $filename), '/');
    }

    /**
     * Individua il percorso per il file.
     *
     * @param string|int $template
     * @param string     $file
     *
     * @return string|null
     */
    public static function filepath($template, $file)
    {
        $template = self::get($template);
        $directory = 'templates/'.$template['directory'].'|custom|';

        return App::filepath($directory, $file);
    }

    /**
     * Restituisce un array associativo dalla codifica JSON delle opzioni di stampa.
     *
     * @param string $string
     *
     * @return array
     */
    protected static function readOptions($string)
    {
        // Fix per contenuti con newline integrate
        $string = str_replace(["\n", "\r"], ['\\n', '\\r'], $string);

        $result = (array) json_decode($string, true);

        return $result;
    }

    /**
     * Controlla se la stampa segue lo standard HTML2PDF.
     *
     * @param string|int $print
     *
     * @return bool
     */
    protected static function isOldStandard($print)
    {
        $infos = self::get($print);

        return file_exists($infos['full_directory'].'/pdfgen.'.$infos['directory'].'.php') || file_exists($infos['full_directory'].'/custom/pdfgen.'.$infos['directory'].'.php');
    }

    /**
     * Controlla se la stampa segue lo standard MPDF.
     *
     * @param string|int $print
     *
     * @return bool
     */
    protected static function isNewStandard($print)
    {
        return !self::isOldStandard($print);
    }

    /**
     * Crea la stampa secondo il formato deprecato HTML2PDF.
     *
     * @param string|int $id_print
     * @param int        $id_record
     * @param string     $filename
     * @param string     $format
     */
    protected static function oldLoader($id_print, $id_record, $filename = null, $format = 'A4')
    {
        $infos = self::get($id_print);
        $options = self::readOptions($infos['options']);
        $docroot = DOCROOT;

        $dbo = $database = database();

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

    /**
     * Crea la stampa secondo il formato modulare MPDF.
     *
     * @param string|int $id_print
     * @param int        $id_record
     * @param string     $filename
     */
    protected static function loader($id_print, $id_record, $filename = null)
    {
        $infos = self::get($id_print);
        $options = self::readOptions($infos['options']);

        $dbo = $database = database();

        $user = Auth::user();

        // Impostazioni di default
        $default = include App::filepath('templates/base|custom|', 'settings.php');

        // Impostazioni personalizzate della stampa
        $custom = include self::filepath($id_print, 'settings.php');

        // Individuazione delle impostazioni finali
        $settings = array_merge($default, (array) $custom);

        // Individuazione delle variabili fondamentali per la sostituzione dei contenuti
        include self::filepath($id_print, 'init.php');

        // Individuazione delle variabili per la sostituzione
        include DOCROOT.'/templates/info.php';

        // Generazione dei contenuti della stampa
        ob_start();
        include self::filepath($id_print, 'body.php');
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
        include self::filepath($id_print, 'header.php');
        $head = ob_get_clean();

        // Generazione dei contenuti del footer
        ob_start();
        include self::filepath($id_print, 'footer.php');
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

        // Instanziamento dell'oggetto mPDF
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => $settings['format'],
            'orientation' => strtoupper($settings['orientation']) == 'L' ? 'L' : 'P',
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
}
