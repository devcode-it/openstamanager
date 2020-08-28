<?php

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

            $row[] = $record[$nome];
        }

        return $this->csv->insertOne($row);
    }
}
