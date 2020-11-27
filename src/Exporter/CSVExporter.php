<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

namespace Exporter;

use League\Csv\Writer;

/**
 * Classe dedicata alla gestione dell'importazione da file CSV.
 *
 * @since 2.4.18
 */
abstract class CSVExporter implements ExporterInterface
{
    protected $csv;
    protected $records;

    public function __construct($file)
    {
        // Impostazione automatica per i caratteri di fine riga
        if (!ini_get('auto_detect_line_endings')) {
            ini_set('auto_detect_line_endings', '1');
        }

        // Gestione del file CSV
        $this->csv = Writer::createFromPath($file, 'w+');
        $this->csv->setDelimiter(';');
    }

    abstract public function getAvailableFields();

    abstract public function getRecords();

    public function setRecords($records)
    {
        $this->records = $records;
    }

    public function setHeader()
    {
        $fields = $this->getAvailableFields();
        $header = array_map(function ($item) {
            return $item['label'];
        }, $fields);

        return $this->csv->insertOne($header);
    }

    public function exportRecords()
    {
        // Esportazione header
        $fields = $this->getAvailableFields();
        $this->csv->insertOne(array_column($fields, 'label'));

        $records = $this->records ?: $this->getRecords();
        foreach ($records as $record) {
            // Esportazione del record
            $this->export($record);
        }

        return count($records);
    }

    public function export($record)
    {
        $fields = $this->getAvailableFields();

        $row = [];
        foreach ($fields as $field) {
            $nome = $field['field'];

            // Navigazione secondo dot notation
            $dot_notation = explode('.', $nome);
            $contenuto = $record;
            foreach ($dot_notation as $segment) {
                $contenuto = isset($contenuto[$segment]) ? $contenuto[$segment] : null;
            }

            $row[] = $contenuto;
        }

        return $this->csv->insertOne($row);
    }
}
