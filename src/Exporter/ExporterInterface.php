<?php

namespace Exporter;

/**
 * Interfaccia che definisce la struttura di base per la gestione delle esportazioni dei dati del gestionale in formati esterni.
 *
 * @since 2.4.18
 */
interface ExporterInterface
{
    /**
     * Restitusice i campi disponibili all'esportazione.
     *
     * @return mixed
     */
    public function getAvailableFields();

    /**
     * Imposta l'header (potenziale) per il documento da importare.
     *
     * @return mixed
     */
    public function setHeader();

    /**
     * Restituisce l'insieme dei record del gestionale da esportare.
     *
     * @return array
     */
    public function getRecords();

    /**
     * Imposta l'insieme dei record del gestionale da esportare.
     *
     * @param $records
     *
     * @return void
     */
    public function setRecords($records);

    /**
     * Esporta l'insieme dei record del documento nel gestionale.
     *
     * @return int
     */
    public function exportRecords();

    /**
     * Gestisce le operazioni di esportazione per un singolo record.
     *
     * @param $record
     *
     * @return bool
     */
    public function export($record);
}
