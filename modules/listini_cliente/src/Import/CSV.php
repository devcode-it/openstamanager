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

namespace Modules\ListiniCliente\Import;

use Importer\CSVImporter;
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\ListiniCliente\Articolo;
use Modules\ListiniCliente\Listino;

/**
 * Struttura per la gestione delle operazioni di importazione (da CSV) degli Articoli nei Listini Cliente.
 *
 * Ottimizzata con logging avanzato degli errori e gestione migliorata delle anomalie.
 *
 * @since 2.4.17
 */
class CSV extends CSVImporter
{
    /**
     * Array per memorizzare gli errori specifici per ogni record fallito.
     */
    protected $failed_errors = [];

    /**
     * Contatore per tenere traccia della riga corrente durante l'importazione.
     */
    protected $current_row = 0;

    /**
     * Inizializza l'importazione con logging e pulizia cache.
     */
    public function init()
    {
        parent::init();

        // Pulisce le cache per una nuova importazione
        self::$listini_cache = [];
        self::$articoli_cache = [];
        $this->failed_errors = [];
        $this->current_row = 0;

        $logger = logger();
        $logger->info('Inizializzazione importazione listino cliente', [
            'timestamp' => date('Y-m-d H:i:s'),
            'classe' => static::class
        ]);
    }

    /**
     * Completa l'importazione con logging finale e statistiche.
     */
    public function complete()
    {
        parent::complete();

        $logger = logger();
        $logger->info('Completamento importazione listino cliente', [
            'timestamp' => date('Y-m-d H:i:s'),
            'record_falliti' => count($this->failed_records),
            'listini_in_cache' => count(self::$listini_cache),
            'articoli_in_cache' => count(self::$articoli_cache),
            'righe_processate' => $this->current_row
        ]);

        // Opzionale: pulisce le cache alla fine per liberare memoria
        // self::$listini_cache = [];
        // self::$articoli_cache = [];
    }
    /**
     * Definisce i campi disponibili per l'importazione.
     *
     * Nota: Se il listino specificato non esiste, verrà creato automaticamente.
     *
     * @return array
     */
    public function getAvailableFields()
    {
        return [
            [
                'field' => 'nome_listino',
                'label' => 'Nome listino',
                'required' => true,
            ],
            [
                'field' => 'codice',
                'label' => 'Codice articolo',
                'primary_key' => true,
                'required' => true,
            ],
            [
                'field' => 'data_scadenza',
                'label' => 'Data scadenza',
            ],
            [
                'field' => 'prezzo_unitario',
                'label' => 'Prezzo unitario',
                'required' => true,
            ],
            [
                'field' => 'sconto_percentuale',
                'label' => 'Sconto percentuale',
            ],
        ];
    }

