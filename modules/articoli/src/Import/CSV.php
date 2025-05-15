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

namespace Modules\Articoli\Import;

use Carbon\Carbon;
use Importer\CSVImporter;
use Models\Module;
use Models\Upload;
use Modules\Anagrafiche\Anagrafica;
use Modules\Anagrafiche\Sede;
use Modules\Anagrafiche\Tipo;
use Modules\Articoli\Articolo;
use Modules\Articoli\Categoria;
use Modules\Articoli\Marca;
use Modules\Iva\Aliquota;
use Plugins\ListinoClienti\DettaglioPrezzo;
use Plugins\ListinoFornitori\DettaglioFornitore;

/**
 * Struttura per la gestione delle operazioni di importazione (da CSV) degli Articoli.
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
                'required' => true,
            ],
            [
                'field' => 'immagine',
                'label' => 'Immagine',
                'names' => [
                    'Immagine',
                    'Foto',
                ],
            ],
            [
                'field' => 'import_immagine',
                'label' => 'Import immagine',
            ],
            [
                'field' => 'descrizione',
                'label' => 'Descrizione',
                'required' => true,
            ],
            [
                'field' => 'qta',
                'label' => 'Quantit&agrave;',
                'names' => [
                    'Quantita',
                    'Quantità',
                    'Qta',
                ],
            ],
            [
                'field' => 'data_qta',
                'label' => 'Data inventario',
            ],
            [
                'field' => 'um',
                'label' => 'Unit&agrave; di misura',
                'names' => [
                    'Unità di misura',
                    'Unità misura',
                    'Unit` di misura',
                    'um',
                ],
            ],
            [
                'field' => 'prezzo_acquisto',
                'label' => 'Prezzo acquisto',
            ],
            [
                'field' => 'prezzo_vendita',
                'label' => 'Prezzo vendita',
            ],
            [
                'field' => 'peso_lordo',
                'label' => 'Peso lordo (KG)',
                'names' => [
                    'Peso lordo (KG)',
                    'Peso',
                ],
            ],
            [
                'field' => 'volume',
                'label' => 'Volume (M3)',
                'names' => [
                    'Volume (M3)',
                    'Volume',
                ],
            ],
            [
                'field' => 'categoria',
                'label' => 'Categoria',
                'names' => [
                    'Categoria',
                    'categoria',
                ],
            ],
            [
                'field' => 'sottocategoria',
                'label' => 'Sottocategoria',
                'names' => [
                    'Sottocategoria',
                    'sottocategoria',
                ],
            ],
            [
                'field' => 'marca',
                'label' => 'marca',
                'names' => [
                    'marca',
                    'marca',
                    'Marca',
                    'marca',
                ],
            ],
            [
                'field' => 'modello',
                'label' => 'Modello',
                'names' => [
                    'Modello',
                    'modello',
                ],
            ],
            [
                'field' => 'barcode',
                'label' => 'Barcode',
                'names' => [
                    'barcode',
                    'Barcode',
                    'EAN',
                ],
            ],
            [
                'field' => 'id_fornitore',
                'label' => 'Fornitore predefinito',
                'names' => [
                    'Fornitore predefinito',
                ],
            ],
            [
                'field' => 'p_iva',
                'label' => 'Partita IVA',
                'names' => [
                    'partita iva',
                    'Partita IVA',
                ],
            ],
            [
                'field' => 'codice_iva_vendita',
                'label' => 'Codice IVA vendita',
                'names' => [
                    'Codice IVA vendita',
                    'codice_iva_vendita',
                ],
            ],
            [
                'field' => 'ubicazione',
                'label' => 'Ubicazione',
            ],
            [
                'field' => 'note',
                'label' => 'Note',
            ],
            [
                'field' => 'anagrafica_listino',
                'label' => 'Anagrafica listino',
            ],
            [
                'field' => 'codice_fornitore',
                'label' => 'Codice fornitore',
            ],
            [
                'field' => 'barcode_fornitore',
                'label' => 'Barcode fornitore',
            ],
            [
                'field' => 'descrizione_fornitore',
                'label' => 'Descrizione fornitore',
            ],
            [
                'field' => 'qta_minima',
                'label' => 'Qta minima',
            ],
            [
                'field' => 'qta_massima',
                'label' => 'Qta massima',
            ],
            [
                'field' => 'prezzo_listino',
                'label' => 'Prezzo listino',
            ],
            [
                'field' => 'sconto_listino',
                'label' => 'Sconto listino',
            ],
            [
                'field' => 'dir',
                'label' => 'Cliente/Fornitore listino',
            ],
            [
                'field' => 'nome_sede',
                'label' => 'Sede',
            ],
        ];
    }

    /**
     * Procedura di inizializzazione per l'importazione.
     * Effettua una rimozione di tutti i dettagli prezzi per le coppie Articolo - Anagrafica presenti nel CSV.
     * Ottimizzata per gestire grandi quantità di dati in modo efficiente.
     *
     * @return void
     */
    public function init()
    {
        $database = database();
        $primary_key = $this->getPrimaryKey();

        // Verifica se ci sono i requisiti minimi per procedere
        if (empty($this->getPrimaryKey())) {
            return;
        }

        // Lettura primo record per verificare se è necessaria la pulizia dei listini
        $rows = $this->getRows(0, 2);
        if (count($rows) < 2) {
            return; // Non ci sono abbastanza righe
        }

        $first_record = $this->getRecord($rows[1]);
        if (empty($first_record['anagrafica_listino'])) {
            return; // Non c'è anagrafica listino nel primo record
        }

        // Elaborazione a blocchi per ottimizzare l'uso della memoria
        $batch_size = 100; // Dimensione del batch
        $offset = 0;
        $processed_records = [];

        while (true) {
            $rows = $this->getRows($offset, $batch_size);
            if (empty($rows)) {
                break; // Fine del file
            }

            foreach ($rows as $row) {
                // Interpretazione secondo la selezione
                $record = $this->getRecord($row);
                if (empty($record['anagrafica_listino']) || empty($record[$primary_key])) {
                    continue;
                }

                // Crea una chiave univoca per evitare duplicati
                $key = $record[$primary_key].'|'.$record['anagrafica_listino'];
                if (isset($processed_records[$key])) {
                    continue; // Evita di elaborare lo stesso record più volte
                }
                $processed_records[$key] = true;

                // Cerca articolo e anagrafica
                $articolo = Articolo::where($primary_key, $record[$primary_key])->first();
                if (empty($articolo)) {
                    continue;
                }

                // Prima cerca per partita IVA se disponibile
                $anagrafica = null;
                if (!empty($record['p_iva'])) {
                    $anagrafica = Anagrafica::where('piva', $record['p_iva'])->first();
                }

                // Se non trovata, cerca per ragione sociale
                if (empty($anagrafica)) {
                    $anagrafica = Anagrafica::where('ragione_sociale', $record['anagrafica_listino'])->first();
                }

                if (empty($anagrafica)) {
                    continue;
                }

                // Elimina i record esistenti solo se verranno sostituiti
                if (!empty($record['prezzo_listino'])) {
                    $database->query('DELETE FROM mg_prezzi_articoli WHERE id_articolo = '.prepare($articolo->id).' AND id_anagrafica = '.prepare($anagrafica->id));
                }

                if (!empty($record['codice_fornitore']) && !empty($record['descrizione_fornitore'])) {
                    $database->query('DELETE FROM mg_fornitore_articolo WHERE id_articolo = '.prepare($articolo->id).' AND id_fornitore = '.prepare($anagrafica->id));
                }
            }

            $offset += $batch_size;
        }
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
            $primary_key = $this->getPrimaryKey();
            $dettagli = [];

            // Validazione dei campi obbligatori
            if (empty($record['codice']) || empty($record['descrizione'])) {
                return false;
            }

            // Individuazione articolo e generazione
            $articolo = null;
            // Ricerca sulla base della chiave primaria se presente
            if (!empty($primary_key) && !empty($record[$primary_key])) {
                $articolo = Articolo::where($primary_key, $record[$primary_key])->withTrashed()->first();
            }

            // Controllo se creare o aggiornare il record
            if (($articolo && !$update_record) || (!$articolo && !$add_record)) {
                return null;
            }

            // Estrazione e gestione dell'immagine
            $url = $record['immagine'] ?? '';
            unset($record['immagine']);

            // Fix per campi con contenuti derivati da query implicite
            if (!empty($record['id_fornitore'])) {
                $result = $database->fetchOne('SELECT idanagrafica AS id FROM an_anagrafiche WHERE LOWER(ragione_sociale) = LOWER('.prepare($record['id_fornitore']).')');
                $dettagli['id_fornitore'] = $result ? $result['id'] : null;
                $dettagli['anagrafica_listino'] = $record['id_fornitore'];
            }

            // Gestione categoria e sottocategoria
            $categoria = $this->processaCategoria($record);
            $sottocategoria = $this->processaSottocategoria($record, $categoria);

            // Gestione marca e modello
            $marca = $this->processaMarca($record);

            // Gestione unità di misura
            $this->processaUnitaMisura($record);

            // Creazione o aggiornamento dell'articolo
            if (empty($articolo)) {
                $articolo = Articolo::build($record['codice'], $categoria, $sottocategoria);
                if (!empty($record['descrizione'])) {
                    $articolo->setTranslation('title', $record['descrizione']);
                }
            } else {
                $articolo->restore();
            }

            // Individuazione dell'IVA di vendita tramite il relativo Codice
            $aliquota = null;
            if (!empty($record['codice_iva_vendita'])) {
                $aliquota = Aliquota::where('codice', $record['codice_iva_vendita'])->first();
                if ($aliquota) {
                    $articolo->idiva_vendita = $aliquota->id;
                }
            }

            $articolo->attivo = 1;

            // Esportazione della quantità indicata
            $nuova_qta = isset($record['qta']) ? (float) $record['qta'] : 0;
            $nome_sede = $record['nome_sede'] ?? '';

            // Aggiornamento dettaglio prezzi
            $dettagli['anagrafica_listino'] ??= $record['anagrafica_listino'];
            $dettagli['partita_iva'] = $record['p_iva'] ?? '';
            $dettagli['qta_minima'] = $record['qta_minima'] ?? '';
            $dettagli['qta_massima'] = $record['qta_massima'] ?? '';
            $dettagli['prezzo_listino'] = $record['prezzo_listino'] ?? '';
            $dettagli['sconto_listino'] = $record['sconto_listino'] ?? '';
            $dettagli['dir'] = $record['dir'] ?? '';
            $dettagli['codice_fornitore'] = $record['codice_fornitore'] ?? '';
            $dettagli['barcode_fornitore'] = $record['barcode_fornitore'] ?? '';
            $dettagli['descrizione_fornitore'] = $record['descrizione_fornitore'] ?? '';

            // Aggiorna i dettagli prezzi solo se ci sono informazioni sufficienti
            if (!empty($dettagli['anagrafica_listino']) && !empty($dettagli['prezzo_listino'])) {
                $this->aggiornaDettaglioPrezzi($articolo, $dettagli);
            }

            // Rimuovi i campi già elaborati
            unset($record['anagrafica_listino'], $record['p_iva'], $record['qta_minima'],
                $record['qta_massima'], $record['prezzo_listino'], $record['sconto_listino'],
                $record['dir'], $record['codice_fornitore'], $record['barcode_fornitore'],
                $record['descrizione_fornitore'], $record['id_fornitore']);

            // Gestione immagine
            $this->processaImmagine($articolo, $url, $record);
            unset($record['import_immagine']);

            // Salvataggio delle informazioni generali
            $articolo->fill($record);

            // Aggiorna categoria e sottocategoria
            if ($categoria || $sottocategoria) {
                $articolo->fill([
                    'categoria' => $categoria ? $categoria->id : $articolo['categoria'],
                    'sottocategoria' => $sottocategoria ? $sottocategoria->id : $articolo['sottocategoria'],
                ]);
            }

            // Associazione marca
            if (!empty($marca)) {
                $articolo->marca()->associate($marca);
            }

            // Associazione modello
            if (!empty($record['modello'])) {
                $articolo->id_modello = $record['modello'];
            }

            // Prezzo di vendita
            if (!empty($record['prezzo_vendita'])) {
                $articolo->setPrezzoVendita($record['prezzo_vendita'], $aliquota ? $aliquota->id : setting('Iva predefinita'));
            }

            $articolo->save();

            // Movimentazione della quantità registrata
            $this->aggiornaGiacenza($articolo, $nuova_qta, $nome_sede, $record);

            return true;
        } catch (\Exception $e) {
            // Registra l'errore in un log
            error_log('Errore durante l\'importazione dell\'articolo: '.$e->getMessage());

            return false;
        }
    }

    public static function getExample()
    {
        return [
            ['Codice', 'Immagine', 'Import immagine', 'Descrizione', 'Quantità', 'Data inventario', 'Unità misura', 'Prezzo acquisto', 'Prezzo vendita', 'Peso', 'Volume', 'Categoria', 'Sottocategoria', 'marca', 'Modello', 'Barcode', 'Fornitore predefinito', 'Partita IVA', 'Codice IVA vendita', 'Ubicazione', 'Note', 'Anagrafica listino', 'Codice fornitore', 'Barcode fornitore', 'Descrizione fornitore', 'Qta minima', 'Qta massima', 'Prezzo listino', 'Sconto listino', 'Cliente/Fornitore listino', 'Sede'],
            ['OSM-BUDGET', 'https://openstamanager.com/moduli/budget/budget.webp', '2', 'Modulo Budget per OpenSTAManager', '1', '28/11/2023', 'PZ', '90.00', '180.00', '', '', 'Software gestionali', 'Moduli aggiuntivi', 'DevCode', 'Budget', '4006381333931', 'DevCode s.r.l.', '05024030289', '', '', 'Nota ad uso interno', 'DevCode s.r.l.', 'DEV-BUDGET', '0123456789012', 'Strumento gestionale utilizzato per pianificare e monitorare le entrate e uscite aziendali', '1', '10', '180.00', '20', 'Fornitore', 'Sede'],
        ];
    }

    /**
     * Processa la categoria dell'articolo.
     *
     * @param array $record Record da processare
     *
     * @return Categoria|null Categoria processata
     */
    protected function processaCategoria($record)
    {
        if (empty($record['categoria'])) {
            return null;
        }

        $categoria = Categoria::where('id', '=', (new Categoria())->getByField('title', strtolower((string) $record['categoria'])))->where('is_articolo', '=', 0)->first();

        if (empty($categoria)) {
            $categoria = Categoria::build();
            $categoria->setTranslation('title', $record['categoria']);
            $categoria->is_articolo = 1;
            $categoria->save();
        }

        return $categoria;
    }

    /**
     * Processa la sottocategoria dell'articolo.
     *
     * @param array          $record    Record da processare
     * @param Categoria|null $categoria Categoria padre
     *
     * @return Categoria|null Sottocategoria processata
     */
    protected function processaSottocategoria($record, $categoria)
    {
        if (empty($record['sottocategoria']) || empty($categoria)) {
            return null;
        }

        $sottocategoria = Categoria::where('id', '=', (new Categoria())->getByField('title', strtolower((string) $record['sottocategoria'])))->first();

        if (empty($sottocategoria)) {
            $sottocategoria = Categoria::build();
            $sottocategoria->setTranslation('title', $record['sottocategoria']);
            $sottocategoria->parent()->associate($categoria);
            $sottocategoria->save();
        }

        return $sottocategoria;
    }

    /**
     * Processa la marca dell'articolo.
     *
     * @param array $record Record da processare
     *
     * @return Marca|null Marca processata
     */
    protected function processaMarca($record)
    {
        if (empty($record['marca'])) {
            return null;
        }

        $marca = Marca::where('name', $record['marca'])->first();

        if (empty($marca)) {
            $marca = Marca::build($record['marca']);
            $marca->save();
        }

        return $marca;
    }

    /**
     * Processa l'unità di misura dell'articolo.
     *
     * @param array $record Record da processare
     *
     * @return void
     */
    protected function processaUnitaMisura($record)
    {
        if (empty($record['um'])) {
            return;
        }

        $database = database();
        $um = $database->fetchOne('SELECT id FROM `mg_unitamisura` WHERE `valore`='.prepare($record['um']));
        if (empty($um)) {
            $database->query('INSERT INTO `mg_unitamisura` (`valore`) VALUES ('.prepare($record['um']).')');
        }
    }

    /**
     * Processa l'immagine dell'articolo.
     *
     * @param Articolo $articolo Articolo da aggiornare
     * @param string   $url      URL dell'immagine
     * @param array    $record   Record da processare
     *
     * @return void
     */
    protected function processaImmagine($articolo, $url, $record)
    {
        if (empty($url) || empty($record['import_immagine'])) {
            return;
        }

        try {
            $file_content = @file_get_contents($url);

            if (empty($file_content)) {
                return;
            }

            $database = database();

            /*
             * Import immagine options:
             *
             * - 1: Permette di importare l'immagine come principale dell'articolo mantenendo gli altri allegati già presenti.
             * - 2: Permette di importare l'immagine come principale dell'articolo rimuovendo tutti gli allegati presenti.
             * - 3: Permette di importare l'immagine come allegato dell'articolo.
             * - 4: Permette di importare l'immagine come allegato dell'articolo rimuovendo tutti gli allegati presenti.
             */
            if ($record['import_immagine'] == 2 || $record['import_immagine'] == 4) {
                \Uploads::deleteLinked([
                    'id_module' => Module::where('name', 'Articoli')->first()->id,
                    'id_record' => $articolo->id,
                ]);

                $database->update('mg_articoli', [
                    'immagine' => '',
                ], [
                    'id' => $articolo->id,
                ]);
            }

            $name = 'immagine_'.$articolo->id.'.'.Upload::getExtensionFromMimeType($file_content);

            $upload = \Uploads::upload($file_content, [
                'name' => 'Immagine',
                'category' => 'Immagini',
                'original_name' => $name,
                'id_module' => Module::where('name', 'Articoli')->first()->id,
                'id_record' => $articolo->id,
            ], [
                'thumbnails' => true,
            ]);

            if ($upload && !empty($upload->filename) && ($record['import_immagine'] == 1 || $record['import_immagine'] == 2)) {
                $database->update('mg_articoli', [
                    'immagine' => $upload->filename,
                ], [
                    'id' => $articolo->id,
                ]);
            }
        } catch (\Exception $e) {
            // Registra l'errore ma continua con l'importazione
            error_log('Errore durante l\'importazione dell\'immagine: '.$e->getMessage());
        }
    }

    /**
     * Aggiorna la giacenza dell'articolo.
     *
     * @param Articolo $articolo  Articolo da aggiornare
     * @param float    $nuova_qta Nuova quantità
     * @param string   $nome_sede Nome della sede
     * @param array    $record    Record da processare
     *
     * @return void
     */
    protected function aggiornaGiacenza($articolo, $nuova_qta, $nome_sede, $record)
    {
        if (!isset($record['qta']) || empty($record['data_qta'])) {
            return;
        }

        try {
            $anagrafica_azienda = Anagrafica::find(setting('Azienda predefinita'));
            $id_sede = 0;

            if (!empty($nome_sede)) {
                $sede = Sede::where('nomesede', $nome_sede)
                    ->where('idanagrafica', $anagrafica_azienda->id)
                    ->first();
                $id_sede = $sede ? $sede->id : 0;
            }

            $giacenze = $articolo->getGiacenze($record['data_qta']);
            $giacenza_attuale = isset($giacenze[$id_sede]) ? $giacenze[$id_sede][0] : 0;
            $qta_movimento = $nuova_qta - $giacenza_attuale;

            if ($qta_movimento != 0) {
                $articolo->movimenta($qta_movimento, tr('Movimento da importazione'), new Carbon($record['data_qta']), true, [
                    'idsede' => $id_sede,
                ]);
            }
        } catch (\Exception $e) {
            // Registra l'errore ma continua con l'importazione
            error_log('Errore durante l\'aggiornamento della giacenza: '.$e->getMessage());
        }
    }

    protected function aggiornaDettaglioPrezzi(Articolo $articolo, $dettagli)
    {
        // Listini
        if ($dettagli['partita_iva']) {
            $anagrafica = Anagrafica::where('piva', $dettagli['partita_iva'])->first();
        }

        if (empty($anagrafica) && $dettagli['anagrafica_listino']) {
            $anagrafica = Anagrafica::where('ragione_sociale', $dettagli['anagrafica_listino'])->first();
        }

        if (empty($anagrafica)) {
            $anagrafica = Anagrafica::build($dettagli['anagrafica_listino']);
            $anagrafica->piva = $dettagli['partita_iva'];
            $anagrafica->save();
        }

        if ($dettagli['dir']) {
            $tipo = Tipo::where('name', $dettagli['dir'])->first();
            if ($tipo) {
                $tipi = $anagrafica->tipi->pluck('id')->toArray();
                $tipi[] = $tipo->id;
                $anagrafica->tipologie = $tipi;
                $anagrafica->save();
            }
        }

        $dettagli['dir'] = strtolower((string) $dettagli['dir']);
        $dettagli['dir'] = match ($dettagli['dir']) {
            'fornitore' => 'uscita',
            'cliente' => 'entrata',
            default => null,
        };

        // Aggiungo Listino
        if (!empty($anagrafica) && !empty($dettagli['dir']) && $dettagli['prezzo_listino']) {
            $dettaglio_predefinito = DettaglioPrezzo::build($articolo, $anagrafica, $dettagli['dir']);
            $dettaglio_predefinito->sconto_percentuale = $dettagli['sconto_listino'];
            $dettaglio_predefinito->setPrezzoUnitario($dettagli['prezzo_listino']);

            if (!empty($dettagli['qta_minima']) && !empty($dettagli['qta_massima'])) {
                $dettaglio_predefinito->minimo = $dettagli['qta_minima'];
                $dettaglio_predefinito->massimo = $dettagli['qta_massima'];
            }

            $dettaglio_predefinito->save();
        }

        // Aggiungo dettagli fornitore
        if (!empty($anagrafica) && $dettagli['dir'] == 'uscita' && !empty($dettagli['codice_fornitore']) && !empty($dettagli['descrizione_fornitore'])) {
            $fornitore = DettaglioFornitore::build($anagrafica, $articolo);
            $fornitore->codice_fornitore = $dettagli['codice_fornitore'];
            $fornitore->barcode_fornitore = $dettagli['barcode_fornitore'];
            $fornitore->descrizione = $dettagli['descrizione_fornitore'];
            $fornitore->save();
        }

        // Imposto fornitore e prezzo predefinito
        $dettagli['id_fornitore'] = $anagrafica->id;
        $listino_id_fornitore = DettaglioPrezzo::dettaglioPredefinito($articolo->id, $dettagli['id_fornitore'], 'uscita')->first();
        if (!empty($listino_id_fornitore)) {
            $prezzo_acquisto = $listino_id_fornitore->prezzo_unitario - ($listino_id_fornitore->prezzo_unitario * $listino_id_fornitore->sconto_percentuale) / 100;
            $articolo->prezzo_acquisto = $prezzo_acquisto;
            $articolo->id_fornitore = $dettagli['id_fornitore'];
            $articolo->save();
        }
    }
}
