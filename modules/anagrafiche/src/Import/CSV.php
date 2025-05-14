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
use Modules\Anagrafiche\Nazione;
use Modules\Anagrafiche\Tipo;

/**
 * Struttura per la gestione delle operazioni di importazione (da CSV) delle Anagrafiche.
 *
 * @since 2.4.17
 */
class CSV extends CSVImporter
{
    /**
     * Definisce i campi disponibili per l'importazione.
     *
     * @return array
     */
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
                'required' => true,
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
                'required' => false, // Almeno uno tra telefono e partita IVA deve essere presente
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
                'required' => false, // Almeno uno tra telefono e partita IVA deve essere presente
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
                'required' => true,
            ],
            [
                'field' => 'tipo',
                'label' => 'Tipologia (Privato, Ente pubblico, Azienda)',
                'names' => [
                    'Tipologia',
                ],
                'required' => true,
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

    /**
     * Importa un record nel database.
     *
     * @param array $record Record da importare
     * @param bool $update_record Se true, aggiorna i record esistenti
     * @param bool $add_record Se true, aggiunge nuovi record
     * @return bool|null True se l'importazione è riuscita, false altrimenti, null se l'operazione è stata saltata
     */
    public function import($record, $update_record = true, $add_record = true)
    {
        // Validazione dei campi obbligatori
        if (empty($record['ragione_sociale'])) {
            return false;
        }

        // Verifica che almeno uno tra telefono e partita IVA sia presente
        if (empty($record['telefono']) && empty($record['piva'])) {
            return false;
        }

        $database = database();
        $primary_key = $this->getPrimaryKey();
        $id_azienda = setting('Azienda predefinita');

        // Compilo la ragione sociale se sono valorizzati cognome e nome
        if (empty($record['ragione_sociale']) && (!empty($record['cognome']) && !empty($record['nome']))) {
            $record['ragione_sociale'] = trim($record['cognome'].' '.$record['nome']);
        }
        
        // Rimuovo i campi già utilizzati per la ragione sociale
        $nome = $record['nome'] ?? '';
        $cognome = $record['cognome'] ?? '';
        unset($record['nome'], $record['cognome']);

        // Ricerca di eventuale anagrafica corrispondente sulla base del campo definito come primary_key
        $anagrafica = null;
        if (!empty($primary_key) && !empty($record[$primary_key])) {
            $anagrafica = Anagrafica::where($primary_key, '=', trim((string) $record[$primary_key]))->first();
        }

        // Controllo se creare o aggiornare il record
        if (($anagrafica && !$update_record) || (!$anagrafica && !$add_record)) {
            return null;
        }

        // Se non trovo nessuna anagrafica corrispondente, allora la creo
        if (empty($anagrafica)) {
            $anagrafica = Anagrafica::build($record['ragione_sociale']);
        }

        // Impedisco di aggiornare l'anagrafica Azienda
        if ($anagrafica->id == $id_azienda) {
            return false;
        }

        // Gestione delle tipologie
        $tipologie = $this->processaTipologie($record);
        unset($record['tipologia']);

        // Gestione del tipo
        $tipo = '';
        if (!empty($record['tipo'])) {
            $tipo = $record['tipo'];
        }
        unset($record['tipo']);

        // Gestione della nazione
        if (!empty($record['id_nazione'])) {
            $record['id_nazione'] = (new Nazione())->getByField('title', $record['id_nazione'], \Models\Locale::getPredefined()->id);
        } else {
            unset($record['id_nazione']);
        }

        // Gestione del settore merceologico
        $id_settore = $this->processaSettore($record);
        unset($record['id_settore']);

        // Gestione dell'IBAN
        $id_banca = $this->processaIBAN($record, $anagrafica);
        unset($record['codiceiban']);

        // Separazione dei campi relativi alla sede legale
        $dati_sede = $this->estraiDatiSede($record, $primary_key);

        // Rimuovo la ragione sociale dal record per evitare di sovrascriverla
        $ragione_sociale = $record['ragione_sociale'];
        unset($record['ragione_sociale']);

        // Aggiorno l'anagrafica
        $anagrafica->fill($record);
        
        // Aggiorno le tipologie solo se sono state passate nel file
        if (!empty($tipologie)) {
            $anagrafica->tipologie = $tipologie;
        }
        
        $anagrafica->id_settore = $id_settore;
        $anagrafica->tipo = $tipo;
        $anagrafica->save();

        // Aggiorno la sede legale
        $sede = $anagrafica->sedeLegale;
        $sede->fill($dati_sede);
        $sede->save();

        return true;
    }

    /**
     * Processa le tipologie dell'anagrafica.
     *
     * @param array $record Record da processare
     * @return array Array di ID delle tipologie
     */
    private function processaTipologie($record)
    {
        if (empty($record['tipologia'])) {
            return [];
        }

        $database = database();
        $tipologie = [];
        $tipi_selezionati = explode(',', (string) $record['tipologia']);

        foreach ($tipi_selezionati as $tipo) {
            $tipo = trim($tipo);
            if (empty($tipo)) {
                continue;
            }

            $tipo_obj = Tipo::where('name', $tipo)->first();
            $id_tipo = $tipo_obj ? $tipo_obj->id : null;

            // Creo il tipo anagrafica se non esiste
            if (empty($id_tipo)) {
                $database->query('INSERT INTO `an_tipianagrafiche` (`id`, `default`) VALUES (NULL, 1)');
                $id_tipo = $database->lastInsertedID();
                
                $database->insert('an_tipianagrafiche_lang', [
                    'id_lang' => \Models\Locale::getDefault()->id,
                    'id_record' => $id_tipo,
                    'name' => $tipo,
                ]);
            }

            $tipologie[] = $id_tipo;
        }

        return $tipologie;
    }

    /**
     * Processa il settore merceologico.
     *
     * @param array $record Record da processare
     * @return string|null ID del settore merceologico
     */
    private function processaSettore($record)
    {
        if (empty($record['id_settore'])) {
            return null;
        }

        $database = database();
        $settore = trim($record['id_settore']);
        
        $result = $database->fetchArray('SELECT `an_settori`.`id` FROM `an_settori` 
            LEFT JOIN `an_settori_lang` ON (`an_settori`.`id` = `an_settori_lang`.`id_record` 
            AND `an_settori_lang`.`id_lang` = '.prepare(\Models\Locale::getDefault()->id).') 
            WHERE LOWER(`title`) = LOWER('.prepare($settore).')');
        
        $id_settore = !empty($result) ? $result[0]['id'] : null;

        if (empty($id_settore)) {
            $database->query('INSERT INTO `an_settori` (`id`, `created_at`, `updated_at`) 
                VALUES (NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)');
            $id_settore = $database->lastInsertedID();
            
            $database->insert('an_settori_lang', [
                'id_lang' => \Models\Locale::getDefault()->id,
                'id_record' => $id_settore,
                'title' => $settore,
            ]);
        }

        return $id_settore;
    }

    /**
     * Processa l'IBAN.
     *
     * @param array $record Record da processare
     * @param Anagrafica $anagrafica Anagrafica associata
     * @return string|null ID della banca
     */
    private function processaIBAN($record, $anagrafica)
    {
        if (empty($record['codiceiban'])) {
            return null;
        }

        $database = database();
        $iban = trim($record['codiceiban']);
        
        $result = $database->fetchOne('SELECT `co_banche`.`id` FROM `co_banche` 
            WHERE LOWER(`iban`) = LOWER('.prepare($iban).') 
            AND `id_anagrafica` = '.$anagrafica->id.' 
            AND deleted_at IS NULL');
        
        $id_banca = !empty($result) ? $result['id'] : null;

        if (empty($id_banca)) {
            $database->query('INSERT INTO `co_banche` (`iban`, `nome`, `id_anagrafica`) 
                VALUES ('.prepare($iban).', "Banca da importazione '.addslashes($anagrafica->ragione_sociale).'", '.$anagrafica->id.')');
            $id_banca = $database->lastInsertedID();
        }

        return $id_banca;
    }

    /**
     * Estrae i dati della sede legale dal record.
     *
     * @param array $record Record da processare
     * @param string $primary_key Chiave primaria
     * @return array Dati della sede legale
     */
    private function estraiDatiSede(&$record, $primary_key)
    {
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
            if ($primary_key != $field && isset($record[$field])) {
                $dati_sede[$field] = trim((string) $record[$field]);
                unset($record[$field]);
            }
        }

        return $dati_sede;
    }

    /**
     * Restituisce un esempio di file CSV per l'importazione.
     *
     * @return array
     */
    public static function getExample()
    {
        return [
            ['Codice', 'Ragione sociale', 'Nome', 'Cognome', 'Codice destinatario', 'Provincia', 'Città', 'Telefono', 'Indirizzo', 'CAP',  'Cellulare', 'Fax', 'Email', 'PEC', 'Sito Web', 'Codice fiscale', 'Data di nascita', 'Luogo di nascita', 'Sesso', 'Partita IVA', 'IBAN', 'Note', 'Nazione', 'ID Agente', 'ID pagamento', 'Tipo', 'Tipologia', 'Split Payment', 'Settore merceologico'],
            ['001', 'Mario Rossi', '', '', '12345', 'PD', 'Este', '+39 0429 60 25 12', 'Via Rovigo, 51', '35042', '+39 321 12 34 567', '', 'email@anagrafica.it', 'email@pec.it', 'www.sito.it', '', '', '', '', '123456789', 'IT60 X054 2811 1010 0000 0123 456', 'Note dell\'anagrafica di esempio', 'Italia', '', '', 'Cliente', 'Privato', '0', 'Tessile'],
        ];
    }
}
