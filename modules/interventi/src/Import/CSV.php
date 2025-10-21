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
use Modules\Interventi\Components\Riga;
use Modules\Interventi\Components\Sessione;
use Modules\Interventi\Intervento;
use Modules\Interventi\Stato;
use Modules\Iva\Aliquota;
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
                'required' => false,
            ],
            [
                'field' => 'codice_fiscale',
                'label' => 'Codice Fiscale cliente',
                'required' => false, 
            ],
            [
                'field' => 'ragione_sociale',
                'label' => 'Ragione Sociale cliente',
                'required' => false,
            ],
            [
                'field' => 'data',
                'label' => 'Data',
                'required' => true,
            ],
            [
                'field' => 'data_richiesta',
                'label' => 'Data richiesta',
            ],
            [
                'field' => 'ora_inizio',
                'label' => 'Ora inizio',
            ],
            [
                'field' => 'ora_fine',
                'label' => 'Ora fine',
            ],
            [
                'field' => 'tecnico',
                'label' => 'Tecnico',
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
            [
                'field' => 'descrizione_riga',
                'label' => 'Descrizione riga',
                'required' => false,
            ],
            [
                'field' => 'imponibile',
                'label' => 'Imponibile riga',
                'required' => false,
            ],
            [
                'field' => 'aliquota_iva',
                'label' => 'Aliquota IVA (%)',
                'required' => false,
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
            $primary_key = $this->getPrimaryKey();

            // Validazione dei campi obbligatori
            if (empty($record['codice']) || empty($record['data']) || empty($record['richiesta'])) {
                error_log('Campi obbligatori mancanti - Codice: '.($record['codice'] ?? 'vuoto').', Data: '.($record['data'] ?? 'vuoto').', Richiesta: '.($record['richiesta'] ?? 'vuoto'));

                return false;
            }

            // Validazione formato data
            $data_richiesta = $record['data_richiesta'] ?? $record['data'];
            if (!$this->validaFormatoData($data_richiesta)) {
                error_log('Formato data non valido: '.$data_richiesta);

                return false;
            }


            // Ricerca dell'anagrafica cliente
            $anagrafica = $this->trovaAnagrafica($record);
            if (empty($anagrafica)) {
                error_log('Impossibile trovare o creare anagrafica per il record: '.json_encode($record));
                return false; 
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
            if (empty($tipo)) {
                error_log('Impossibile trovare tipo intervento per il record: '.json_encode($record));

                return false;
            }

            // Trova o crea lo stato dell'intervento
            $stato = $this->trovaStatoIntervento($record);
            if (empty($stato)) {
                error_log('Impossibile trovare stato intervento per il record: '.json_encode($record));

                return false;
            }

            // Crea o aggiorna l'intervento
            if (empty($intervento)) {
                $intervento = Intervento::build($anagrafica, $tipo, $stato, $record['data_richiesta']);

                // Imposta il codice personalizzato se diverso da quello generato automaticamente
                if ($intervento->codice != $record['codice']) {
                    $intervento->codice = $record['codice'];
                }
            } else {
                // Aggiorna i campi dell'intervento esistente
                $intervento->idtipointervento = $tipo->id;
                $intervento->idstatointervento = $stato->id;
                $intervento->data_richiesta = $record['data_richiesta'];
            }

            // Aggiorna i campi dell'intervento
            $this->aggiornaIntervento($intervento, $record);

            // Imposta la sede di partenza a 0 (sede legale di default)
            $intervento->idsede_partenza = 0;

            // Collega l'impianto all'intervento se specificato
            if (!empty($impianto)) {
                $this->collegaImpianto($intervento, $impianto);
            }

            // Salva l'intervento
            $intervento->save();

            // Crea la sessione di lavoro
            $this->creaSessione($intervento, $record);

            // Crea la riga dell'intervento se specificata
            $this->creaRigaIntervento($intervento, $record);

            return true;
        } catch (\Exception $e) {
            // Registra l'errore in un log con più dettagli
            error_log('Errore durante l\'importazione dell\'intervento: '.$e->getMessage().' - Record: '.json_encode($record).' - Stack trace: '.$e->getTraceAsString());

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
            ['Codice', 'Partita IVA Cliente', 'Codice Fiscale Cliente', 'Ragione Sociale Cliente', 'Data', 'Data richiesta', 'Ora inizio', 'Ora fine', 'Tecnico', 'Tipo', 'Note', 'Impianto', 'Richiesta', 'Descrizione', 'Stato', 'Descrizione riga', 'Imponibile riga', 'Aliquota IVA (%)'],
            ['0001/2025', '12345678901', 'RSSMRA80A01H501Z', 'Acme S.r.l.', '15/01/2025', '10/01/2025', '08:30', '09:30', 'Mario Rossi', 'GEN', 'Intervento programmato', 'IMP001', 'Manutenzione ordinaria', 'Eseguito intervento di manutenzione programmata', 'Completato', 'Servizio di manutenzione', '100.00', '22'],
            ['0002/2025', '98765432109', 'VRDLGU75B15F205X', 'Beta Company S.p.A.', '20/01/2025', '18/01/2025', '10:00', '12:00', 'Mario Rossi', 'GEN', 'Cliente molto soddisfatto', 'IMP002', 'Controllo impianto', 'Eseguito controllo periodico impianto', 'Completato', 'Controllo impianto', '150.00', '22'],
            ['0003/2025', '11223344556', 'BNCNNA85C20G273Y', 'Gamma S.n.c.', '25/01/2025', '25/01/2025', '14:00', '15:30', 'Mario Rossi', 'GEN', 'Intervento urgente risolto', 'IMP003', 'Riparazione guasto', 'Riparato guasto elettrico', 'Completato', 'Riparazione urgente', '200.00', '22'],
        ];
    }

    /**
     * Trova l'anagrafica cliente in base alla partita IVA, al codice fiscale o alla ragione sociale.
     * Se non trova nessuna corrispondenza, crea una nuova anagrafica.
     *
     * @param array $record Record da processare
     *
     * @return Anagrafica|null Anagrafica trovata o creata, null in caso di errore
     */
    protected function trovaAnagrafica($record)
    {
        $anagrafica = null;

        // Ricerca per partita IVA
        if (!empty($record['partita_iva'])) {
            $anagrafica = Anagrafica::where('piva', '=', $record['partita_iva'])->first();
        }

        // Ricerca per codice fiscale se non trovata con partita IVA
        if (empty($anagrafica) && !empty($record['codice_fiscale'])) {
            $anagrafica = Anagrafica::where('codice_fiscale', '=', $record['codice_fiscale'])->first();
        }

        // Ricerca per ragione sociale se non trovata con partita IVA o codice fiscale
        if (empty($anagrafica) && !empty($record['ragione_sociale'])) {
            $anagrafica = Anagrafica::where('ragione_sociale', '=', $record['ragione_sociale'])->first();
        }

        // Se non trova nessuna anagrafica, ne crea una nuova
        if (empty($anagrafica)) {
            $anagrafica = $this->creaAnagrafica($record);
        }

        return $anagrafica;
    }

    /**
     * Trova l'impianto in base alla matricola.
     *
     * @param string $matricola Matricola dell'impianto
     *
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
     *
     * @return TipoIntervento Tipo di intervento
     */
    protected function trovaTipoIntervento($record)
    {
        $tipo = null;

        if (!empty($record['tipo'])) {
            $tipo = TipoIntervento::where('codice', $record['tipo'])->first();
        }

        // Se non trovato, cerca il tipo "GEN" (Generico)
        if (empty($tipo)) {
            $tipo = TipoIntervento::where('codice', 'GEN')->first();
        }

        // Se ancora non trovato, prende il primo tipo disponibile
        if (empty($tipo)) {
            $tipo = TipoIntervento::first();
        }

        if (empty($tipo)) {
            error_log('Nessun tipo intervento trovato nel database');
        }

        return $tipo;
    }

    /**
     * Trova o crea lo stato dell'intervento.
     *
     * @param array $record Record da processare
     *
     * @return Stato Stato dell'intervento
     */
    protected function trovaStatoIntervento($record)
    {
        $stato = null;

        if (!empty($record['stato'])) {
            $stato = Stato::where('name', $record['stato'])->first();
        }

        // Se non trovato, cerca lo stato "Completato"
        if (empty($stato)) {
            $stato = Stato::where('name', 'Completato')->first();
        }

        // Se ancora non trovato, cerca lo stato "Da programmare"
        if (empty($stato)) {
            $stato = Stato::where('name', 'Da programmare')->first();
        }

        // Se ancora non trovato, prende il primo stato disponibile
        if (empty($stato)) {
            $stato = Stato::first();
        }

        if (empty($stato)) {
            error_log('Nessuno stato intervento trovato nel database');
        }

        return $stato;
    }

    /**
     * Aggiorna i campi dell'intervento.
     *
     * @param Intervento $intervento Intervento da aggiornare
     * @param array      $record     Record da processare
     *
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
     * @param Impianto   $impianto   Impianto da collegare
     *
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
     * @param array      $record     Record da processare
     *
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
            error_log('Errore durante la creazione della sessione: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Crea una nuova anagrafica in base ai dati del record.
     *
     * @param array $record Record da processare
     *
     * @return Anagrafica|null Anagrafica creata o null in caso di errore
     */
    protected function creaAnagrafica($record)
    {
        try {
            // Determina la ragione sociale da utilizzare
            $ragione_sociale = '';
            if (!empty($record['ragione_sociale'])) {
                $ragione_sociale = $record['ragione_sociale'];
            } elseif (!empty($record['partita_iva'])) {
                $ragione_sociale = 'Cliente P.IVA '.$record['partita_iva'];
            } elseif (!empty($record['codice_fiscale'])) {
                $ragione_sociale = 'Cliente C.F. '.$record['codice_fiscale'];
            } else {
                $ragione_sociale = 'Cliente importato '.date('Y-m-d H:i:s');
            }

            // Verifica che la ragione sociale non sia vuota
            if (empty($ragione_sociale)) {
                error_log('Impossibile determinare la ragione sociale per il record: '.json_encode($record));

                return null;
            }

            $tipo_cliente = TipoAnagrafica::where('name', 'Cliente')->first();
            $tipologie = !empty($tipo_cliente) ? [$tipo_cliente->id] : [];

            $anagrafica = Anagrafica::build($ragione_sociale, '', '', $tipologie);

            // Imposta partita IVA se presente
            if (!empty($record['partita_iva'])) {
                $anagrafica->piva = $record['partita_iva'];
            }

            // Imposta codice fiscale se presente
            if (!empty($record['codice_fiscale'])) {
                $anagrafica->codice_fiscale = $record['codice_fiscale'];
            }

            // Imposta un telefono fittizio se mancante (richiesto per le anagrafiche)
            if (empty($anagrafica->telefono) && empty($anagrafica->piva)) {
                $anagrafica->telefono = '000000000'; // Telefono fittizio per soddisfare i vincoli
            }

            $anagrafica->save();
            $anagrafica->refresh();

            error_log('Anagrafica creata con successo: ID '.$anagrafica->id.', Ragione sociale: '.$ragione_sociale);

            return $anagrafica;
        } catch (\Exception $e) {
            // Registra l'errore con più dettagli
            error_log('Errore durante la creazione dell\'anagrafica: '.$e->getMessage().' - Record: '.json_encode($record).' - Stack trace: '.$e->getTraceAsString());

            return null;
        }
    }

    /**
     * Trova l'aliquota IVA in base alla percentuale.
     *
     * @param float $percentuale Percentuale dell'aliquota IVA
     *
     * @return Aliquota|null Aliquota trovata o null se non trovata
     */
    protected function trovaAliquotaIva($percentuale)
    {
        if (empty($percentuale)) {
            return Aliquota::find(setting('Iva predefinita'));
        }

        // Cerca l'aliquota IVA per percentuale
        $aliquota = Aliquota::where('percentuale', $percentuale)->first();

        // Se non trova l'aliquota, usa quella predefinita
        if (empty($aliquota)) {
            $aliquota = Aliquota::find(setting('Iva predefinita'));
        }

        return $aliquota;
    }

    /**
     * Crea una riga per l'intervento se specificata.
     *
     * @param Intervento $intervento Intervento associato
     * @param array      $record     Record da processare
     *
     * @return Riga|null Riga creata o null se non creata
     */
    protected function creaRigaIntervento($intervento, $record)
    {
        // Verifica se sono presenti i dati per creare una riga
        if (empty($record['descrizione_riga']) && empty($record['imponibile'])) {
            return null;
        }

        try {
            // Crea una nuova riga per l'intervento
            $riga = Riga::build($intervento);

            // Imposta la descrizione della riga
            $riga->descrizione = $record['descrizione_riga'] ?: 'Riga importata';

            // Imposta la quantità a 1
            $riga->qta = 1;

            // Trova l'aliquota IVA
            $aliquota = $this->trovaAliquotaIva($record['aliquota_iva']);

            // Imposta il prezzo unitario e l'IVA
            $prezzo_unitario = !empty($record['imponibile']) ? floatval($record['imponibile']) : 0;
            $riga->setPrezzoUnitario($prezzo_unitario, $aliquota->id);

            // Salva la riga
            $riga->save();

            return $riga;
        } catch (\Exception $e) {
            // Registra l'errore ma continua con l'importazione
            error_log('Errore durante la creazione della riga dell\'intervento: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Valida il formato della data.
     *
     * @param string $data Data da validare
     *
     * @return bool True se il formato è valido, false altrimenti
     */
    protected function validaFormatoData($data)
    {
        if (empty($data)) {
            return false;
        }

        // Prova diversi formati di data comuni
        $formati = [
            'Y-m-d',
            'd/m/Y',
            'd-m-Y',
            'Y/m/d',
            'Y-m-d H:i:s',
            'd/m/Y H:i:s',
        ];

        foreach ($formati as $formato) {
            $date = \DateTime::createFromFormat($formato, $data);
            if ($date && $date->format($formato) === $data) {
                return true;
            }
        }

        // Prova anche con strtotime
        return strtotime($data) !== false;
    }
}
