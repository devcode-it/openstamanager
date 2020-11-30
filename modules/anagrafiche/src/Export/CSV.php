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

namespace Modules\Anagrafiche\Export;

use Exporter\CSVExporter;
use Modules\Anagrafiche\Anagrafica;

/**
 * Struttura per la gestione delle operazioni di esportazione (in CSV) delle Anagrafiche.
 *
 * @since 2.4.18
 */
class CSV extends CSVExporter
{
    public function getAvailableFields()
    {
        return [
            [
                'field' => 'codice',
                'label' => 'Codice',
                'primary_key' => true,
            ],
            [
                'field' => 'ragione_sociale',
                'label' => 'Ragione sociale',
            ],
            [
                'field' => 'codice_destinatario',
                'label' => 'Codice destinatario',
            ],
            [
                'field' => 'provincia',
                'label' => 'Provincia',
            ],
            [
                'field' => 'citta',
                'label' => 'Città',
            ],
            [
                'field' => 'telefono',
                'label' => 'Telefono',
            ],
            [
                'field' => 'indirizzo',
                'label' => 'Indirizzo',
            ],
            [
                'field' => 'indirizzo2',
                'label' => 'Civico',
            ],
            [
                'field' => 'cap',
                'label' => 'CAP',
            ],
            [
                'field' => 'cellulare',
                'label' => 'Cellulare',
            ],
            [
                'field' => 'fax',
                'label' => 'Fax',
            ],
            [
                'field' => 'email',
                'label' => 'Email',
            ],
            [
                'field' => 'pec',
                'label' => 'PEC',
            ],
            [
                'field' => 'codice_fiscale',
                'label' => 'Codice Fiscale',
            ],
            [
                'field' => 'data_nascita',
                'label' => 'Data di nascita',
            ],
            [
                'field' => 'luogo_nascita',
                'label' => 'Luogo di nascita',
            ],
            [
                'field' => 'sesso',
                'label' => 'Sesso',
            ],
            [
                'field' => 'piva',
                'label' => 'Partita IVA',
            ],
            [
                'field' => 'codiceiban',
                'label' => 'IBAN',
            ],
            [
                'field' => 'note',
                'label' => 'Note',
            ],
            [
                'field' => 'nazione.nome',
                'label' => 'Nazione',
            ],
            [
                'field' => 'idagente',
                'label' => 'ID Agente',
            ],
            [
                'field' => 'idpagamento_vendite',
                'label' => 'ID Pagamento',
            ],
            [
                'field' => 'idtipoanagrafica',
                'label' => 'Tipo',
            ],
            [
                'field' => 'tipo',
                'label' => 'Tipologia',
            ],
        ];
    }

    public function getRecords()
    {
        return Anagrafica::all();
    }
}
