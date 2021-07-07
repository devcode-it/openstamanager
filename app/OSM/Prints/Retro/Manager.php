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

namespace App\OSM\Prints\Retro;

use App\OSM\Prints\MPDFManager;
use AppLegacy;

class Manager extends MPDFManager
{
    protected $body;
    protected $header;
    protected $footer;

    protected function renderBody(array $args): void
    {
        $this->load($args);

        $this->getManager()->WriteHTML($this->body);
    }

    protected function load(array $args): void
    {
        if (isset($this->body)) {
            return;
        }

        // Fix per le variabili in PHP
        foreach ($args['cliente'] as $key => $value) {
            $args['c_'.$key] = $value;
        }

        foreach ($args['azienda'] as $key => $value) {
            $args['f_'.$key] = $value;
        }

        extract($args);
        $dbo = $database = database();

        ob_start();
        include $this->filepath('header.php');
        $content = ob_get_clean();

        $this->header = $this->replace($content);

        ob_start();
        include $this->filepath('body.php');
        $content = ob_get_clean();

        if (!empty($autofill)) {
            $result = $autofill->generate();

            $content = str_replace('|autofill|', $result, $content);
        }

        $this->body = $this->replace($content);

        ob_start();
        include $this->filepath('footer.php');
        $content = ob_get_clean();

        $this->footer = $this->replace($content);
    }

    protected function getReplaces(?int $id_cliente = null, ?int $id_sede = null): array
    {
        $replaces = parent::getReplaces($id_cliente, $id_sede);

        // Logo specifico della stampa
        $logo = \Prints::filepath($this->print->id, 'logo_azienda.jpg');
        $logo = $logo ?: $replaces['default_logo'];

        // Valori aggiuntivi per la sostituzione
        $this->replaces = array_merge($replaces, [
            'logo' => $logo,
        ]);

        return $this->replaces;
    }

    protected function replace($content): string
    {
        $info = $this->init();
        $replaces = $this->getReplaces($info['id_cliente'], $info['id_sede']);

        $replaces = array_merge($replaces, (array) $info['custom']);

        $list = [];
        foreach ($replaces as $key => $value) {
            if (!is_array($value)) {
                $list[$key] = $value;
            }
        }

        foreach ($replaces['cliente'] as $key => $value) {
            $list['c_'.$key] = $value;
        }

        foreach ($replaces['azienda'] as $key => $value) {
            $list['f_'.$key] = $value;
        }

        $results = [];
        foreach ($list as $key => $value) {
            $results['$'.$key.'$'] = $value;
        }

        return replace($content, $results);
    }

    protected function getPath(): string
    {
        return base_path().'/templates/'.$this->print->directory;
    }

    protected function filepath($file): ?string
    {
        return AppLegacy::filepath($this->getPath().'|custom|', $file);
    }

    protected function init(): array
    {
        $record_id = $id_record = $this->record_id;
        $module_id = $id_module = $this->print->module->id;
        $print_id = $id_print = $this->print->id;

        $dbo = $database = database();

        // Individuazione delle variabili fondamentali per la sostituzione dei contenuti
        include $this->filepath('init.php');

        return get_defined_vars();
    }

    protected function getTemplateSettings(): array
    {
        $file = $this->filepath('settings.php');

        if (file_exists($file)) {
            return include $file;
        }

        return [];
    }

    protected function getTemplateHeader(array $args): string
    {
        $this->load($args);

        return $this->header;
    }

    protected function getTemplateFooter(array $args): string
    {
        $this->load($args);

        return $this->footer;
    }
}
