<?php

namespace Importer;

/**
 * Interfaccia che definisce la struttura di base per la gestione delle importazioni di documenti come dati del gestionale.
 *
 * @since 2.4.17
 */
interface ImporterInterface
{
    /**
     * Restituisce le associazioni impostate tra colonne e campi del documento.
     *
     * @return mixed
     */
    public function getColumnAssociations();

    /**
     * Imposta l'associazione di una specifica colonna del documento al relativo campo del documento.
     *
     * @param $column_key
     * @param $field_key
     *
     * @return mixed
     */
    public function setColumnAssociation($column_key, $field_key);

    /**
     * Restitusice i campi disponibili all'importazione.
     *
     * @return mixed
     */
    public function getAvailableFields();

    /**
     * Restituisce l'header (potenziale) per il documento da importare.
     *
     * @return mixed
     */
    public function getHeader();

    /**
     * Restituisce un sottoinsieme delle righe del documento.
     *
     * @param $offset
     * @param $length
     *
     * @return array
     */
    public function getRows($offset, $length);

    /**
     * Importa un sottoinsieme delle righe del documento nel gestionale.
     *
     * @param $offset
     * @param $length
     *
     * @return int
     */
    public function importRows($offset, $length);

    /**
     * Gestisce le operazioni di importazione per un singolo record.
     *
     * @param $record
     *
     * @return bool
     */
    public function import($record);

    /**
     * Restituisce la chiave primaria impostata dall'utente.
     *
     * @return mixed
     */
    public function getPrimaryKey();

    /**
     * Imposta la chiave primaria selezionata dall'utente.
     *
     * @param $field_key
     */
    public function setPrimaryKey($field_key);

    /**
     * Restituisce un esempio di dato importabile.
     *
     * @return array
     */
    public static function getExample();
}
