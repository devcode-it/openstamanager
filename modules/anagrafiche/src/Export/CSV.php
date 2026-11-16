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
    protected $includi_referenti = false;

    public function setIncludiReferenti($includi_referenti)
    {
        $this->includi_referenti = (bool) $includi_referenti;
    }

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
                'field' => 'p_iva',
                'label' => 'Partita IVA',
            ],
            [
                'field' => 'codice_iban',
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
                'field' => 'id_agente',
                'label' => 'ID Agente',
            ],
            [
                'field' => 'id_pagamento_vendite',
                'label' => 'ID Pagamento',
            ],
            [
                'field' => 'id',
                'label' => 'Tipo',
            ],
            [
                'field' => 'tipo',
                'label' => 'Tipologia',
            ],
            [
                'field' => 'referente.nome',
                'label' => 'Referente',
            ],
            [
                'field' => 'referente.mansione',
                'label' => 'Mansione referente',
            ],
            [
                'field' => 'referente.telefono',
                'label' => 'Telefono referente',
            ],
            [
                'field' => 'referente.email',
                'label' => 'Email referente',
            ],
            [
                'field' => 'referente.sede',
                'label' => 'Sede referente',
            ],
        ];
    }

    public function getRecords()
    {
        return Anagrafica::all();
    }

    public function exportRecords()
    {
        // Esportazione header
        $fields = $this->getAvailableFields();
        $this->csv->insertOne(array_column($fields, 'label'));

        $records = $this->records ?: $this->getRecords();
        $count = 0;
        foreach ($records as $record) {
            if ($this->includi_referenti) {
                $referenti = $record->referenti;

                // Ripetizione dell'anagrafica su una riga per ogni referente
                foreach ($referenti as $referente) {
                    $this->export($record, $referente);
                    ++$count;
                }

                // Anagrafica senza referenti: riga singola con campi referente vuoti
                if ($referenti->isEmpty()) {
                    $this->export($record);
                    ++$count;
                }
            } else {
                $this->export($record);
                ++$count;
            }
        }

        return $count;
    }

    public function export($record, $referente = null)
    {
        $fields = $this->getAvailableFields();

        $row = [];
        foreach ($fields as $field) {
            $nome = $field['field'];

            if (str_starts_with((string) $nome, 'referente.')) {
                $contenuto = $referente !== null ? $this->getReferenteField($referente, substr((string) $nome, 10)) : null;
            } else {
                // Navigazione secondo dot notation
                $dot_notation = explode('.', (string) $nome);
                $contenuto = $record;
                foreach ($dot_notation as $segment) {
                    $contenuto = $contenuto[$segment] ?? null;
                }
            }

            // Formattazione automatica del campo
            $type = $field['type'] ?? 'string';
            if ($type == 'number') {
                $contenuto = numberFormat($contenuto);
            }

            $row[] = $contenuto;
        }

        return $this->csv->insertOne($row);
    }

    protected function getReferenteField($referente, $field)
    {
        switch ($field) {
            case 'mansione':
                return $referente->mansione?->nome;

            case 'sede':
                return $referente->id_sede == 0 ? tr('Sede legale') : $referente->sede?->nome_sede;

            default:
                return $referente->{$field};
        }
    }
}
