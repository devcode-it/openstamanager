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

namespace Importer;

use League\Csv\Reader;

/**
 * Classe dedicata alla gestione dell'importazione da file CSV.
 *
 * @since 2.4.17
 */
abstract class CSVImporter implements ImporterInterface
{
    protected $csv;

    protected $column_associations;
    protected $primary_key;

    /**
     * Array per memorizzare i record che non sono stati importati a causa di errori.
     */
    protected $failed_records = [];

    /**
     * Array per memorizzare le righe originali che non sono state importate.
     */
    protected $failed_rows = [];

    public function __construct($file)
    {
        // Impostazione automatica per i caratteri di fine riga
        if (!ini_get('default_socket_timeout')) {
            ini_set('default_socket_timeout', '0');
        }

        // Gestione del file CSV
        $this->csv = Reader::createFromPath($file, 'r');
        $this->csv->setDelimiter(';');

        $this->column_associations = [];
    }

    public function init()
    {
    }

    public function complete()
    {
    }

    public function getColumnAssociations()
    {
        return $this->column_associations;
    }

    public function setColumnAssociation($column_key, $field_key)
    {
        $this->column_associations[$column_key] = $this->getAvailableFields()[$field_key]['field'];
    }

    abstract public function getAvailableFields();

    public function getHeader()
    {
        $first_row = $this->getRows(0, 1);

        return array_shift($first_row);
    }

    public function getRows($offset, $length)
    {
        $rows = [];
        for ($i = 0; $i < $length; ++$i) {
            // Lettura di una singola riga alla volta
            $row = $this->csv->fetchOne($offset + $i);
            if (empty($row)) {
                break;
            }

            // Aggiunta all'insieme dei record
            $rows[] = \Filter::parse($row);
        }

        return $rows;
    }

    public function importRows($offset, $length, $update_record = true, $add_record = true)
    {
        $rows = $this->getRows($offset, $length);
        $imported_count = 0;
        $failed_count = 0;

        foreach ($rows as $row) {
            // Interpretazione della riga come record
            $record = $this->getRecord($row);

            // Verifica se tutti i campi obbligatori sono presenti
            $missing_required_fields = [];
            foreach ($this->getAvailableFields() as $field) {
                if (isset($field['required']) && $field['required'] === true && array_key_exists($field['field'], $record)) {
                    if (trim((string) $record[$field['field']]) === '') {
                        $missing_required_fields[] = $field['field'];
                    }
                }
            }

            // Caso speciale per anagrafiche: almeno uno tra telefono e partita IVA deve essere presente
            $is_anagrafica_import = str_contains(static::class, 'Anagrafiche');
            if ($is_anagrafica_import) {
                $telefono_present = !empty($record['telefono']);
                $piva_present = !empty($record['piva']);

                if (!$telefono_present && !$piva_present) {
                    $missing_required_fields[] = 'telefono/piva';
                }
            }

            // Se mancano campi obbligatori, aggiungi il record ai falliti
            if (!empty($missing_required_fields)) {
                $this->failed_records[] = $record;
                $this->failed_rows[] = $row;
                ++$failed_count;
                continue;
            }

            // Importazione del record
            $result = $this->import($record, $update_record, $add_record);

            // Se l'importazione fallisce, aggiungi il record ai falliti
            if ($result === false) {
                $this->failed_records[] = $record;
                $this->failed_rows[] = $row;
                ++$failed_count;
            } else {
                ++$imported_count;
            }
        }

        return [
            'imported' => $imported_count,
            'failed' => $failed_count,
            'total' => count($rows),
        ];
    }

    abstract public function import($record, $update_record, $add_record);

    public function getPrimaryKey()
    {
        return $this->primary_key;
    }

    public function setPrimaryKey($field_key)
    {
        $this->primary_key = $this->getAvailableFields()[$field_key]['field'];
    }

    public static function createExample($filepath)
    {
        $content = static::getExample();

        $file = fopen($filepath, 'w');
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

        foreach ($content as $row) {
            fputcsv($file, $row, ';');
        }

        fclose($file);
    }

    /**
     * Verifica se un campo è obbligatorio.
     *
     * @param string $field_name Nome del campo
     *
     * @return bool
     */
    public function isFieldRequired($field_name)
    {
        foreach ($this->getAvailableFields() as $field) {
            if ($field['field'] === $field_name) {
                return isset($field['required']) && $field['required'];
            }
        }

        return false;
    }

    /**
     * Verifica se tutti i campi obbligatori sono stati mappati.
     *
     * @return bool
     */
    public function areRequiredFieldsMapped()
    {
        $associations = $this->getColumnAssociations();
        $mapped_fields = array_values($associations);

        // Verifica campi obbligatori standard
        foreach ($this->getAvailableFields() as $field) {
            if (isset($field['required']) && $field['required'] === true) {
                if (!in_array($field['field'], $mapped_fields)) {
                    return false;
                }
            }
        }

        // Caso speciale per anagrafiche: almeno uno tra telefono e partita IVA deve essere mappato
        $telefono_mapped = in_array('telefono', $mapped_fields);
        $piva_mapped = in_array('piva', $mapped_fields);

        // Se entrambi i campi sono marcati come required=false ma sono in realtà
        // parte di una condizione OR (almeno uno dei due deve essere presente)
        $fields = $this->getAvailableFields();
        $has_telefono_field = false;
        $has_piva_field = false;

        foreach ($fields as $field) {
            if ($field['field'] === 'telefono' && isset($field['required']) && $field['required'] === false) {
                $has_telefono_field = true;
            }
            if ($field['field'] === 'piva' && isset($field['required']) && $field['required'] === false) {
                $has_piva_field = true;
            }
        }

        // Se entrambi i campi sono presenti con required=false, allora almeno uno deve essere mappato
        if ($has_telefono_field && $has_piva_field && !$telefono_mapped && !$piva_mapped) {
            return false;
        }

        return true;
    }

    /**
     * Restituisce i record che non sono stati importati a causa di errori.
     *
     * @return array
     */
    public function getFailedRecords()
    {
        return $this->failed_records;
    }

    /**
     * Salva i record falliti in un file CSV.
     *
     * @param string $filepath Percorso del file in cui salvare i record falliti
     *
     * @return string Percorso del file salvato
     */
    public function saveFailedRecords($filepath)
    {
        if (empty($this->failed_rows)) {
            return '';
        }

        // Crea la directory se non esiste
        $dir = dirname($filepath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $file = fopen($filepath, 'w');
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

        // Scrivi l'intestazione
        $header = $this->getHeader();
        fputcsv($file, $header, ';');

        // Scrivi le righe fallite
        foreach ($this->failed_rows as $row) {
            fputcsv($file, $row, ';');
        }

        fclose($file);

        return $filepath;
    }

    /**
     * Interpreta una riga del CSV secondo i campi impostati dinamicamente.
     *
     * @return array
     */
    protected function getRecord($row)
    {
        $associations = $this->getColumnAssociations();

        // Interpretazione della riga come record
        $record = [];
        foreach ($row as $key => $value) {
            $field = $associations[$key] ?? null;
            if (!empty($field)) {
                $record[$field] = $value;
            }
        }

        return $record;
    }
}
