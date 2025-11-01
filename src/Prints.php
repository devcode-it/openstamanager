<?php

/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

use Jurosh\PDFMerge\PDFMerger;
use Models\Module;
use Models\PrintTemplate;
use Mpdf\Mpdf;
use Util\Query;

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

            $results = PrintTemplate::where('enabled', 1)->orderBy('order')->get();

            $prints = [];

            // Inizializzazione dei riferimenti
            $modules = Modules::getModules();
            foreach ($modules as $module) {
                self::$modules[$module['id']] = [];
            }

            foreach ($results as $result) {
                $result['full_directory'] = base_dir().'/templates/'.$result['directory'];

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
        $module_id = Module::find($module)->id;

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
     * @param string     $directory
     * @param bool       $return_string
     * @param bool       $overwrite
     */
    public static function render($print, $id_record = null, $directory = null, $return_string = false, $overwrite = true, $mpdfPageNumSubstitutions = [])
    {
        // ob_end_clean(); // Compatibilità con versioni vecchie delle stampe
        $dbo = $database = database();
        $infos = self::get($print);

        $additional_checks = false;
        if (!$return_string) {
            Permissions::addModule($infos['id_module']);

            $has_access = true;
            if (!empty($infos['is_record'])) {
                $module = Module::find($infos['id_module']);

                Query::setSegments(false);
                $query = Query::getQuery($module, [
                    'id' => $id_record,
                ]);
                Query::setSegments(true);

                $has_access = !empty($query) ? $dbo->fetchNum($query) !== 0 : true;
            }

            $additional_checks = !Permissions::check([], false) || !$has_access;
        }

        if (empty($infos) || empty($infos['enabled']) || $additional_checks) {
            return false;
        }

        // Individuazione della configurazione
        if (!empty($directory) && !directory($directory)) {
            $error = tr('Non hai i permessi per creare directory e files in _DIRECTORY_', [
                '_DIRECTORY_' => $directory,
            ]);

            flash()->error($error);

            echo '
                <p align="center">'.$error.'</p>';

            exit;
        }

        if (self::isCompletelyCustom($print)) {
            return self::customLoader($infos['id'], $id_record, $directory, $return_string);
        } elseif (self::isOldStandard($print)) {
            return self::oldLoader($infos['id'], $id_record, $directory, $return_string, $overwrite);
        } else {
            return self::loader($infos['id'], $id_record, $directory, $return_string, $overwrite, $mpdfPageNumSubstitutions);
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
        $infos = PrintTemplate::where('name', $print)->first()->id;

        if (empty($infos)) {
            $infos = PrintTemplate::find($print)->id;
        }

        if (empty($infos)) {
            return false;
        }

        $link = base_path_osm().'/pdfgen.php?';

        if (self::isOldStandard($infos['id'])) {
            $link .= 'ptype='.$infos['directory'];

            $link .= !empty($infos['previous']) && !empty($id_record) ? '&'.$infos['previous'].'='.$id_record : '';
        } else {
            $link .= 'id_print='.$infos;

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
     * @param string     $directory
     *
     * @return string
     */
    public static function getPreviewLink($print, $id_record, $directory)
    {
        $info = self::render($print, $id_record, $directory);

        return self::getPDFLink($info['path'].'?'.random_int(0, mt_getrandmax()));
    }

    /**
     * Restituisce il link per la visualizzazione del PDF.
     *
     * @param string $path
     *
     * @return string
     */
    public static function getPDFLink($path)
    {
        return base_path_osm().'/assets/dist/pdfjs/web/viewer.html?file='.base_url().'/'.ltrim(str_replace(base_dir(), '', $path.'?'.random_int(0, mt_getrandmax())), '/');
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
        return !self::isOldStandard($print) && !self::isCompletelyCustom($print);
    }

    /**
     * Controlla se la stampa segue lo standard completamente autonomo.
     *
     * @param string|int $print
     *
     * @return bool
     */
    protected static function isCompletelyCustom($print)
    {
        $infos = self::get($print);

        return file_exists($infos['full_directory'].'/stampa.php') || file_exists($infos['full_directory'].'/custom/stampa.php');
    }

    /**
     * Crea la stampa secondo il formato deprecato HTML2PDF.
     *
     * @param string|int $id_print
     * @param int        $id_record
     * @param string     $directory
     * @param bool       $return_string
     */
    protected static function oldLoader($id_print, $id_record, $directory = null, $return_string = false, $overwrite = true)
    {
        $format = 'A4';

        $body = '';
        $report = '';
        $footer = '';

        $infos = self::get($id_print);
        $options = self::readOptions($infos['options']);
        $docroot = base_dir();

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

        if (empty($filename)) {
            // Sostituzione di variabili generiche
            $report = str_replace('$body$', $body, $report);
            $report = str_replace('$footer$', $footer, $report);

            $report = str_replace('$font_size$', $font_size, $report);
            $report = str_replace('$body_table_params$', $body_table_params, $report);
            $report = str_replace('$table$', $table, $report);

            // Footer di default
            if (!string_contains($report, '<page_footer>')) {
                $report .= '<page_footer>$default_footer$</page_footer>';
            }

            // Operazioni di sostituzione
            include base_dir().'/templates/replace.php';

            $mode = !empty($directory) ? 'F' : 'I';
            $mode = !empty($return_string) ? 'S' : $mode;

            $file = self::getFile($infos, $id_record, $directory, $replaces);
            $title = $file['name'];
            $path = $file['path'];

            $html2pdf = new Spipu\Html2Pdf\Html2Pdf($orientation, $format, 'it', true, 'UTF-8');

            $html2pdf->writeHTML($report);
            $html2pdf->pdf->setTitle($title);

            // Ridenominazione file se l'impostazione è disattivata
            if (!$overwrite) {
                $index = 1;

                $file_parts = pathinfo((string) $path);
                $filename_no_extension = $file_parts['filename'];

                while (file_exists($directory.'/'.$file_parts['basename'])) {
                    $path = $file_parts['dirname'].'/'.$filename_no_extension.'_'.$index++.'.'.$file_parts['extension'];

                    $file_parts = pathinfo($path);
                }
            }

            $pdf = $html2pdf->output($path, $mode);
            $file['pdf'] = $pdf;
        } else {
            $file = self::getFile($infos, $id_record, $directory, $replaces);
            $file['pdf'] = file_get_contents($filename);
        }

        return $file;
    }

    protected static function getFile($record, $id_record, $directory, $original_replaces)
    {
        $module = Module::find($record['id_module']);

        $name = $record->getTranslation('filename').'.pdf';
        $name = $module->replacePlaceholders($id_record, $name);

        $replaces = [];
        foreach ($original_replaces as $key => $value) {
            $key = str_replace('$', '', $key);

            $replaces['{'.$key.'}'] = $value;
        }

        $name = replace($name, $replaces);

        $filename = sanitizeFilename($name);
        $file = (empty($directory)) ? $filename : rtrim((string) $directory, '/').'/'.$filename;

        return [
            'name' => $name,
            'path' => $file,
        ];
    }

    /**
     * Crea la stampa secondo il formato modulare MPDF.
     *
     * @param string|int $id_print
     * @param int        $id_record
     * @param string     $directory
     * @param bool       $return_string
     */
    protected static function loader($id_print, $id_record, $directory = null, $return_string = false, $overwrite = true, $mpdfPageNumSubstitutions = [])
    {
        $infos = self::get($id_print);
        $options = self::readOptions($infos['options']);

        $dbo = $database = database();

        $user = Auth::user();

        // Generazione a singoli pezzi
        $single_pieces = self::filepath($id_print, 'piece.php');

        // Impostazioni di default
        $default = include App::filepath('templates/base|custom|', 'settings.php');

        // Impostazioni personalizzate della stampa definite su file settings.php
        $print_settings = self::filepath($id_print, 'settings.php');
        if (!empty($print_settings)) {
            $custom = include $print_settings;
        }

        // Individuazione delle impostazioni finali
        $settings = array_merge($default, (array) $custom);
        $settings = array_merge($settings, (array) $options);

        // Individuazione delle variabili fondamentali per la sostituzione dei contenuti
        $print_init = self::filepath($id_print, 'init.php');
        if (!empty($print_init)) {
            include $print_init;
        }

        // Individuazione delle variabili per la sostituzione
        include base_dir().'/templates/info.php';

        // Instanziamento dell'oggetto mPDF
        $mpdf = new Mpdf([
            'format' => $settings['format'],
            'orientation' => strtoupper((string) $settings['orientation']) == 'L' ? 'L' : 'P',
            'font-size' => $settings['font-size'],
            'margin_left' => $settings['margins']['left'],
            'margin_right' => $settings['margins']['right'],

            'setAutoTopMargin' => $settings['margins']['top'] === 'auto' ? 'stretch' : false,
            'margin_top' => $settings['margins']['top'] === 'auto' ? 0 : $settings['margins']['top'], // Disabilitato se setAutoTopMargin impostato

            'setAutoBottomMargin' => $settings['margins']['bottom'] === 'auto' ? 'stretch' : false,
            'margin_bottom' => $settings['margins']['bottom'] === 'auto' ? 0 : $settings['margins']['bottom'], // Disabilitato se setAutoBottomMargin impostato

            'default_font' => 'dejavusanscondensed',

            'tempDir' => base_dir().'/files/temp',

            // Abilitazione per lo standard PDF/A
            // 'PDFA' => true,
            // 'PDFAauto' => true,
        ]);

        if (!empty($mpdfPageNumSubstitutions)) {
            $mpdf->PageNumSubstitutions[] = $mpdfPageNumSubstitutions;
        }

        if (setting('Filigrana stampe')) {
            $mpdf->SetWatermarkImage(
                base_dir().'/files/anagrafiche/'.setting('Filigrana stampe'),
                0.5,
                'F',
                'F'
            );

            // false = 'showWatermarkImage' => false,
            if ($settings['showWatermarkImage'] == null) {
                $mpdf->showWatermarkImage = true;
            } else {
                $mpdf->showWatermarkImage = intval($settings['showWatermarkImage']);
            }
        }

        // Inclusione dei fogli di stile CSS
        $styles = [
            App::filepath('templates/base|custom|', 'bootstrap.css'),
            App::filepath('templates/base|custom|', 'style.css'),
        ];

        foreach ($styles as $value) {
            $mpdf->WriteHTML(file_get_contents($value), 1);
        }

        // Impostazione del font-size
        $mpdf->WriteHTML('body {font-size: '.$settings['font-size'].'pt;}', 1);

        // Generazione totale
        if (empty($single_pieces)) {
            $rows_per_page = $options['rows-per-page'];
            ob_start();
            $print_body = self::filepath($id_print, 'body.php');
            if (!empty($print_body)) {
                include $print_body;
            }
            $report = ob_get_clean();

            if (!empty($autofill)) {
                $result = $autofill->generate();

                $report = str_replace('|autofill|', $result, $report);
            }
        }

        if (empty($filename)) {
            // Generazione dei contenuti dell'header
            ob_start();
            $print_header = self::filepath($id_print, 'header.php');
            if (!empty($print_header)) {
                include $print_header;
            }
            $head = ob_get_clean();

            // Header di default
            $head = !empty($head) ? $head : '$default_header$';

            // Generazione dei contenuti del footer
            ob_start();
            $print_footer = self::filepath($id_print, 'footer.php');
            if (!empty($print_footer)) {
                include $print_footer;
            }
            $foot = ob_get_clean();

            // Footer di default
            $foot = !empty($foot) ? $foot : '$default_footer$';

            // Operazioni di sostituzione
            include base_dir().'/templates/replace.php';

            // Impostazione di header e footer
            $mpdf->SetHTMLHeader($head);
            $mpdf->SetHTMLFooter($foot);

            // Generazione dei contenuti della stampa

            if (!empty($single_pieces)) {
                ob_start();
                $print_top = self::filepath($id_print, 'top.php');
                if (!empty($print_top)) {
                    include $print_top;
                }
                $top = ob_get_clean();

                $top = str_replace(array_keys($replaces), array_values($replaces), $top);

                $mpdf->WriteHTML($top);

                $print_piece = self::filepath($id_print, 'piece.php');
                foreach ($records as $record) {
                    ob_start();
                    if (!empty($print_piece)) {
                        include $print_piece;
                    }
                    $piece = ob_get_clean();

                    $mpdf->WriteHTML($piece);
                }

                ob_start();
                $print_bottom = self::filepath($id_print, 'bottom.php');
                if (!empty($print_bottom)) {
                    include $print_bottom;
                }
                $bottom = ob_get_clean();

                $bottom = str_replace(array_keys($replaces), array_values($replaces), $bottom);

                $mpdf->WriteHTML($bottom);

                $report = '';
            }

            // Footer visibile solo sull'ultima pagina, sempre che non abbia deciso di nasconderlo
            if (!empty($options['last-page-footer']) && empty($options['hide-footer'])) {
                // Definisco qual'è l'ultima pagina
                $is_last_page = true;

                // Generazione dei contenuti del footer
                ob_start();
                $print_footer = self::filepath($id_print, 'footer.php');
                if (!empty($print_footer)) {
                    include $print_footer;
                }
                $foot = ob_get_clean();
            }

            // Operazioni di sostituzione
            include base_dir().'/templates/replace.php';

            $mode = !empty($directory) ? 'F' : 'I';
            $mode = !empty($return_string) ? 'S' : $mode;

            $file = self::getFile($infos, $id_record, $directory, $replaces);
            $title = $file['name'];
            $path = $file['path'];

            // Ridenominazione file se l'impostazione è disattivata
            if (!$overwrite) {
                $index = 1;

                $file_parts = pathinfo((string) $path);
                $filename_no_extension = $file_parts['filename'];

                while (file_exists($directory.'/'.$file_parts['basename'])) {
                    $path = $file_parts['dirname'].'/'.$filename_no_extension.'_'.$index++.'.'.$file_parts['extension'];

                    $file_parts = pathinfo($path);
                }
            }

            // Impostazione del titolo del PDF
            $mpdf->SetTitle($title);

            // Aggiunta dei contenuti
            $mpdf->WriteHTML($report);

            // Impostazione footer per l'ultima pagina
            if (!empty($options['last-page-footer'])) {
                $mpdf->WriteHTML('<div class="hidden">'.$foot.'</div>');

                $mpdf->WriteHTML('<div style="position:absolute; bottom: 13mm; margin-right: '.$settings['margins']['right'].'mm">'.$foot.'</div>');
            }

            $id_files = $dbo->select('zz_files_print', 'id_file', [], ['id_print' => $id_print]);
            $has_pdf = false;
            foreach ($id_files as $id_file) {
                $fil = Models\Upload::find($id_file)->first();

                if ($fil->isImage()) {
                    $link = base_path_osm().'/'.$fil->filepath;
                    $mpdf->WriteHTML('<pagebreak />');
                    $mpdf->WriteHTML('<img src="'.$link.'"></img>');
                } elseif ($fil->isPdf()) {
                    $has_pdf = true;
                }
            }

            // Creazione effettiva del PDF
            if ($has_pdf) {
                $pdf_merger = new PDFMerger();
                $mpdf->Output(base_dir().'/files/'.$infos['directory'].'/'.basename((string) $path), 'F');
                $pdf_merger->addPDF(base_dir().'/files/'.$infos['directory'].'/'.basename((string) $path));
                foreach ($id_files as $id_file) {
                    $fil = Models\Upload::find($id_file)->first();
                    $pdf_merger->addPDF(base_dir().'/'.$fil->file_path, 'all');
                }
                $mode_merger = ($mode == 'F' ? 'file' : ($mode == 'D' ? 'download' : ($mode == 'S' ? 'string' : 'I')));
                $path_merger = $mode == 'F' ? base_dir().'/files/'.$infos['directory'].'/'.basename((string) $path) : $path;

                $pdf = $pdf_merger->merge($mode_merger, $path_merger);
            } else {
                $pdf = $mpdf->Output($path, $mode);
            }

            $file['pdf'] = $pdf;
        } else {
            $file = self::getFile($infos, $id_record, $directory, $replaces);
            $file['pdf'] = file_get_contents($filename);
        }

        $file['pages'] = $mpdf->pages;

        return $file;
    }

    /**
     * Gestore per il formato di stampa completamente gestito dalla stampa stessa.
     * Rischiede la compilazione della variabile $file, utilizzata coe return.
     *
     * @param null  $directory
     * @param false $return_string
     *
     * @return array
     */
    protected static function customLoader($id_print, $id_record, $directory = null, $return_string = false)
    {
        $infos = self::get($id_print);
        $options = self::readOptions($infos['options']);

        $dbo = $database = database();
        $user = Auth::user();

        $file = null;

        // Decido se usare la stampa personalizzata (se esiste) oppure quella standard
        // La stampa personalizzata deve gestire tutto manualmente
        if (file_exists($infos['full_directory'].'/custom/stampa.php')) {
            include $infos['full_directory'].'/custom/stampa.php';
        } else {
            include $infos['full_directory'].'/stampa.php';
        }

        return $file;
    }
}
