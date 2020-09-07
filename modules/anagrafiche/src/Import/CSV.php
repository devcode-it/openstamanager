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

namespace Modules\Anagrafiche\Import;

use Importer\CSVImporter;
use Modules\Anagrafiche\Anagrafica;

/**
 * Struttura per la gestione delle operazioni di importazione (da CSV) delle Anagrafiche.
 *
 * @since 2.4.17
 */
class CSV extends CSVImporter
{
    public function getAvailableFields()
    {
        return [
            [
                'field' => 'codice',
                'label' => 'Codice',
                'primary_key' => true,
                'names' => [
                    'Codice interno',
                    'Numero',
                    'Codice',
                ],
            ],
            [
                'field' => 'ragione_sociale',
                'label' => 'Ragione sociale',
                'names' => [
                    'Nome',
                    'Denominazione',
                    'Ragione sociale',
                ],
            ],
            [
                'field' => 'codice_destinatario',
                'label' => 'Codice destinatario',
                'names' => [
                    'Codice destinatario',
                    'Codice SDI',
                    'Codice univoco',
                    'Codice univoco ufficio',
                    'SDI',
                ],
            ],
            [
                'field' => 'provincia',
                'label' => 'Provincia',
            ],
            [
                'field' => 'citta',
                'label' => 'Città',
                'names' => [
                    'Citt_',
                    'Citt&agrave;',
                    'Città',
                    'Citta',
                ],
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
                'names' => [
                    'E-mail',
                    'Indirizzo email',
                    'Mail',
                    'Email',
                ],
            ],
            [
                'field' => 'pec',
                'label' => 'PEC',
                'names' => [
                    'E-mail PEC',
                    'Email certificata',
                    'Indirizzo email certificata',
                    'PEC',
                ],
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
                'names' => [
                    'P.IVA',
                    'P.IVA/TAX ID',
                    'TAX ID',
                    'Partita IVA',
                ],
            ],
            [
                'field' => 'codiceiban',
                'label' => 'IBAN',
            ],
            [
                'field' => 'note',
                'label' => 'Note',
                'names' => [
                    'Note Extra',
                    'Note',
                ],
            ],
            [
                'field' => 'id_nazione',
                'label' => 'Nazione',
                'names' => [
                    'Nazione',
                    'Paese',
                    'id_nazione',
                    'idnazione',
                    'nazione',
                ],
            ],
            [
                'field' => 'idagente',
                'label' => 'ID Agente',
            ],
            [
                'field' => 'idpagamento_vendite',
                'label' => 'ID Pagamento',
                'names' => [
                    'Pagamento',
                    'ID Pagamento',
                    'id_pagamento',
                    'idpagamento_vendite',
                    'idpagamento',
                ],
            ],
            [
                'field' => 'idtipoanagrafica',
                'label' => 'Tipo',
                'names' => [
                    'Tipo',
                    'tipo',
                    'idtipo',
                ],
            ],
            [
                'field' => 'tipo',
                'label' => 'Tipologia',
            ],
        ];
    }

    public function import($record)
    {
        $database = database();
        $primary_key = $this->getPrimaryKey();
        $id_azienda = setting('Azienda predefinita');

        // Individuazione del tipo dell'anagrafica
        $tipologie = [];
        if (!empty($record['idtipoanagrafica'])) {
            $tipi_selezionati = explode(',', $record['idtipoanagrafica']);

            foreach ($tipi_selezionati as $tipo) {
                $tipo_anagrafica = $database->fetchOne('SELECT idtipoanagrafica AS id FROM an_tipianagrafiche WHERE descrizione = '.prepare($tipo).' OR idtipoanagrafica = '.prepare($tipo));

                if (!empty($tipo_anagrafica)) {
                    $tipologie[] = $tipo_anagrafica['id'];
                }
            }
        }
        unset($record['idtipoanagrafica']);

        // Fix per campi con contenuti derivati da query implicite
        if (!empty($record['id_nazione'])) {
            $record['id_nazione'] = $database->fetchOne('SELECT id FROM an_nazioni WHERE LOWER(nome) = LOWER('.prepare($record['id_nazione']).') OR LOWER(iso2) = LOWER('.prepare($record['id_nazione']).')')['id'];
        }

        // Separazione dei campi relativi alla sede legale
        $campi_sede = [
            //'piva',
            //'codice_fiscale',
            //'codice_destinatario',
            'indirizzo',
            'indirizzo2',
            'citta',
            'cap',
            'provincia',
            'km',
            'id_nazione',
            'telefono',
            'fax',
            'cellulare',
            'email',
            'idzona',
            'gaddress',
            'lat',
            'lng',
        ];

        $dati_sede = [];
        foreach ($campi_sede as $field) {
            if (isset($record[$field])) {
                $dati_sede[$field] = $record[$field];
                unset($record[$field]);
            }
        }

        // Ricerca di eventuale anagrafica corrispondente sulla base del campo definito come primary_key (es. codice)
        if (!empty($primary_key)) {
            $anagrafica = Anagrafica::where($primary_key, '=', $record[$primary_key])->first();
        }

        // Se non trovo nessuna anagrafica corrispondente, allora la creo
        if (empty($anagrafica)) {
            $anagrafica = Anagrafica::build($record['ragione_sociale']);
        }

        // Impedisco di aggiornare l'anagrafica Azienda
        if ($anagrafica->id == $id_azienda) {
            return;
        }

        $anagrafica->fill($record);
        $anagrafica->tipologie = $tipologie;
        $anagrafica->save();

        $sede = $anagrafica->sedeLegale;
        $sede->fill($dati_sede);
        $sede->save();
    }

    public static function getExample()
    {
        return [
            ['Codice', 'Ragione sociale', 'Tipologia', 'Partita IVA', 'Codice destinatario', 'Nazione', 'Indirizzo', 'CAP', 'Città', 'Provincia', 'Telefono', 'Fax', 'Cellulare', 'Email', 'PEC', 'IBAN', 'Note', 'Tipo'],
            ['00001', 'Mia anagrafica', 'Azienda', '12345678910', '1234567', 'ITALIA', 'Via Giuseppe Mazzini, 123', '12345', 'Este', 'PD', '+39 0429 60 25 12', '+39 0429 456 781', '+39 321 12 34 567', 'email@anagrafica.it', 'pec@anagrafica.it', 'IT60 X054 2811 1010 0000 0123 456', 'Note dell\'anagrafica di esempio', 'Cliente,Fornitore'],
        ];
    }
}
