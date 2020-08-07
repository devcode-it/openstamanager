<?php

namespace Importer;

use Filter;
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

    public function __construct($file)
    {
        // Impostazione automatica per i caratteri di fine riga
        if (!ini_get('auto_detect_line_endings')) {
            ini_set('auto_detect_line_endings', '1');
        }

        // Gestione del file CSV
        $this->csv = Reader::createFromPath($file, 'r');
        $this->csv->setDelimiter(';');

        $this->column_associations = [];
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
            $rows[] = Filter::parse($row);
        }

        return $rows;
    }

    public function importRows($offset, $length)
    {
        $associations = $this->getColumnAssociations();

        $rows = $this->getRows($offset, $length);
        foreach ($rows as $row) {
            // Interpretazione della riga come record
            $record = [];
            foreach ($row as $key => $value) {
                $field = isset($associations[$key]) ? $associations[$key] : null;
                if (!empty($field)) {
                    $record[$field] = $value;
                }
            }

            // Importazione del record
            $this->import($record);
        }

        return count($rows);
    }

    abstract public function import($record);

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
}
