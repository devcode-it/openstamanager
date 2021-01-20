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

namespace Modules\Fatture\Export;

use Exporter\CSVExporter;
use Modules\Fatture\Fattura;

/**
 * Struttura per la gestione delle operazioni di esportazione (in CSV) delle Fatture.
 *
 * @since 2.4.18
 */
class CSV extends CSVExporter
{
    public function getAvailableFields()
    {
        return [
            [
                'field' => 'id',
                'label' => 'ID',
                'primary_key' => true,
            ],
            [
                'field' => 'numero_esterno',
                'label' => 'Numero',
            ],
            [
                'field' => 'data',
                'label' => 'Data',
            ],
            [
                'field' => 'anagrafica.ragione_sociale',
                'label' => 'Ragione sociale',
            ],
            [
                'field' => 'totale',
                'label' => 'Totale',
            ],
            [
                'field' => 'stato.descrizione',
                'label' => 'Stato',
            ],
            [
                'field' => 'codice_stato_fe',
                'label' => 'Stato FE',
            ],
        ];
    }

    public function getRecords()
    {
        return Fattura::all();
    }
}
