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

namespace Modules\Partitario\Import;

use Importer\CSVImporter;

/**
 * Struttura per la gestione delle operazioni di importazione (da CSV) del piano dei conti.
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
                'field' => 'numero',
                'label' => 'Conto',
                'primary_key' => true,
                'required' => true,
            ],
            [
                'field' => 'descrizione',
                'label' => 'Descrizione',
                'required' => true,
            ],
            [
                'field' => 'idpianodeiconti1',
                'label' => 'Sezione',
                'required' => true,
            ],
            [
                'field' => 'dir',
                'label' => 'Direzione',
                'names' => [
                    'Direzione',
                    'direzione',
                    'dir',
                ],
            ],
        ];
    }

    /**
     * Importa un record nel database.
     *
     * @param array $record        Record da importare
     * @param bool  $update_record Se true, aggiorna i record esistenti
     * @param bool  $add_record    Se true, aggiunge nuovi record
     *
     * @return bool|null True se l'importazione è riuscita, false altrimenti, null se l'operazione è stata saltata
     */
    public function import($record, $update_record = true, $add_record = true)
    {
        try {
            $database = database();

            // Validazione dei campi obbligatori
            if (empty($record['numero']) || empty($record['descrizione']) || empty($record['idpianodeiconti1'])) {
                return false;
            }

            // Parsing del numero di conto
            $parti_conto = $this->parseNumeroConto($record);
            if (empty($parti_conto)) {
                return false;
            }

            // Ricerca della sezione (conto1)
            $conto1 = $this->trovaConto1($record, $database);
            if (empty($conto1)) {
                return false;
            }

            // Ricerca del conto2
            $conto2 = $this->trovaConto2($parti_conto['codice_conto2'], $database);

            // Gestione dell'inserimento o aggiornamento
            if ($add_record) {
                $this->aggiungiRecord($conto1, $conto2, $parti_conto, $record, $database);
            }

            if ($update_record) {
                $this->aggiornaRecord($conto2, $parti_conto, $record, $database);
            }

            return true;
        } catch (\Exception $e) {
            // Registra l'errore in un log
            error_log('Errore durante l\'importazione del piano dei conti: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Restituisce un esempio di file CSV per l'importazione.
     *
     * @return array
     */
    public static function getExample()
    {
        return [
            ['Sezione', 'Conto', 'Descrizione', 'Direzione'],
            ['Economico', '600.000010', 'Costi merci c/acquisto di rivendita', 'uscita'],
            ['Patrimoniale', '110.000010', 'Riepilogativo clienti', ''],
        ];
    }

    /**
     * Analizza il numero di conto e lo divide in parti.
     *
     * @param array $record Record da importare
     *
     * @return array|null Array con le parti del conto o null se il formato non è valido
     */
    protected function parseNumeroConto($record)
    {
        if (empty($record['numero']) || !str_contains((string) $record['numero'], '.')) {
            return null;
        }

        $numero = explode('.', (string) $record['numero']);
        if (count($numero) < 2) {
            return null;
        }

        return [
            'codice_conto2' => $numero[0],
            'codice_conto3' => $numero[1],
        ];
    }

    /**
     * Trova il conto di primo livello (sezione) in base alla descrizione.
     *
     * @param array  $record   Record da importare
     * @param object $database Connessione al database
     *
     * @return array|null
     */
    protected function trovaConto1($record, $database)
    {
        if (empty($record['idpianodeiconti1'])) {
            return null;
        }

        $result = $database->fetchOne('SELECT id FROM co_pianodeiconti1 WHERE LOWER(descrizione)=LOWER('.prepare($record['idpianodeiconti1']).')');

        return !empty($result) ? $result : null;
    }

    /**
     * Trova il conto di secondo livello in base al numero.
     *
     * @param string $codice_conto2 Codice del conto di secondo livello
     * @param object $database      Connessione al database
     *
     * @return array|null
     */
    protected function trovaConto2($codice_conto2, $database)
    {
        if (empty($codice_conto2)) {
            return null;
        }

        $result = $database->fetchOne('SELECT id FROM co_pianodeiconti2 WHERE numero='.prepare($codice_conto2));

        return !empty($result) ? $result : null;
    }

    /**
     * Trova il conto di terzo livello in base al numero e al conto2.
     *
     * @param string $codice_conto3    Codice del conto di terzo livello
     * @param int    $idpianodeiconti2 ID del conto di secondo livello
     * @param object $database         Connessione al database
     *
     * @return array|null
     */
    protected function trovaConto3($codice_conto3, $idpianodeiconti2, $database)
    {
        if (empty($codice_conto3) || empty($idpianodeiconti2)) {
            return null;
        }

        $result = $database->fetchOne('SELECT id FROM co_pianodeiconti3 WHERE numero='.prepare($codice_conto3).' AND idpianodeiconti2='.prepare($idpianodeiconti2));

        return !empty($result) ? $result : null;
    }

    /**
     * Aggiunge un nuovo record al piano dei conti.
     *
     * @param array      $conto1      Conto di primo livello
     * @param array|null $conto2      Conto di secondo livello
     * @param array      $parti_conto Parti del numero di conto
     * @param array      $record      Record da importare
     * @param object     $database    Connessione al database
     */
    protected function aggiungiRecord($conto1, $conto2, $parti_conto, $record, $database)
    {
        // Aggiungi conto di secondo livello se non esiste
        if (empty($conto2) && empty($parti_conto['codice_conto3'])) {
            $database->insert('co_pianodeiconti2', [
                'numero' => $parti_conto['codice_conto2'],
                'descrizione' => $record['descrizione'],
                'idpianodeiconti1' => $conto1['id'],
                'dir' => $record['dir'],
            ]);
        }
        // Aggiungi conto di terzo livello se non esiste
        elseif (!empty($conto2) && !empty($parti_conto['codice_conto3'])) {
            $conto3 = $this->trovaConto3($parti_conto['codice_conto3'], $conto2['id'], $database);

            if (empty($conto3)) {
                $database->insert('co_pianodeiconti3', [
                    'numero' => $parti_conto['codice_conto3'],
                    'descrizione' => $record['descrizione'],
                    'idpianodeiconti2' => $conto2['id'],
                    'dir' => $record['dir'],
                ]);
            }
        }
    }

    /**
     * Aggiorna un record esistente nel piano dei conti.
     *
     * @param array|null $conto2      Conto di secondo livello
     * @param array      $parti_conto Parti del numero di conto
     * @param array      $record      Record da importare
     * @param object     $database    Connessione al database
     */
    protected function aggiornaRecord($conto2, $parti_conto, $record, $database)
    {
        // Aggiorna conto di secondo livello
        if (!empty($conto2) && empty($parti_conto['codice_conto3'])) {
            $database->update('co_pianodeiconti2', [
                'descrizione' => $record['descrizione'],
            ], [
                'id' => $conto2['id'],
            ]);
        }
        // Aggiorna conto di terzo livello
        elseif (!empty($conto2) && !empty($parti_conto['codice_conto3'])) {
            $conto3 = $this->trovaConto3($parti_conto['codice_conto3'], $conto2['id'], $database);

            if (!empty($conto3)) {
                $database->update('co_pianodeiconti3', [
                    'descrizione' => $record['descrizione'],
                ], [
                    'id' => $conto3['id'],
                ]);
            }
        }
    }
}
