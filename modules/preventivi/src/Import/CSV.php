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

namespace Modules\Preventivi\Import;

use Carbon\Carbon;
use Importer\CSVImporter;
use Modules\Anagrafiche\Anagrafica;
use Modules\Anagrafiche\Tipo as TipoAnagrafica;
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Preventivi\Components\Articolo;
use Modules\Preventivi\Preventivo;
use Modules\Preventivi\Stato;
use Modules\TipiIntervento\Tipo as TipoSessione;

/**
 * Struttura per la gestione delle operazioni di importazione (da CSV) dei Preventivi.
 *
 * @since 2.4.44
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
                'label' => 'Numero',
                'primary_key' => true,
                'required' => true,
            ],
            [
                'field' => 'nome',
                'label' => 'Nome preventivo',
                'required' => true,
            ],
            [
                'field' => 'descrizione',
                'label' => 'Descrizione preventivo',
            ],
            [
                'field' => 'ragione_sociale',
                'label' => 'Cliente',
                'required' => true,
            ],
            [
                'field' => 'idtipointervento',
                'label' => 'Tipo attività',
            ],
            [
                'field' => 'data_bozza',
                'label' => 'Data',
                'required' => true,
            ],
            [
                'field' => 'codice',
                'label' => 'Codice articolo',
                'required' => true,
            ],
            [
                'field' => 'qta',
                'label' => 'Quantità riga',
                'required' => true,
            ],
            [
                'field' => 'data_evasione',
                'label' => 'Data prevista evasione riga',
            ],
            [
                'field' => 'prezzo_unitario',
                'label' => 'Prezzo unitario riga',
                'required' => true,
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
            if (empty($record['numero']) || empty($record['nome']) || empty($record['ragione_sociale'])
                || empty($record['data_bozza']) || empty($record['codice']) || empty($record['qta'])
                || empty($record['prezzo_unitario'])) {
                return false;
            }

            // Ricerca del preventivo esistente
            $preventivo = $this->trovaPreventivo($record, $database);

            // Controllo se creare o aggiornare il record
            if (($preventivo && !$update_record) || (!$preventivo && !$add_record)) {
                return null;
            }

            // Creazione o aggiornamento del preventivo
            if (empty($preventivo)) {
                $preventivo = $this->creaPreventivo($record);
                if (empty($preventivo)) {
                    return false;
                }
            }

            // Aggiunta dell'articolo al preventivo
            $this->aggiungiArticoloAlPreventivo($preventivo, $record);

            return true;
        } catch (\Exception $e) {
            // Registra l'errore in un log
            error_log('Errore durante l\'importazione del preventivo: '.$e->getMessage());

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
            ['Numero', 'Nome Preventivo', 'Descrizione Preventivo', 'Cliente', 'Tipo Attività', 'Data', 'Codice Articolo', 'Quantità riga', 'Data prevista evasione riga', 'Prezzo unitario riga'],
            ['15', 'Preventivo Materiali', 'Preventivo iniziale', 'Rossi', 'Generico', '27/04/2024', '001', '2', '30/04/2024', '50'],
            ['15', 'Preventivo Materiali', 'Preventivo iniziale', 'Rossi', 'Generico', '27/04/2024', '043', '1', '10/05/2024', '100'],
        ];
    }

    /**
     * Trova il preventivo esistente in base al numero.
     *
     * @param array  $record   Record da importare
     * @param object $database Connessione al database
     *
     * @return Preventivo|null
     */
    protected function trovaPreventivo($record, $database)
    {
        if (empty($record['numero'])) {
            return null;
        }

        $id_preventivo = $database->fetchOne('SELECT id FROM `co_preventivi` WHERE `numero`='.prepare($record['numero']));

        return !empty($id_preventivo) ? Preventivo::find($id_preventivo['id']) : null;
    }

    /**
     * Crea un nuovo preventivo.
     *
     * @param array $record Record da importare
     *
     * @return Preventivo|null
     */
    protected function creaPreventivo($record)
    {
        try {
            // Ricerca o creazione dell'anagrafica cliente
            $anagrafica = $this->trovaOCreaAnagrafica($record);
            if (empty($anagrafica)) {
                return null;
            }

            // Ricerca del tipo di intervento
            $tipo = $this->trovaTipoIntervento($record);

            // Creazione del preventivo
            $preventivo = Preventivo::build($anagrafica, $tipo, $record['nome'], $this->parseData($record['data_bozza']), 0);
            $preventivo->numero = $record['numero'];
            $preventivo->idstato = Stato::where('name', 'Bozza')->first()->id;
            $preventivo->descrizione = $record['descrizione'] ?? '';
            $preventivo->save();

            return $preventivo;
        } catch (\Exception $e) {
            error_log('Errore durante la creazione del preventivo: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Trova o crea l'anagrafica cliente.
     *
     * @param array $record Record da importare
     *
     * @return Anagrafica|null
     */
    protected function trovaOCreaAnagrafica($record)
    {
        if (empty($record['ragione_sociale'])) {
            return null;
        }

        $anagrafica = Anagrafica::where('ragione_sociale', $record['ragione_sociale'])->first();

        if (empty($anagrafica)) {
            $anagrafica = Anagrafica::build($record['ragione_sociale']);
            $tipo_cliente = TipoAnagrafica::where('name', 'Cliente')->first()->id;
            $anagrafica->tipologie = [$tipo_cliente];
            $anagrafica->save();
        }

        return $anagrafica;
    }

    /**
     * Trova il tipo di intervento.
     *
     * @param array $record Record da importare
     *
     * @return TipoSessione
     */
    protected function trovaTipoIntervento($record)
    {
        return TipoSessione::find($record['idtipointervento']) ?: TipoSessione::where('codice', 'GEN')->first();
    }

    /**
     * Aggiunge un articolo al preventivo.
     *
     * @param Preventivo $preventivo Preventivo
     * @param array      $record     Record da importare
     *
     * @return bool
     */
    protected function aggiungiArticoloAlPreventivo($preventivo, $record)
    {
        try {
            // Individuazione articolo
            $articolo_orig = ArticoloOriginale::where('codice', $record['codice'])->first();
            if (empty($articolo_orig)) {
                return false;
            }

            $riga_articolo = Articolo::build($preventivo, $articolo_orig);
            $riga_articolo->um = $articolo_orig->um ?: null;

            // Gestione della data di evasione
            if (!empty($record['data_evasione'])) {
                $riga_articolo->data_evasione = $this->parseData($record['data_evasione']);
            }

            // Gestione dell'IVA
            $anagrafica = $preventivo->anagrafica;
            $idiva = $articolo_orig->idiva_vendita ?: ($anagrafica->idiva_vendite ?: setting('Iva predefinita'));

            $riga_articolo->descrizione = $articolo_orig->getTranslation('title');
            $riga_articolo->setPrezzoUnitario($record['prezzo_unitario'], $idiva);
            $riga_articolo->qta = $record['qta'];

            $riga_articolo->save();

            return true;
        } catch (\Exception $e) {
            error_log('Errore durante l\'aggiunta dell\'articolo al preventivo: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Converte una stringa data in un oggetto Carbon.
     *
     * @param string $data_string Stringa data
     *
     * @return Carbon
     */
    protected function parseData($data_string)
    {
        try {
            return new Carbon($data_string);
        } catch (\Exception $e) {
            // Prova a interpretare formati di data italiani (dd/mm/yyyy)
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $data_string, $matches)) {
                return Carbon::createFromDate($matches[3], $matches[2], $matches[1]);
            }

            // Fallback alla data corrente
            error_log('Errore nel parsing della data: '.$e->getMessage());

            return Carbon::now();
        }
    }
}
