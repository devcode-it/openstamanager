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

namespace Modules\Anagrafiche\Import;

use Importer\CSVImporter;
use Modules\Anagrafiche\Anagrafica;
use Modules\Anagrafiche\Tipo;
use Modules\Anagrafiche\Nazione;

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
                    'Codice',
                    'Codice interno',
                    'Numero',
                ],
            ],
            [
                'field' => 'ragione_sociale',
                'label' => 'Ragione sociale',
                'names' => [
                    'Denominazione',
                    'Ragione sociale',
                ],
            ],
            [
                'field' => 'nome',
                'label' => 'Nome',
            ],
            [
                'field' => 'cognome',
                'label' => 'Cognome',
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
                'field' => 'sitoweb',
                'label' => 'Sito Web',
                'names' => [
                    'Sito web',
                    'Website',
                    'Sito',
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
                'field' => 'tipologia',
                'label' => 'Tipo di anagrafica (Cliente, Fornitore)',
                'names' => [
                    'Tipo',
                    'tipo',
                    'idtipo',
                ],
            ],
            [
                'field' => 'tipo',
                'label' => 'Tipologia (Privato, Ente pubblico, Azienda)',
                'names' => [
                    'Tipologia',
                ],
            ],
            [
                'field' => 'split_payment',
                'label' => 'Split Payment',
                'names' => [
                    'Split Payment',
                    'split payment',
                    'split_payment',
                ],
            ],
            [
                'field' => 'id_settore',
                'label' => 'Settore merceologico',
                'names' => [
                    'settore',
                    'settore merceologico',
                ],
            ],
        ];
    }

    public function import($record)
    {
        $database = database();
        $primary_key = $this->getPrimaryKey();
        $id_azienda = setting('Azienda predefinita');

        // Compilo la ragione sociale se sono valorizzati cognome e nome
        if (!$record['ragione_sociale'] && ($record['cognome'] && $record['nome'])) {
            $record['ragione_sociale'] = $record['cognome'].' '.$record['nome'];
        }
        unset($record['cognome']);
        unset($record['nome']);

        // Individuazione del tipo dell'anagrafica
        $tipologie = [];
        if (!empty($record['tipologia'])) {
            $tipi_selezionati = explode(',', $record['tipologia']);

            foreach ($tipi_selezionati as $tipo) {
                $id_tipo = (new Tipo)->getByName($tipo)->id_record;

                // Creo il tipo anagrafica se non esiste
                if (empty($id_tipo)) {
                    $id_tipo = database()->query('INSERT INTO `an_tipianagrafiche` (`id`, `default`) VALUES (NULL, `1`)');
                    $database->insert('an_tipianagrafiche_lang', [
                        'id_lang' => setting('Lingua'),
                        'id_record' => $id_tipo,
                        'name' => $tipo,
                    ])['id'];

                    $id_tipo = (new Tipo)->getByName($tipo)->id_record;
                }

                $tipologie[] = $id_tipo;
            }
        }
        unset($record['tipologia']);

        $tipo = '';
        if (!empty($record['tipo'])) {
            $tipo = $record['tipo'];
        }
        unset($record['tipo']);

        // Fix per campi con contenuti derivati da query implicite
        if (!empty($record['id_nazione'])) {
            $record['id_nazione'] = (new Nazione())->getByName($record['id_nazione'])->id_record;
        } else {
            unset($record['id_nazione']);
        }

        // Creo il settore merceologico nel caso in cui non sia presente
        $id_settore = '';
        if (!empty($record['id_settore'])) {
            $settore = $record['id_settore'];
            $id_settore = $database->fetchOne('SELECT `an_settori`.`id` FROM `an_settori` LEFT JOIN (`an_settori_lang` ON`an_settori`.`id` = `an_settori_lang`.`id_record` AND `an_settori_lang`.`id_lang` = '.prepare(setting('Lingua')).') WHERE LOWER(`name`) = LOWER('.prepare($settore).')')['id'];

            if (empty($id_settore)) {
                $id_settore = database()->query('INSERT INTO `an_settori` (`id`, `created_at`, `updated_at`) VALUES (NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)');
                $database->insert('an_settori_lang', [
                    'id_lang' => setting('Lingua'),
                    'id_record' => $id_settore,
                    'name' => $settore,
                ])['id'];
            }
        }

        // Separazione dei campi relativi alla sede legale
        $campi_sede = [
            'indirizzo',
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
            if ($primary_key != $field) {
                if (isset($record[$field])) {
                    $dati_sede[$field] = trim($record[$field]);
                    unset($record[$field]);
                }
            }
        }

        // Ricerca di eventuale anagrafica corrispondente sulla base del campo definito come primary_key (es. codice)
        if (!empty($primary_key)) {
            $anagrafica = Anagrafica::where($primary_key, '=', trim($record[$primary_key]))->first();
        }

        // Se non trovo nessuna anagrafica corrispondente, allora la creo
        if (empty($anagrafica)) {
            $anagrafica = Anagrafica::build($record['ragione_sociale']);
        }

        // Impedisco di aggiornare l'anagrafica Azienda
        if ($anagrafica->id == $id_azienda) {
            return;
        }
        unset($record['ragione_sociale']);

        $anagrafica->fill($record);
        $anagrafica->tipologie = $tipologie;
        $anagrafica->tipo = $tipo;
        $anagrafica->save();

        $sede = $anagrafica->sedeLegale;
        $sede->fill($dati_sede);
        $sede->save();
    }

    public static function getExample()
    {
        return [
            ['Codice', 'Ragione sociale', 'Nome', 'Cognome', 'Codice destinatario', 'Provincia', 'Città', 'Telefono', 'Indirizzo', 'CAP',  'Cellulare', 'Fax', 'Email', 'PEC', 'Sito Web', 'Codice fiscale', 'Data di nascita', 'Luogo di nascita', 'Sesso', 'Partita IVA', 'IBAN', 'Note', 'Nazione', 'ID Agente', 'ID pagamento', 'Tipo', 'Tipologia', 'Split Payment', 'Settore merceologico'],
            ['001', 'Mario Rossi', '', '', '12345', 'PD', 'Este', '+39 0429 60 25 12', 'Via Rovigo, 51', '35042', '+39 321 12 34 567', '', 'email@anagrafica.it', 'email@pec.it', 'www.sito.it', '', '', '', '', '123456789', 'IT60 X054 2811 1010 0000 0123 456', 'Note dell\'anagrafica di esempio', 'Italia', '', '', 'Cliente', 'Privato', '0', 'Tessile'],
        ];
    }
}