    /**
     * Importa un record nel database con logging avanzato degli errori.
     *
     * @param array $record        Record da importare
     * @param bool  $update_record Se true, aggiorna i record esistenti
     * @param bool  $add_record    Se true, aggiunge nuovi record
     *
     * @return bool|null True se l'importazione è riuscita, false altrimenti, null se l'operazione è stata saltata
     */
    public function import($record, $update_record = true, $add_record = true)
    {
        $this->current_row++;
        $logger = logger();

        try {
            $database = database();

            // Validazione dettagliata dei campi obbligatori
            $validation_errors = $this->validateRecord($record);
            if (!empty($validation_errors)) {
                $error_message = 'Errori di validazione: ' . implode(', ', $validation_errors);
                $this->logError('validation', $error_message, $record);
                return false;
            }

            // Ricerca del listino, creandolo se non esiste
            $listino = $this->trovaOCreaListino($record, $database);
            if (empty($listino)) {
                $error_message = "Impossibile trovare o creare il listino '{$record['nome_listino']}'";
                $this->logError('listino_creation_failed', $error_message, $record);
                return false;
            }

            // Ricerca dell'articolo
            $articolo = $this->trovaArticolo($record, $database);
            if (empty($articolo)) {
                $error_message = "Articolo con codice '{$record['codice']}' non trovato";
                $this->logError('articolo_not_found', $error_message, $record);
                return false;
            }

            // Ricerca dell'articolo nel listino
            $articolo_listino = $this->trovaArticoloListino($articolo['id'], $listino['id']);

            // Controllo se creare o aggiornare il record
            if (($articolo_listino && !$update_record) || (!$articolo_listino && !$add_record)) {
                $logger->info('Importazione saltata per articolo nel listino', [
                    'codice_articolo' => $record['codice'],
                    'nome_listino' => $record['nome_listino'],
                    'riga' => $this->current_row,
                    'motivo' => $articolo_listino ? 'record esistente, aggiornamento disabilitato' : 'nuovo record, inserimento disabilitato'
                ]);
                return null;
            }

            // Creazione o aggiornamento dell'articolo nel listino
            $this->salvaArticoloListino($articolo_listino, $articolo, $listino['id'], $record);

            // Log di successo
            $logger->info('Articolo importato con successo nel listino', [
                'codice_articolo' => $record['codice'],
                'nome_listino' => $record['nome_listino'],
                'riga' => $this->current_row,
                'operazione' => $articolo_listino ? 'aggiornamento' : 'inserimento'
            ]);

            return true;
        } catch (\Exception $e) {
            $error_message = 'Errore durante l\'importazione dell\'articolo nel listino: ' . $e->getMessage();
            $this->logError('exception', $error_message, $record, $e);
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
            ['Nome listino', 'Codice articolo', 'Data scadenza', 'Prezzo unitario', 'Sconto percentuale'],
            ['Listino Clienti VIP', 'OSM-BUDGET', '2024-12-31', '100', '10'],
            ['Listino Promozionale', 'OSM-BUDGET', '2024-07-31', '120', ''],
            ['Listino Nuovo Cliente', 'OSM-BUDGET', '', '95', '5'],
        ];
    }

    /**
     * Valida un record prima dell'importazione.
     *
     * @param array $record Record da validare
     * @return array Array di errori di validazione
     */
    protected function validateRecord($record)
    {
        $errors = [];

        // Validazione campi obbligatori
        if (empty($record['nome_listino'])) {
            $errors[] = 'Nome listino mancante';
        } elseif (strlen($record['nome_listino']) > 255) {
            $errors[] = 'Nome listino troppo lungo (massimo 255 caratteri)';
        }

        if (empty($record['codice'])) {
            $errors[] = 'Codice articolo mancante';
        }

        if (empty($record['prezzo_unitario'])) {
            $errors[] = 'Prezzo unitario mancante';
        }

        // Validazione formato prezzo
        if (!empty($record['prezzo_unitario']) && !is_numeric($record['prezzo_unitario'])) {
            $errors[] = 'Prezzo unitario non valido (deve essere numerico)';
        }

        // Validazione sconto percentuale
        if (!empty($record['sconto_percentuale'])) {
            if (!is_numeric($record['sconto_percentuale'])) {
                $errors[] = 'Sconto percentuale non valido (deve essere numerico)';
            } elseif ($record['sconto_percentuale'] < 0 || $record['sconto_percentuale'] > 100) {
                $errors[] = 'Sconto percentuale non valido (deve essere tra 0 e 100)';
            }
        }

        // Validazione formato data scadenza
        if (!empty($record['data_scadenza'])) {
            $date = \DateTime::createFromFormat('Y-m-d', $record['data_scadenza']);
            if (!$date || $date->format('Y-m-d') !== $record['data_scadenza']) {
                $errors[] = 'Formato data scadenza non valido (utilizzare YYYY-MM-DD)';
            }
        }

        return $errors;
    }

