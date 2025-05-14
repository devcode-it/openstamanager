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

namespace Modules\Interventi\Import;

use Importer\CSVImporter;
use Modules\Anagrafiche\Anagrafica;
use Modules\Anagrafiche\Tipo as TipoAnagrafica;
use Modules\Impianti\Impianto;
use Modules\Interventi\Components\Sessione;
use Modules\Interventi\Intervento;
use Modules\Interventi\Stato;
use Modules\TipiIntervento\Tipo as TipoIntervento;

/**
 * Struttura per la gestione delle operazioni di importazione (da CSV) degli Interventi.
 * Versione ottimizzata con migliore gestione degli errori e validazione dei dati.
 *
 * @since 2.4.52
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
                'required' => true,
            ],
            [
                'field' => 'partita_iva',
                'label' => 'Partita IVA cliente',
                'required' => false, // Almeno uno tra partita IVA e codice fiscale deve essere presente
            ],
            [
                'field' => 'codice_fiscale',
                'label' => 'Codice Fiscale cliente',
                'required' => false, // Almeno uno tra partita IVA e codice fiscale deve essere presente
            ],
            [
                'field' => 'data',
                'label' => 'Data',
                'required' => true,
            ],
            [
                'field' => 'data_richiesta',
                'label' => 'Data richiesta',
                'required' => true,
            ],
            [
                'field' => 'ora_inizio',
                'label' => 'Ora inizio',
                'required' => true,
            ],
            [
                'field' => 'ora_fine',
                'label' => 'Ora fine',
            ],
            [
                'field' => 'tecnico',
                'label' => 'Tecnico',
                'required' => true,
            ],
            [
                'field' => 'tipo',
                'label' => 'Tipo',
            ],
            [
                'field' => 'note',
                'label' => 'Note',
            ],
            [
                'field' => 'impianto',
                'label' => 'Impianto',
            ],
            [
                'field' => 'richiesta',
                'label' => 'Richiesta',
                'required' => true,
            ],
            [
                'field' => 'descrizione',
                'label' => 'Descrizione',
            ],
            [
                'field' => 'stato',
                'label' => 'Stato',
            ],
        ];
    }

    /**
     * Procedura di inizializzazione per l'importazione.
     * Può essere utilizzata per preparare il database prima dell'importazione.
     *
     * @return void
     */
    public function init()
    {
        // Nessuna operazione di inizializzazione necessaria per gli interventi
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
            $primary_key = $this->getPrimaryKey();

            // Validazione dei campi obbligatori
            if (empty($record['codice']) || empty($record['data']) || empty($record['data_richiesta']) ||
                empty($record['ora_inizio']) || empty($record['tecnico']) || empty($record['richiesta'])) {
                return false;
            }

            // Verifica che almeno uno tra partita IVA e codice fiscale sia presente
            if (empty($record['partita_iva']) && empty($record['codice_fiscale'])) {
                return false;
            }

            // Ricerca dell'anagrafica cliente
            $anagrafica = $this->trovaAnagrafica($record);
            if (empty($anagrafica)) {
                return false; // Non è possibile procedere senza un'anagrafica cliente
            }

            // Ricerca dell'impianto se specificato
            $impianto = null;
            if (!empty($record['impianto'])) {
                $impianto = $this->trovaImpianto($record['impianto']);
            }

            // Ricerca dell'intervento esistente
            $intervento = null;
            if (!empty($primary_key) && !empty($record[$primary_key])) {
                $intervento = Intervento::where($primary_key, $record[$primary_key])->first();
            }

            // Controllo se creare o aggiornare il record
            if (($intervento && !$update_record) || (!$intervento && !$add_record)) {
                return null;
            }

            // Trova o crea il tipo di intervento
            $tipo = $this->trovaTipoIntervento($record);

            // Trova o crea lo stato dell'intervento
            $stato = $this->trovaStatoIntervento($record);

            // Crea o aggiorna l'intervento
            if (empty($intervento)) {
                $intervento = Intervento::build($anagrafica, $tipo, $stato, $record['data_richiesta']);
            } else {
                // Aggiorna i campi dell'intervento esistente
                $intervento->idtipointervento = $tipo->id;
                $intervento->idstatointervento = $stato->id;
                $intervento->data_richiesta = $record['data_richiesta'];
            }

            // Aggiorna i campi dell'intervento
            $this->aggiornaIntervento($intervento, $record);

            // Collega l'impianto all'intervento se specificato
            if (!empty($impianto)) {
                $this->collegaImpianto($intervento, $impianto);
            }

            // Salva l'intervento
            $intervento->save();

            // Crea la sessione di lavoro
            $this->creaSessione($intervento, $record);

            return true;
        } catch (\Exception $e) {
            // Registra l'errore in un log
            error_log('Errore durante l\'importazione dell\'intervento: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Trova l'anagrafica cliente in base alla partita IVA o al codice fiscale.
     *
     * @param array $record Record da processare
     * @return Anagrafica|null Anagrafica trovata o null se non trovata
     */
    protected function trovaAnagrafica($record)
    {
        $anagrafica = null;

        if (!empty($record['partita_iva'])) {
            $anagrafica = Anagrafica::where('piva', '=', $record['partita_iva'])->first();
        }

        if (empty($anagrafica) && !empty($record['codice_fiscale'])) {
            $anagrafica = Anagrafica::where('codice_fiscale', '=', $record['codice_fiscale'])->first();
        }

        return $anagrafica;
    }

    /**
     * Trova l'impianto in base alla matricola.
     *
     * @param string $matricola Matricola dell'impianto
     * @return Impianto|null Impianto trovato o null se non trovato
     */
    protected function trovaImpianto($matricola)
    {
        if (empty($matricola)) {
            return null;
        }

        return Impianto::where('matricola', $matricola)->first();
    }

    /**
     * Trova o crea il tipo di intervento.
     *
     * @param array $record Record da processare
     * @return TipoIntervento Tipo di intervento
     */
    protected function trovaTipoIntervento($record)
    {
        if (empty($record['tipo'])) {
            return TipoIntervento::where('codice', 'GEN')->first();
        }

        return TipoIntervento::where('codice', $record['tipo'])->first();
    }

    /**
     * Trova o crea lo stato dell'intervento.
     *
     * @param array $record Record da processare
     * @return Stato Stato dell'intervento
     */
    protected function trovaStatoIntervento($record)
    {
        if (empty($record['stato'])) {
            return Stato::where('name', 'Completato')->first();
        }

        return Stato::where('name', $record['stato'])->first();
    }

    /**
     * Aggiorna i campi dell'intervento.
     *
     * @param Intervento $intervento Intervento da aggiornare
     * @param array $record Record da processare
     * @return void
     */
    protected function aggiornaIntervento($intervento, $record)
    {
        // Aggiorna i campi dell'intervento
        $intervento->data_richiesta = $record['data_richiesta'];
        $intervento->richiesta = $record['richiesta'];

        // Inserisce la descrizione se presente
        if (!empty($record['descrizione'])) {
            $intervento->descrizione = $record['descrizione'];
        }

        // Inserisce le note se presenti
        if (!empty($record['note'])) {
            $intervento->informazioniaggiuntive = $record['note'];
        }
    }

    /**
     * Collega un impianto all'intervento.
     *
     * @param Intervento $intervento Intervento da collegare
     * @param Impianto $impianto Impianto da collegare
     * @return void
     */
    protected function collegaImpianto($intervento, $impianto)
    {
        $database = database();

        // Verifica se l'impianto è già collegato all'intervento
        $collegamento = $database->table('my_impianti_interventi')
            ->where('idimpianto', $impianto->id)
            ->where('idintervento', $intervento->id)
            ->first();

        if (empty($collegamento)) {
            // Collega l'impianto all'intervento
            $database->query('INSERT INTO my_impianti_interventi(idimpianto, idintervento) VALUES('.prepare($impianto->id).', '.prepare($intervento->id).')');
        }
    }

    /**
     * Crea una sessione di lavoro per l'intervento.
     *
     * @param Intervento $intervento Intervento associato
     * @param array $record Record da processare
     * @return Sessione|null Sessione creata o null se non creata
     */
    protected function creaSessione($intervento, $record)
    {
        if (empty($record['data']) || empty($record['ora_inizio']) || empty($record['tecnico'])) {
            return null;
        }

        try {
            $database = database();

            // Calcola l'orario di inizio e fine
            $inizio = date('Y-m-d H:i', strtotime($record['data'].' '.$record['ora_inizio']));
            $fine = !empty($record['ora_fine']) ? date('Y-m-d H:i', strtotime($record['data'].' '.$record['ora_fine'])) : null;

            // Trova il tecnico
            $anagrafica_t = Anagrafica::where('ragione_sociale', $record['tecnico'])->first();
            if (empty($anagrafica_t)) {
                return null;
            }

            // Verifica se il tecnico ha il tipo "Tecnico"
            $tipo = $database->fetchOne('SELECT `idtipoanagrafica` FROM `an_tipianagrafiche_anagrafiche` WHERE `idanagrafica` = '.prepare($anagrafica_t->id));
            $tecnico_tipo = TipoAnagrafica::where('name', 'Tecnico')->first();

            if ($tipo == $tecnico_tipo->id) {
                $anagrafica_t->tipo = $tecnico_tipo;
            }

            // Crea la sessione
            $sessione = Sessione::build($intervento, $anagrafica_t, $inizio, $fine);
            $sessione->save();

            return $sessione;
        } catch (\Exception $e) {
            // Registra l'errore ma continua con l'importazione
            error_log('Errore durante la creazione della sessione: ' . $e->getMessage());
            return null;
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
            ['Codice', 'Partita IVA Cliente', 'Codice Fiscale Cliente', 'Data', 'Data richiesta', 'Ora inizio', 'Ora fine', 'Tecnico', 'Tipo', 'Note', 'Impianto', 'Richiesta', 'Descrizione', 'Stato'],
            ['00001/2024', '123456789', '123456789', '07/11/2024', '03/11/2025', '8:30', '9:30', 'Stefano Bianchi', '', '', '12345-85A22', 'Manutenzione ordinaria', 'eseguito intervento di manutenzione', 'Da programmare'],
            ['0002/2024', '123456789', '123456789', '08/11/2024', '04/11/2025', '11:20', '', 'Stefano Bianchi', '', '', '12345-85B23', 'Manutenzione ordinaria', 'eseguito intervento di manutenzione', ''],
        ];
    }
}
