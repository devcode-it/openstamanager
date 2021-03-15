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

use App;
use AppLegacy;

abstract class Manager extends App\OSM\ComponentManager
{
    protected $record_id;

    protected $manager;
    protected $replaces;

    public function setRecord(?int $record_id = null)
    {
        $this->record_id = $record_id;
    }

    /**
     * Genera e salva la stampa PDF richiesta.
     */
    public function save(string $directory): void
    {
        if (empty($directory) || !directory($directory)) {
            throw new \InvalidArgumentException();
        }
    }

    /**
     * Genera la stampa PDF richiesta e la visualizza nel browser.
     */
    abstract public function render(array $args = []): string;

    /**
     * Genera la stampa PDF richiesta.
     */
    abstract public function generate(?string $directory = null): array;

    protected function getSettings(): array
    {
        // Impostazioni di default
        $default = include AppLegacy::filepath('templates/base|custom|', 'settings.php');

        // Impostazioni personalizzate della stampa
        $custom = $this->getTemplateSettings();

        // Individuazione delle impostazioni finali
        $settings = array_merge($default, (array) $custom);

        return $settings;
    }

    protected function getHeader(array $args): string
    {
        $content = $this->getTemplateHeader($args);

        return !empty($content) ? $content : '$default_header$';
    }

    protected function getFooter(array $args): string
    {
        $content = $this->getTemplateFooter($args);

        return !empty($content) ? $content : '$default_footer$';
    }

    protected function getReplaces(?int $id_cliente = null, ?int $id_sede = null): array
    {
        if (isset($this->replaces)) {
            return $this->replaces;
        }

        $database = $this->database;
        $id_record = $this->record_id;

        // Informazioni cliente
        $query = 'SELECT an_anagrafiche.*, an_sedi.*,
            IF(an_sedi.codice_fiscale != "", an_sedi.codice_fiscale, sede_legale.codice_fiscale) AS codice_fiscale,
            IF(an_sedi.piva != "", an_sedi.piva, sede_legale.piva) AS piva
        FROM an_anagrafiche
            INNER JOIN an_sedi ON an_anagrafiche.idanagrafica = an_sedi.idanagrafica
            INNER JOIN an_sedi AS sede_legale ON an_anagrafiche.id_sede_legale = an_sedi.id
        WHERE an_sedi.idanagrafica='.prepare($id_cliente);
        if (empty($id_sede)) {
            $query .= ' AND `an_sedi`.`id`=`an_anagrafiche`.`id_sede_legale`';
        } else {
            $query .= ' AND `an_sedi`.`id`='.prepare($id_sede);
        }
        $cliente = $database->fetchOne($query);

        // Informazioni azienda
        $id_azienda = setting('Azienda predefinita');
        $azienda = $database->fetchOne('SELECT *, (SELECT iban FROM co_banche WHERE id IN (SELECT idbanca FROM co_documenti WHERE id = '.prepare($id_record).' ) ) AS codiceiban, (SELECT nome FROM co_banche WHERE id IN (SELECT idbanca FROM co_documenti WHERE id = '.prepare($id_record).' ) ) AS appoggiobancario, (SELECT bic FROM co_banche WHERE id IN (SELECT idbanca FROM co_documenti WHERE id = '.prepare($id_record).' ) ) AS bic FROM an_anagrafiche WHERE idanagrafica = '.prepare($id_azienda));

        // Prefissi e contenuti del replace
        $results = [
            'cliente' => $cliente,
            'azienda' => $azienda,
        ];

        foreach ($results as $prefix => $values) {
            // Eventuali estensioni dei contenuti
            $citta = '';
            if (!empty($values['cap'])) {
                $citta .= $values['cap'];
            }
            if (!empty($values['citta'])) {
                $citta .= ' '.$values['citta'];
            }
            if (!empty($values['provincia'])) {
                $citta .= ' ('.$values['provincia'].')';
            }

            $results[$prefix]['citta_full'] = $citta;
        }

        // Header di default
        $header_file = AppLegacy::filepath('templates/base|custom|/header.php');
        $default_header = include $header_file;
        $default_header = !empty($options['hide-header']) ? '' : $default_header;

        // Footer di default
        $footer_file = AppLegacy::filepath('templates/base|custom|/footer.php');
        $default_footer = include $footer_file;
        $default_footer = !empty($options['hide-footer']) ? '' : $default_footer;

        // Logo di default
        $default_logo = AppLegacy::filepath('templates/base|custom|/logo_azienda.jpg');

        // Logo generico
        if (!empty(setting('Logo stampe'))) {
            $default_logo = AppLegacy::filepath('files/anagrafiche/'.setting('Logo stampe'));
        }

        // Valori aggiuntivi per la sostituzione
        $this->replaces = array_merge($results, [
            'default_header' => $default_header,
            'default_footer' => $default_footer,
            'default_logo' => $default_logo,
        ]);

        return $this->replaces;
    }

    protected function getFileData($directory, $original_replaces)
    {
        $id_record = $this->record_id;
        $module = $this->print->module;

        $name = $this->print->filename.'.pdf';
        $name = $module->replacePlaceholders($id_record, $name);

        $replaces = [];
        foreach ($original_replaces as $key => $value) {
            $key = str_replace('$', '', $key);

            $replaces['{'.$key.'}'] = $value;
        }

        $name = replace($name, $replaces);

        $filename = sanitizeFilename($name);
        $file = rtrim($directory, '/').'/'.$filename;

        return [
            'name' => $name,
            'path' => $file,
        ];
    }

    abstract protected function init(): array;

    abstract protected function getManager();

    abstract protected function renderHeader(array $args): void;

    abstract protected function renderFooter(array $args): void;

    abstract protected function renderBody(array $args): void;

    abstract protected function getTemplateSettings(): array;

    abstract protected function getTemplateHeader(array $args): string;

    abstract protected function getTemplateFooter(array $args): string;
}