    /**
     * Registra un errore con logging strutturato.
     *
     * @param string $type Tipo di errore
     * @param string $message Messaggio di errore
     * @param array $record Record che ha causato l'errore
     * @param \Exception|null $exception Eccezione opzionale
     */
    protected function logError($type, $message, $record, $exception = null)
    {
        $logger = logger();

        // Aggiungi l'errore all'array per il file CSV delle anomalie
        $this->failed_errors[] = $message;

        // Context per il log strutturato
        $context = [
            'tipo_errore' => $type,
            'riga' => $this->current_row,
            'codice_articolo' => $record['codice'] ?? 'N/A',
            'nome_listino' => $record['nome_listino'] ?? 'N/A',
            'record' => $record
        ];

        if ($exception) {
            $context['exception'] = [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ];
        }

        // Log con livello appropriato
        switch ($type) {
            case 'validation':
                $logger->warning('Errore di validazione durante importazione listino cliente', $context);
                break;
            case 'listino_not_found':
            case 'articolo_not_found':
                $logger->error('Entità non trovata durante importazione listino cliente', $context);
                break;
            case 'listino_creation_failed':
                $logger->error('Impossibile creare il listino durante importazione', $context);
                break;
            case 'exception':
                $logger->error('Eccezione durante importazione listino cliente', $context);
                break;
            default:
                $logger->error('Errore generico durante importazione listino cliente', $context);
        }
    }

    /**
     * Cache per i listini già cercati per migliorare le performance.
     */
    protected static $listini_cache = [];

    /**
     * Cache per gli articoli già cercati per migliorare le performance.
     */
    protected static $articoli_cache = [];

    /**
     * Trova il listino in base al nome con caching per migliorare le performance.
     *
     * @param array  $record   Record da importare
     * @param object $database Connessione al database
     *
     * @return array|null
     */
    protected function trovaListino($record, $database)
    {
        if (empty($record['nome_listino'])) {
            return null;
        }

        $nome_listino = $record['nome_listino'];

        // Controlla la cache prima di fare la query
        if (isset(self::$listini_cache[$nome_listino])) {
            return self::$listini_cache[$nome_listino];
        }

        $result = $database->fetchOne('SELECT id FROM mg_listini WHERE nome = '.prepare($nome_listino));

        // Salva in cache il risultato (anche se null)
        self::$listini_cache[$nome_listino] = !empty($result) ? $result : null;

        return self::$listini_cache[$nome_listino];
    }

    /**
     * Trova l'articolo in base al codice con caching per migliorare le performance.
     *
     * @param array  $record   Record da importare
     * @param object $database Connessione al database
     *
     * @return array|null
     */
    protected function trovaArticolo($record, $database)
    {
        if (empty($record['codice'])) {
            return null;
        }

        $codice = $record['codice'];

        // Controlla la cache prima di fare la query
        if (isset(self::$articoli_cache[$codice])) {
            return self::$articoli_cache[$codice];
        }

        $result = $database->fetchOne('SELECT `id` FROM `mg_articoli` WHERE `codice` = '.prepare($codice));

        // Salva in cache il risultato (anche se null)
        self::$articoli_cache[$codice] = !empty($result) ? $result : null;

        return self::$articoli_cache[$codice];
    }

    /**
     * Trova il listino in base al nome, creandolo se non esiste.
     *
     * @param array  $record   Record da importare
     * @param object $database Connessione al database
     *
     * @return array|null
     */
    protected function trovaOCreaListino($record, $database)
    {
        if (empty($record['nome_listino'])) {
            return null;
        }

        $nome_listino = $record['nome_listino'];

        // Prima prova a trovare il listino esistente
        $listino = $this->trovaListino($record, $database);

        if (!empty($listino)) {
            return $listino;
        }

        // Se non trovato, crea un nuovo listino
        try {
            $logger = logger();

            $nuovo_listino = Listino::build($nome_listino);
            $nuovo_listino->attivo = 1; // Attivo di default
            $nuovo_listino->save();

            // Aggiorna la cache con il nuovo listino
            $listino_data = ['id' => $nuovo_listino->id];
            self::$listini_cache[$nome_listino] = $listino_data;

            $logger->info('Nuovo listino creato durante importazione', [
                'nome_listino' => $nome_listino,
                'id_listino' => $nuovo_listino->id,
                'riga' => $this->current_row
            ]);

            return $listino_data;
        } catch (\Exception $e) {
            $logger = logger();
            $logger->error('Errore durante la creazione del listino', [
                'nome_listino' => $nome_listino,
                'errore' => $e->getMessage(),
                'riga' => $this->current_row
            ]);

            return null;
        }
    }

