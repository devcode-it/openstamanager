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

namespace App\OSM\Prints;

use AppLegacy;
use Mpdf\Mpdf;

/**
 * Classe per la gestione delle informazioni relative alle stampe installate.
 *
 * @since 2.5
 */
abstract class MPDFManager extends Manager
{
    public function getManager()
    {
        if (!isset($this->manager)) {
            $settings = $this->getSettings();

            // Instanziamento dell'oggetto mPDF
            $manager = new Mpdf([
                'mode' => 'utf-8',
                'format' => $settings['format'],
                'orientation' => strtoupper($settings['orientation']) == 'L' ? 'L' : 'P',
                'font-size' => $settings['font-size'],
                'margin_left' => $settings['margins']['left'],
                'margin_right' => $settings['margins']['right'],
                'setAutoBottomMargin' => 'stretch',
                'setAutoTopMargin' => 'stretch',

                // Abilitazione per lo standard PDF/A
                //'PDFA' => true,
                //'PDFAauto' => true,
            ]);

            // Inclusione dei fogli di stile CSS
            $styles = [
                AppLegacy::filepath('templates/base|custom|', 'bootstrap.css'),
                AppLegacy::filepath('templates/base|custom|', 'style.css'),
            ];

            foreach ($styles as $value) {
                $manager->WriteHTML(file_get_contents($value), 1);
            }

            // Impostazione del font-size
            $manager->WriteHTML('body {font-size: '.$settings['font-size'].'pt;}', 1);

            $this->manager = $manager;
        }

        return $this->manager;
    }

    /**
     * Genera la stampa PDF richiesta.
     */
    public function generate(?string $directory = null): array
    {
        $info = $this->init();
        $settings = $this->getSettings();
        $manager = $this->getManager();

        $replaces = $this->getReplaces($info['id_cliente'], $info['id_sede']);

        $args = array_merge($info, $replaces);

        // Impostazione header
        $this->renderHeader($args);

        // Impostazione footer
        $this->renderFooter($args);

        // Impostazione body
        $this->renderBody($args);

        // Impostazione footer per l'ultima pagina
        if (!empty($options['last-page-footer'])) {
            $args['is_last_page'] = true;
            $footer = $this->getFooter($args);

            $manager->WriteHTML('<div class="fake-footer">'.$footer.'</div>');
            $manager->WriteHTML('<div style="position:absolute; bottom: 13mm; margin-right: '.($settings['margins']['right']).'mm">'.$footer.'</div>');
        }

        $file = $this->getFileData($this->record_id, $directory, $replaces);
        $title = $file['name'];

        // Impostazione del titolo del PDF
        $this->getManager()->SetTitle($title);

        return $file;
    }

    /**
     * Genera la stampa PDF richiesta e la visualizza nel browser.
     */
    public function render(): void
    {
        $this->generate();

        // Creazione effettiva del PDF
        $this->getManager()->Output(null, \Mpdf\Output\Destination::INLINE);
    }

    /**
     * Genera la stampa PDF richiesta e la visualizza nel browser.
     */
    public function save(string $directory): void
    {
        parent::save($directory);
        $file = $this->generate($directory);

        // Creazione effettiva del PDF
        $this->getManager()->Output($file['path'], \Mpdf\Output\Destination::FILE);
    }

    protected function renderHeader(array $args): void
    {
        $content = $this->getHeader($args);

        // Impostazione di header
        $this->getManager()->SetHTMLHeader($content);
    }

    protected function renderFooter(array $args): void
    {
        $content = $this->getFooter($args);

        // Impostazione di footer
        $this->getManager()->SetHTMLFooter($content);
    }
}
