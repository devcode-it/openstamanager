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

/**
 * Struttura per la gestione delle operazioni di importazione (da CSV) degli Articoli.
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
     * Importa un record nel database.
     *
     * @param array $record Record da importare
     * @param bool $update_record Se true, aggiorna i record esistenti
     * @param bool $add_record Se true, aggiunge nuovi record
     * @return bool|null True se l'importazione è riuscita, false altrimenti, null se l'operazione è stata saltata
     */
    public function import($record, $update_record = true, $add_record = true)
    {
        try {
            $database = database();

            // Validazione dei campi obbligatori
            if (empty($record['nome_listino']) || empty($record['codice'])) {
                return false;
            }

            // Ricerca del listino e dell'articolo
            $listino = $this->trovaListino($record, $database);
            if (empty($listino)) {
                return false;
            }

            $articolo = $this->trovaArticolo($record, $database);
            if (empty($articolo)) {
                return false;
            }

            // Ricerca dell'articolo nel listino
            $articolo_listino = $this->trovaArticoloListino($articolo['id'], $listino['id']);

            // Controllo se creare o aggiornare il record
            if (($articolo_listino && !$update_record) || (!$articolo_listino && !$add_record)) {
                return null;
            }

            // Creazione o aggiornamento dell'articolo nel listino
            $this->salvaArticoloListino($articolo_listino, $articolo, $listino['id'], $record);

            return true;
        } catch (\Exception $e) {
            // Registra l'errore in un log
            error_log('Errore durante l\'importazione dell\'articolo nel listino: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Trova il listino in base al nome.
     *
     * @param array $record Record da importare
     * @param object $database Connessione al database
     * @return array|null
     */
    protected function trovaListino($record, $database)
    {
        if (empty($record['nome_listino'])) {
            return null;
        }

        $result = $database->fetchOne('SELECT id FROM mg_listini WHERE nome = '.prepare($record['nome_listino']));
        return !empty($result) ? $result : null;
    }

    /**
     * Trova l'articolo in base al codice.
     *
     * @param array $record Record da importare
     * @param object $database Connessione al database
     * @return array|null
     */
    protected function trovaArticolo($record, $database)
    {
        if (empty($record['codice'])) {
            return null;
        }

        $result = $database->fetchOne('SELECT `id` FROM `mg_articoli` WHERE `codice` = '.prepare($record['codice']));
        return !empty($result) ? $result : null;
    }

    /**
     * Trova l'articolo nel listino.
     *
     * @param int $id_articolo ID dell'articolo
     * @param int $id_listino ID del listino
     * @return Articolo|null
     */
    protected function trovaArticoloListino($id_articolo, $id_listino)
    {
        return Articolo::where('id_articolo', $id_articolo)->where('id_listino', $id_listino)->first();
    }

    /**
     * Salva l'articolo nel listino.
     *
     * @param Articolo|null $articolo_listino Articolo nel listino esistente
     * @param array $articolo_originale Articolo originale
     * @param int $id_listino ID del listino
     * @param array $record Record da importare
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
     * Restituisce un esempio di file CSV per l'importazione.
     *
     * @return array
     */
    public static function getExample()
    {
        return [
            ['Nome listino', 'Codice articolo', 'Data scadenza', 'Prezzo unitario', 'Sconto percentuale'],
            ['Listino 1', '1234', '2024-12-31', '100', '10'],
            ['Listino 1', '5678', '', '100', '50'],
            ['Listino 1', '9101', '2024-07-31', '100', ''],
        ];
    }
}
