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

    /**
     * Array per memorizzare i messaggi di errore per i record falliti.
     */
    protected $failed_errors = [];

    public function __construct($file)
    {
        try {
            // Impostazione automatica per i caratteri di fine riga
            if (!ini_get('default_socket_timeout')) {
                ini_set('default_socket_timeout', '0');
            }

            // Gestione del file CSV
            $this->csv = Reader::createFromPath($file, 'r');
            $this->csv->setDelimiter(';');

            $this->column_associations = [];
        } catch (\Exception $e) {
            error_log('Errore durante creazione CSV reader: '.$e->getMessage()."\n".$e->getTraceAsString());
            throw $e;
        }
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
        $primary_key = $this->getPrimaryKey();

        $valid_rows = [];
        $valid_records = [];
        $primary_key_failed = 0;

        foreach ($rows as $row) {
            $record = $this->getRecord($row);

            if (!empty($primary_key) && (empty($record[$primary_key]) || trim((string) $record[$primary_key]) === '')) {
                $this->failed_records[] = $record;
                $this->failed_rows[] = $row;
                $this->failed_errors[] = 'Chiave primaria vuota o nulla: '.$primary_key;
                ++$failed_count;
                ++$primary_key_failed;
                continue;
            }

            $valid_rows[] = $row;
            $valid_records[] = $record;
        }

        $validated_rows = [];
        $validated_records = [];
        $required_field_failed = 0;

        foreach ($valid_records as $index => $record) {
            $row = $valid_rows[$index];
            $missing_required_fields = [];

            foreach ($this->getAvailableFields() as $field) {
                if (isset($field['required']) && $field['required'] === true) {
                    if (!array_key_exists($field['field'], $record) || trim((string) $record[$field['field']]) === '') {
                        $missing_required_fields[] = $field['field'];
                    }
                }
            }

            if (!empty($missing_required_fields)) {
                $this->failed_records[] = $record;
                $this->failed_rows[] = $row;
                $this->failed_errors[] = 'Campi obbligatori mancanti: '.implode(', ', $missing_required_fields);
                ++$failed_count;
                ++$required_field_failed;

                continue;
            }

            $validated_rows[] = $row;
            $validated_records[] = $record;
        }

        $batch_size = 100;
        $import_failed = 0;
        foreach ($validated_records as $index => $record) {
            $row = $validated_rows[$index];

            try {
                $result = $this->import($record, $update_record, $add_record);

                if ($result === false) {
                    $this->failed_records[] = $record;
                    $this->failed_rows[] = $row;
                    $this->failed_errors[] = 'Errore durante l\'importazione (errore sconosciuto)';
                    ++$failed_count;
                    ++$import_failed;
                } elseif ($result !== null) {
                    ++$imported_count;
                }
            } catch (\Exception $import_exception) {
                // Catch any exception during import and add to failed records with detailed error
                $this->failed_records[] = $record;
                $this->failed_rows[] = $row;
                $this->failed_errors[] = 'Errore durante l\'importazione: '.$import_exception->getMessage();
                ++$failed_count;
                ++$import_failed;
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

        // Caso speciale per anagrafiche: almeno uno tra telefono, partita IVA, codice fiscale e email deve essere mappato
        $telefono_mapped = in_array('telefono', $mapped_fields);
        $piva_mapped = in_array('piva', $mapped_fields);
        $codice_fiscale_mapped = in_array('codice_fiscale', $mapped_fields);
        $email_mapped = in_array('email', $mapped_fields);

        // Se i campi sono marcati come required=false ma sono in realtà
        // parte di una condizione OR (almeno uno deve essere presente)
        $fields = $this->getAvailableFields();
        $has_telefono_field = false;
        $has_piva_field = false;
        $has_codice_fiscale_field = false;
        $has_email_field = false;

        foreach ($fields as $field) {
            if ($field['field'] === 'telefono' && isset($field['required']) && $field['required'] === false) {
                $has_telefono_field = true;
            }
            if ($field['field'] === 'piva' && isset($field['required']) && $field['required'] === false) {
                $has_piva_field = true;
            }
            if ($field['field'] === 'codice_fiscale' && isset($field['required']) && $field['required'] === false) {
                $has_codice_fiscale_field = true;
            }
            if ($field['field'] === 'email' && isset($field['required']) && $field['required'] === false) {
                $has_email_field = true;
            }
        }

        // Se i campi sono presenti con required=false, allora almeno uno deve essere mappato
        if ($has_telefono_field && $has_piva_field && $has_codice_fiscale_field && $has_email_field
            && !$telefono_mapped && !$piva_mapped && !$codice_fiscale_mapped && !$email_mapped) {
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
     * Salva i record falliti in un file CSV con i messaggi di errore.
     * Accumula i record falliti da batch successivi.
     *
     * @param string $filepath Percorso del file in cui salvare i record falliti
     *
     * @return string Percorso del file salvato
     */
    public function saveFailedRecordsWithErrors($filepath)
    {
        if (empty($this->failed_rows)) {
            return '';
        }

        // Crea la directory se non esiste
        $dir = dirname($filepath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        // Controlla se il file esiste già (batch successivi)
        $file_exists = file_exists($filepath);

        // Apri il file in append mode se esiste, altrimenti in write mode
        $file = fopen($filepath, $file_exists ? 'a' : 'w');

        // Scrivi il BOM UTF-8 solo se è un file nuovo
        if (!$file_exists) {
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Scrivi l'intestazione con colonna errore
            $header = $this->getHeader();
            $header[] = 'Errore';
            fputcsv($file, $header, ';');
        }

        // Scrivi le righe fallite con errore
        foreach ($this->failed_rows as $index => $row) {
            $error_message = $this->failed_errors[$index] ?? 'Errore sconosciuto';
            $row[] = $error_message;
            fputcsv($file, $row, ';');
        }

        fclose($file);

        return $filepath;
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
        return $this->saveFailedRecordsWithErrors($filepath);
    }

    /**
     * Restituisce i messaggi di errore per i record falliti.
     *
     * @return array
     */
    public function getFailedErrors()
    {
        return $this->failed_errors;
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
