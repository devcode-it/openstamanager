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
use Modules\Articoli\Barcode;
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
    protected $failed_errors = [];
    public function importRows($offset, $length, $update_record = true, $add_record = true)
    {
        $rows = $this->getRows($offset, $length);
        $imported_count = 0;
        $failed_count = 0;

        foreach ($rows as $row) {
            $record = $this->getRecord($row);

            $missing_required_fields = [];
            foreach ($this->getAvailableFields() as $field) {
                if (isset($field['required']) && $field['required'] === true && array_key_exists($field['field'], $record)) {
                    if (trim((string) $record[$field['field']]) === '') {
                        $missing_required_fields[] = $field['field'];
                    }
                }
            }

            if (!empty($missing_required_fields)) {
                $this->failed_records[] = $record;
                $this->failed_rows[] = $row;
                $this->failed_errors[] = 'Campi obbligatori mancanti: ' . implode(', ', $missing_required_fields);
                ++$failed_count;
                continue;
            }

            $result = $this->import($record, $update_record, $add_record);

            if ($result === false) {
                $this->failed_records[] = $record;
                $this->failed_rows[] = $row;
                ++$failed_count;
            } else {
                ++$imported_count;
            }
        }

        return [
            'imported' => $imported_count,
            'failed' => $failed_count,
            'total' => count($rows),
        ];
    }

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

    public function init()
    {
        $database = database();
        $primary_key = $this->getPrimaryKey();

        if (empty($this->getPrimaryKey())) {
            return;
        }

        $rows = $this->getRows(0, 2);
        if (count($rows) < 2) {
            return;
        }

        $first_record = $this->getRecord($rows[1]);
        if (empty($first_record['anagrafica_listino'])) {
            return;
        }

        $batch_size = 100;
        $offset = 0;
        $processed_records = [];

        while (true) {
            $rows = $this->getRows($offset, $batch_size);
            if (empty($rows)) {
                break;
            }

            foreach ($rows as $row) {
                $record = $this->getRecord($row);
                if (empty($record['anagrafica_listino']) || empty($record[$primary_key])) {
                    continue;
                }

                $key = $record[$primary_key].'|'.$record['anagrafica_listino'];
                if (isset($processed_records[$key])) {
                    continue;
                }
                $processed_records[$key] = true;

                $articolo = Articolo::where($primary_key, $record[$primary_key])->first();
                if (empty($articolo)) {
                    continue;
                }

                $anagrafica = null;
                if (!empty($record['p_iva'])) {
                    $anagrafica = Anagrafica::where('piva', $record['p_iva'])->first();
                }

                if (empty($anagrafica)) {
                    $anagrafica = Anagrafica::where('ragione_sociale', $record['anagrafica_listino'])->first();
                }

                if (empty($anagrafica)) {
                    continue;
                }

                if (!empty($record['prezzo_listino'])) {
                    // Determina la direzione del listino
                    $direzione = strtolower((string) ($record['dir'] ?? ''));
                    $direzione = match ($direzione) {
                        'fornitore' => 'uscita',
                        'cliente' => 'entrata',
                        default => null,
                    };

                    if ($direzione) {
                        // Elimina solo i prezzi nella direzione specificata
                        $database->query('DELETE FROM mg_prezzi_articoli WHERE id_articolo = '.prepare($articolo->id).' AND id_anagrafica = '.prepare($anagrafica->id).' AND dir = '.prepare($direzione));
                    } else {
                        // Se la direzione non è specificata, elimina tutti i prezzi per questa combinazione
                        $database->query('DELETE FROM mg_prezzi_articoli WHERE id_articolo = '.prepare($articolo->id).' AND id_anagrafica = '.prepare($anagrafica->id));
                    }
                }

                if (!empty($record['codice_fornitore']) && !empty($record['descrizione_fornitore'])) {
                    $database->query('DELETE FROM mg_fornitore_articolo WHERE id_articolo = '.prepare($articolo->id).' AND id_fornitore = '.prepare($anagrafica->id));
                }
            }

            $offset += $batch_size;
        }
    }

    public function import($record, $update_record = true, $add_record = true)
    {
        try {
            $database = database();
            $primary_key = $this->getPrimaryKey();
            $dettagli = [];

            if (empty($record['codice'])) {
                throw new \Exception('Codice articolo mancante');
            }
            if (empty($record['descrizione'])) {
                throw new \Exception('Descrizione articolo mancante');
            }

            $articolo = null;
            if (!empty($primary_key) && !empty($record[$primary_key])) {
                $articolo = Articolo::where($primary_key, $record[$primary_key])->withTrashed()->first();
            }

            if (empty($articolo) && !empty($record['codice'])) {
                $articolo = Articolo::where('codice', $record['codice'])->withTrashed()->first();
            }

            if (($articolo && !$update_record) || (!$articolo && !$add_record)) {
                return null;
            }

            if ($articolo && $articolo->trashed()) {
                $articolo->restore();
                error_log("Articolo ripristinato durante importazione: {$articolo->codice} - {$articolo->descrizione}");
            }

            $url = $record['immagine'] ?? '';
            unset($record['immagine']);

            if (!empty($record['id_fornitore'])) {
                $result = $database->fetchOne('SELECT idanagrafica AS id FROM an_anagrafiche WHERE LOWER(ragione_sociale) = LOWER('.prepare($record['id_fornitore']).')');
                if ($result) {
                    $dettagli['id_fornitore'] = $result['id'];
                    $dettagli['anagrafica_listino'] = $record['id_fornitore'];
                }
            }

            try {
                $categoria = $this->processaCategoria($record);
                $sottocategoria = $this->processaSottocategoria($record, $categoria);
            } catch (\Exception $e) {
                throw new \Exception('Errore nella gestione categoria/sottocategoria: ' . $e->getMessage());
            }

            try {
                $marca = $this->processaMarca($record);
            } catch (\Exception $e) {
                throw new \Exception('Errore nella gestione marca: ' . $e->getMessage());
            }

            $modello = null;
            if (!empty($record['modello'])) {
                try {
                    $modello = $this->processaModello($record, $marca);
                } catch (\Exception $e) {
                    error_log("Errore nella gestione modello: " . $e->getMessage());
                }
            }

            try {
                $this->processaUnitaMisura($record);
            } catch (\Exception $e) {
                throw new \Exception('Errore nella gestione unità di misura: ' . $e->getMessage());
            }

            if (empty($articolo)) {
                $articolo = Articolo::build($record['codice'], $categoria, $sottocategoria);
                $articolo->name = $record['descrizione'];
                if (!empty($record['descrizione'])) {
                    $articolo->setTranslation('title', $record['descrizione']);
                }
            } else {
                $articolo->name = $record['descrizione'];
                if (!empty($record['descrizione'])) {
                    $articolo->setTranslation('title', $record['descrizione']);
                }
            }

            $aliquota = null;
            if (!empty($record['codice_iva_vendita'])) {
                $aliquota = Aliquota::where('codice', $record['codice_iva_vendita'])->first();
                if ($aliquota) {
                    $articolo->idiva_vendita = $aliquota->id;
                } else {
                    $aliquota_predefinita = setting('Iva predefinita');
                    if ($aliquota_predefinita) {
                        $articolo->idiva_vendita = $aliquota_predefinita;
                    }
                }
            }

            if (!empty($record['prezzo_acquisto'])) {
                $record['prezzo_acquisto'] = str_replace(',', '.', $record['prezzo_acquisto']);
                if (!is_numeric($record['prezzo_acquisto'])) {
                    $record['prezzo_acquisto'] = 0;
                }
            }
            if (!empty($record['prezzo_vendita'])) {
                $record['prezzo_vendita'] = str_replace(',', '.', $record['prezzo_vendita']);
                if (!is_numeric($record['prezzo_vendita'])) {
                    $record['prezzo_vendita'] = 0;
                }
            }
            if (!empty($record['qta'])) {
                $record['qta'] = str_replace(',', '.', $record['qta']);
                if (!is_numeric($record['qta'])) {
                    $record['qta'] = 0;
                }
            }

            $articolo->attivo = 1;

            $nuova_qta = 0;
            if (isset($record['qta'])) {
                if (!is_numeric($record['qta'])) {
                    throw new \Exception('Quantità non valida: ' . $record['qta']);
                }
                $nuova_qta = (float) $record['qta'];
            }

            if (!empty($record['data_qta'])) {
                try {
                    $data_test = Carbon::createFromFormat('d/m/Y', $record['data_qta']);
                    if (!$data_test) {
                        $formati = ['Y-m-d', 'd-m-Y', 'd/m/y', 'Y/m/d'];
                        $data_valida = false;
                        foreach ($formati as $formato) {
                            try {
                                $data_test = Carbon::createFromFormat($formato, $record['data_qta']);
                                if ($data_test) {
                                    $record['data_qta'] = $data_test->format('d/m/Y');
                                    $data_valida = true;
                                    break;
                                }
                            } catch (\Exception $e) {
                                continue;
                            }
                        }
                        if (!$data_valida) {
                            $record['data_qta'] = Carbon::now()->format('d/m/Y');
                        }
                    }
                } catch (\Exception $e) {
                    $record['data_qta'] = Carbon::now()->format('d/m/Y');
                }
            }

            $nome_sede = $record['nome_sede'] ?? '';

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

            if (!empty($dettagli['anagrafica_listino']) && !empty($dettagli['prezzo_listino'])) {
                $this->aggiornaDettaglioPrezzi($articolo, $dettagli);
            }

            // Gestione barcode separata
            $barcode_value = $record['barcode'] ?? '';

            unset($record['anagrafica_listino'], $record['p_iva'], $record['qta_minima'],
                $record['qta_massima'], $record['prezzo_listino'], $record['sconto_listino'],
                $record['dir'], $record['codice_fornitore'], $record['barcode_fornitore'],
                $record['descrizione_fornitore'], $record['id_fornitore'],
                $record['categoria'], $record['sottocategoria'], $record['marca'], $record['modello'],
                $record['codice_iva_vendita'], $record['data_qta'], $record['nome_sede'], $record['barcode']);

            $this->processaImmagine($articolo, $url, $record);
            unset($record['import_immagine']);

            $articolo->fill($record);

            if ($categoria && $articolo->id_categoria != $categoria->id) {
                $articolo->categoria()->associate($categoria);
            }
            if ($sottocategoria && $articolo->id_sottocategoria != $sottocategoria->id) {
                $articolo->sottocategoria()->associate($sottocategoria);
            }

            if (!empty($marca)) {
                $articolo->marca()->associate($marca);
            }

            if (!empty($modello)) {
                $articolo->id_modello = $modello->id;
            }

            if (!empty($record['prezzo_vendita'])) {
                $articolo->setPrezzoVendita($record['prezzo_vendita'], $aliquota ? $aliquota->id : setting('Iva predefinita'));
            }

            $articolo->save();

            // Gestione barcode dopo il salvataggio dell'articolo
            if (!empty($barcode_value)) {
                $this->processaBarcode($articolo, $barcode_value);
            }

            $this->aggiornaGiacenza($articolo, $nuova_qta, $nome_sede, $record);

            return true;
        } catch (\Exception $e) {
            $error_message = 'Errore durante l\'importazione dell\'articolo';
            if (!empty($record['codice'])) {
                $error_message .= ' (Codice: ' . $record['codice'] . ')';
            }
            $error_message .= ': ' . $e->getMessage();

            error_log($error_message);

            $this->failed_errors[] = $e->getMessage();

            return false;
        }
    }

    public static function getExample()
    {
        return [
            ['Codice', 'Immagine', 'Import immagine', 'Descrizione', 'Quantità', 'Data inventario', 'Unità misura', 'Prezzo acquisto', 'Prezzo vendita', 'Peso', 'Volume', 'Categoria', 'Sottocategoria', 'marca', 'Modello', 'Barcode', 'Fornitore predefinito', 'Partita IVA', 'Codice IVA vendita', 'Ubicazione', 'Note', 'Anagrafica listino', 'Codice fornitore', 'Barcode fornitore', 'Descrizione fornitore', 'Qta minima', 'Qta massima', 'Prezzo listino', 'Sconto listino', 'Cliente/Fornitore listino', 'Sede'],
            ['OSM-BUDGET', 'https://openstamanager.com/moduli/budget/budget.webp', '2', 'Modulo Budget per OpenSTAManager', '1.00', '28/11/2023', 'PZ', '90.00', '180.00', '0.50', '0.00', 'Software gestionali', 'Moduli aggiuntivi', 'DevCode', 'Budget', '4006381333931', 'DevCode s.r.l.', '05024030289', '22', 'Scaffale A1', 'Nota ad uso interno', 'DevCode s.r.l.', 'DEV-BUDGET', '0123456789012', 'Strumento gestionale utilizzato per pianificare e monitorare le entrate e uscite aziendali', '1.00', '10.00', '180.00', '20.00', 'Fornitore', 'Sede']
        ];
    }

    protected function processaCategoria($record)
    {
        if (empty($record['categoria'])) {
            return null;
        }

        try {
            $categoria_id = (new Categoria())->getByField('title', $record['categoria']);
            $categoria = $categoria_id ? Categoria::find($categoria_id) : null;

            if (empty($categoria)) {
                $categoria = Categoria::where('name', $record['categoria'])
                    ->where('is_articolo', 1)
                    ->where('parent', null)
                    ->first();
            }

            if (empty($categoria)) {
                $categoria = Categoria::build(null, $record['categoria']);
                $categoria->is_articolo = 1;
                $categoria->setTranslation('title', $record['categoria']);
                $categoria->save();
            }

            return $categoria;
        } catch (\Exception $e) {
            throw new \Exception('Errore nella creazione/ricerca categoria "' . $record['categoria'] . '": ' . $e->getMessage());
        }
    }

    protected function processaSottocategoria($record, $categoria)
    {
        if (empty($record['sottocategoria']) || empty($categoria)) {
            return null;
        }

        try {
            $sottocategoria_id = (new Categoria())->getByField('title', $record['sottocategoria']);
            $sottocategoria = null;

            if ($sottocategoria_id) {
                $sottocategoria = Categoria::where('id', $sottocategoria_id)
                    ->where('parent', $categoria->id)
                    ->first();
            }

            if (empty($sottocategoria)) {
                $sottocategoria = Categoria::where('name', $record['sottocategoria'])
                    ->where('parent', $categoria->id)
                    ->first();
            }

            if (empty($sottocategoria)) {
                $sottocategoria = Categoria::build(null, $record['sottocategoria']);
                $sottocategoria->parent = $categoria->id;
                $sottocategoria->is_articolo = 1;
                $sottocategoria->setTranslation('title', $record['sottocategoria']);
                $sottocategoria->save();
            }

            return $sottocategoria;
        } catch (\Exception $e) {
            throw new \Exception('Errore nella creazione/ricerca sottocategoria "' . $record['sottocategoria'] . '": ' . $e->getMessage());
        }
    }

    protected function processaMarca($record)
    {
        if (empty($record['marca'])) {
            return null;
        }

        try {
            $marca = Marca::where('name', $record['marca'])
                ->where('is_articolo', 1)
                ->first();

            if (empty($marca)) {
                $marca = Marca::build($record['marca']);
                $marca->is_articolo = 1;
                $marca->save();
            }

            return $marca;
        } catch (\Exception $e) {
            throw new \Exception('Errore nella creazione/ricerca marca "' . $record['marca'] . '": ' . $e->getMessage());
        }
    }

    protected function processaUnitaMisura($record)
    {
        if (empty($record['um'])) {
            return;
        }

        try {
            $database = database();
            $um = $database->fetchOne('SELECT id FROM `mg_unitamisura` WHERE `valore`='.prepare($record['um']));
            if (empty($um)) {
                $result = $database->query('INSERT INTO `mg_unitamisura` (`valore`) VALUES ('.prepare($record['um']).')');
                if (!$result) {
                    throw new \Exception('Impossibile creare l\'unità di misura');
                }
            }
        } catch (\Exception $e) {
            throw new \Exception('Errore nella gestione unità di misura "' . $record['um'] . '": ' . $e->getMessage());
        }
    }

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

            if ($record['import_immagine'] == 2 || $record['import_immagine'] == 4) {
                \Uploads::deleteLinked([
                    'id_module' => Module::where('name', 'Articoli')->first()->id,
                    'id_record' => $articolo->id,
                ]);
            }

            if ($record['import_immagine'] == 1 || $record['import_immagine'] == 2) {
                $name = 'immagine_'.$articolo->id.'.'.Upload::getExtensionFromMimeType($file_content);

                \Uploads::upload($file_content, [
                    'name' => 'Immagine',
                    'category' => 'Immagini',
                    'original_name' => $name,
                    'id_module' => Module::where('name', 'Articoli')->first()->id,
                    'id_record' => $articolo->id,
                ], [
                    'thumbnails' => true,
                ]);
            }
        } catch (\Exception $e) {
            error_log('Errore durante l\'importazione dell\'immagine: '.$e->getMessage());
        }
    }

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
            error_log('Errore durante l\'aggiornamento della giacenza: '.$e->getMessage());
        }
    }

    protected function aggiornaDettaglioPrezzi(Articolo $articolo, $dettagli)
    {
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

        if (!empty($anagrafica) && !empty($dettagli['dir']) && $dettagli['prezzo_listino']) {
            // Elimina i prezzi esistenti per questo articolo e anagrafica nella direzione specificata
            $deleted_count = DettaglioPrezzo::where('id_articolo', $articolo->id)
                ->where('id_anagrafica', $anagrafica->id)
                ->where('dir', $dettagli['dir'])
                ->delete();

            if ($deleted_count > 0) {
                error_log("Eliminati {$deleted_count} prezzi esistenti per articolo {$articolo->codice} e anagrafica {$anagrafica->ragione_sociale} (direzione: {$dettagli['dir']})");
            }

            $dettaglio_predefinito = DettaglioPrezzo::build($articolo, $anagrafica, $dettagli['dir']);
            $dettaglio_predefinito->sconto_percentuale = $dettagli['sconto_listino'];
            $dettaglio_predefinito->setPrezzoUnitario($dettagli['prezzo_listino']);

            if (!empty($dettagli['qta_minima']) && !empty($dettagli['qta_massima'])) {
                $dettaglio_predefinito->minimo = $dettagli['qta_minima'];
                $dettaglio_predefinito->massimo = $dettagli['qta_massima'];
            }

            $dettaglio_predefinito->save();
            error_log("Creato nuovo prezzo per articolo {$articolo->codice} e anagrafica {$anagrafica->ragione_sociale}: {$dettagli['prezzo_listino']}");
        }

        if (!empty($anagrafica) && $dettagli['dir'] == 'uscita' && !empty($dettagli['codice_fornitore']) && !empty($dettagli['descrizione_fornitore'])) {
            // Elimina i dettagli fornitore esistenti per questo articolo e fornitore
            $deleted_count = DettaglioFornitore::where('id_articolo', $articolo->id)
                ->where('id_fornitore', $anagrafica->id)
                ->delete();

            if ($deleted_count > 0) {
                error_log("Eliminati {$deleted_count} dettagli fornitore esistenti per articolo {$articolo->codice} e fornitore {$anagrafica->ragione_sociale}");
            }

            $fornitore = DettaglioFornitore::build($anagrafica, $articolo);
            $fornitore->codice_fornitore = $dettagli['codice_fornitore'];
            $fornitore->barcode_fornitore = $dettagli['barcode_fornitore'];
            $fornitore->descrizione = $dettagli['descrizione_fornitore'];
            $fornitore->save();
            error_log("Creato nuovo dettaglio fornitore per articolo {$articolo->codice} e fornitore {$anagrafica->ragione_sociale}: {$dettagli['codice_fornitore']}");
        }

        $dettagli['id_fornitore'] = $anagrafica->id;
        $listino_id_fornitore = DettaglioPrezzo::dettaglioPredefinito($articolo->id, $dettagli['id_fornitore'], 'uscita')->first();
        if (!empty($listino_id_fornitore)) {
            $prezzo_acquisto = $listino_id_fornitore->prezzo_unitario - ($listino_id_fornitore->prezzo_unitario * $listino_id_fornitore->sconto_percentuale) / 100;
            $articolo->prezzo_acquisto = $prezzo_acquisto;
            $articolo->id_fornitore = $dettagli['id_fornitore'];
            $articolo->save();
        }
    }

    public function saveFailedRecordsWithErrors($filepath)
    {
        if (empty($this->failed_rows)) {
            return '';
        }

        $dir = dirname($filepath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $file = fopen($filepath, 'w');
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

        $header = $this->getHeader();
        $header[] = 'Errore';
        fputcsv($file, $header, ';');

        foreach ($this->failed_rows as $index => $row) {
            $error_message = $this->failed_errors[$index] ?? 'Errore sconosciuto';
            $row[] = $error_message;
            fputcsv($file, $row, ';');
        }

        fclose($file);

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
     * Gestisce la creazione o ricerca di un modello.
     *
     * @param array $record
     * @param Marca|null $marca
     *
     * @return Marca|null
     */
    protected function processaModello($record, $marca = null)
    {
        if (empty($record['modello'])) {
            return null;
        }

        try {
            error_log("Processando modello: " . $record['modello'] . " per marca: " . ($marca ? $marca->name : 'nessuna'));

            $modello = null;

            if (!empty($marca)) {
                $modello = Marca::where('name', $record['modello'])
                    ->where('parent', $marca->id)
                    ->where('is_articolo', 1)
                    ->first();

                error_log("Ricerca modello con parent {$marca->id}: " . ($modello ? 'trovato' : 'non trovato'));
            }

            if (empty($modello)) {
                $modello = Marca::where('name', $record['modello'])
                    ->where('parent', '>', 0)
                    ->where('is_articolo', 1)
                    ->first();
            }

            if (empty($modello)) {
                error_log("Creando nuovo modello: " . $record['modello']);

                $modello = Marca::build($record['modello']);
                $modello->is_articolo = 1;

                if (!empty($marca)) {
                    $modello->parent = $marca->id;
                    error_log("Impostando parent del modello: {$marca->id} ({$marca->name})");
                } else {
                    $marca_generica = Marca::where('name', 'Generico')
                        ->where('is_articolo', 1)
                        ->where('parent', null)
                        ->first();

                    if (empty($marca_generica)) {
                        error_log("Creando marca generica");
                        $marca_generica = Marca::build('Generico');
                        $marca_generica->is_articolo = 1;
                        $marca_generica->save();
                    }

                    $modello->parent = $marca_generica->id;
                    error_log("Impostando parent del modello a marca generica: {$marca_generica->id}");
                }

                $modello->save();
                error_log("Modello salvato con ID: {$modello->id}");
            }

            return $modello;
        } catch (\Exception $e) {
            throw new \Exception('Errore nella creazione/ricerca modello "' . $record['modello'] . '": ' . $e->getMessage());
        }
    }

    /**
     * Gestisce l'importazione dei barcode per un articolo.
     *
     * @param Articolo $articolo
     * @param string   $barcode_value
     *
     * @throws \Exception
     */
    protected function processaBarcode(Articolo $articolo, $barcode_value)
    {
        if (empty($barcode_value)) {
            return;
        }

        try {
            $database = database();

            // Supporta barcode multipli separati da virgola
            $barcodes = array_map('trim', explode(',', $barcode_value));

            foreach ($barcodes as $barcode) {
                if (empty($barcode)) {
                    continue;
                }

                // Verifica che il barcode non sia già presente nella tabella mg_articoli (barcode principali)
                $esistente_articoli = Articolo::where('name', $articolo->name)->count() > 0;

                // Verifica che il barcode non sia già presente nella tabella mg_articoli_barcode (barcode aggiuntivi)
                $esistente_barcode = $database->table('mg_articoli_barcode')
                    ->where('barcode', $barcode)
                    ->count() > 0;

                // Verifica che il barcode non coincida con un codice articolo esistente
                $coincide_codice = Articolo::where([
                    ['codice', $barcode],
                    ['barcode', '=', ''],
                ])->count() > 0;

                // Se il barcode è unico, procede con l'inserimento
                if ($esistente_articoli && !$esistente_barcode && !$coincide_codice) {
                    Barcode::build($articolo->id, $barcode);
                    error_log("Barcode aggiunto per articolo {$articolo->codice}: {$barcode}");
                } else {
                    error_log("Barcode già esistente o in conflitto per articolo {$articolo->codice}: {$barcode}");
                }
            }
        } catch (\Exception $e) {
            throw new \Exception('Errore durante l\'importazione del barcode "'.$barcode_value.'": '.$e->getMessage());
        }
    }
}