    /**
     * Trova l'articolo nel listino.
     *
     * @param int $id_articolo ID dell'articolo
     * @param int $id_listino  ID del listino
     *
     * @return Articolo|null
     */
    protected function trovaArticoloListino($id_articolo, $id_listino)
    {
        return Articolo::where('id_articolo', $id_articolo)->where('id_listino', $id_listino)->first();
    }

    /**
     * Salva l'articolo nel listino.
     *
     * @param Articolo|null $articolo_listino   Articolo nel listino esistente
     * @param array         $articolo_originale Articolo originale
     * @param int           $id_listino         ID del listino
     * @param array         $record             Record da importare
     */
    protected function salvaArticoloListino($articolo_listino, $articolo_originale, $id_listino, $record)
    {
        $prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');
        $articolo_obj = ArticoloOriginale::find($articolo_originale['id']);
        $prezzo_unitario = $prezzi_ivati ? $articolo_obj->prezzo_vendita_ivato : $articolo_obj->prezzo_vendita;

        if (!$articolo_listino) {
            $articolo_listino = Articolo::build($articolo_obj, $id_listino);
        }

        $articolo_listino->data_scadenza = $record['data_scadenza'] ?: null;
        $articolo_listino->setPrezzoUnitario($record['prezzo_unitario'] ?: $prezzo_unitario);
        $articolo_listino->sconto_percentuale = $record['sconto_percentuale'] ?: 0;
        $articolo_listino->save();
    }

    /**
     * Salva i record falliti con gli errori specifici in un file CSV.
     *
     * @param string $filepath Percorso del file in cui salvare i record falliti
     * @return string Percorso del file salvato
     */
    public function saveFailedRecordsWithErrors($filepath)
    {
        if (empty($this->failed_rows)) {
            return '';
        }

        // Crea la directory se non esiste
        $dir = dirname($filepath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $file = fopen($filepath, 'w');
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

        // Scrivi l'intestazione con colonna errore aggiuntiva
        $header = $this->getHeader();
        $header[] = 'Errore';
        fputcsv($file, $header, ';');

        // Scrivi le righe fallite con i relativi errori
        foreach ($this->failed_rows as $index => $row) {
            $error_message = $this->failed_errors[$index] ?? 'Errore sconosciuto';
            $row[] = $error_message;
            fputcsv($file, $row, ';');
        }

        fclose($file);

        // Log dell'operazione
        $logger = logger();
        $logger->info('File anomalie listino cliente creato', [
            'filepath' => $filepath,
            'record_falliti' => count($this->failed_rows),
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        return $filepath;
    }

    /**
     * Restituisce gli errori specifici per i record falliti.
     *
     * @return array
     */
    public function getFailedErrors()
    {
        return $this->failed_errors;
    }

    /**
     * Override del metodo importRows per migliorare il tracking degli errori.
     */
    public function importRows($offset, $length, $update_record = true, $add_record = true)
    {
        $this->current_row = $offset;
        $logger = logger();

        $logger->info('Inizio importazione batch listino cliente', [
            'offset' => $offset,
            'length' => $length,
            'update_record' => $update_record,
            'add_record' => $add_record
        ]);

        $result = parent::importRows($offset, $length, $update_record, $add_record);

        $logger->info('Completamento importazione batch listino cliente', [
            'offset' => $offset,
            'importati' => $result['imported'],
            'falliti' => $result['failed'],
            'totali' => $result['total']
        ]);

        return $result;
    }
}
