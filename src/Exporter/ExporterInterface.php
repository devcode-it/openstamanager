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
