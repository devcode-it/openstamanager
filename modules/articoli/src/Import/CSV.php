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
use Models\Upload;
use Modules;
use Modules\Anagrafiche\Anagrafica;
use Modules\Anagrafiche\Sede;
use Modules\Articoli\Articolo;
use Modules\Articoli\Categoria;
use Modules\Iva\Aliquota;
use Plugins\ListinoClienti\DettaglioPrezzo;
use Plugins\ListinoFornitori\DettaglioFornitore;
use Uploads;

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
            ],
            [
                'field' => 'qta',
                'label' => 'Quantità',
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
                'field' => 'id_categoria',
                'label' => 'Categoria',
                'names' => [
                    'Categoria',
                    'categoria',
                    'idcategoria',
                ],
            ],
            [
                'field' => 'id_sottocategoria',
                'label' => 'Sottocategoria',
                'names' => [
                    'Sottocategoria',
                    'sottocategoria',
                    'idsottocategoria',
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
                    'id_fornitore',
                    'Id Fornitore',
                    'Fornitore',
                    'Fornitore predefinito',
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
        ];
    }

    /**
     * Procedura di inizializzazione per l'importazione.
     * Effettua una rimozione di tutti i dettagli prezzi per le coppie Articolo - Anagrafica presenti nel CSV.
     *
     * @return mixed|void
     */
    public function init()
    {
        $database = database();
        $primary_key = $this->getPrimaryKey();

        // Count the numbers of rows in a CSV
        $number = 0;
        foreach ($this->csv as $row) {
            $number++;
        }

        $rows = $this->getRows(0, $number);
        $first_record = $this->getRecord($rows[1]);
        if (!isset($first_record['anagrafica_listino']) || empty($this->getPrimaryKey())) {
            return;
        }

        foreach ($rows as $row) {
            // Interpretazione secondo la selezione
            $record = $this->getRecord($row);
            if (empty($record['anagrafica_listino'])) {
                continue;
            }

            $articolo = Articolo::where($primary_key, $record[$primary_key])
                ->first();
            $anagrafica = Anagrafica::where('ragione_sociale', $record['anagrafica_listino'])
                ->first();
            if (empty($articolo) || empty($anagrafica)) {
                continue;
            }

            if (!empty($record['prezzo_listino'])) {
                $database->query('DELETE FROM mg_prezzi_articoli WHERE id_articolo = '.prepare($articolo->id).' AND id_anagrafica = '.prepare($anagrafica->id));
            }

            if (!empty($record['codice_fornitore']) && !empty($record['descrizione_fornitore'])) {
                $database->query('DELETE FROM mg_fornitore_articolo WHERE id_articolo = '.prepare($articolo->id).' AND id_fornitore = '.prepare($anagrafica->id));
            }
        }
    }

    public function import($record)
    {
        $database = database();
        $primary_key = $this->getPrimaryKey();
        $url = $record['immagine'];
        unset($record['immagine']);

        // Fix per campi con contenuti derivati da query implicite
        if (!empty($record['id_fornitore'])) {
            $record['id_fornitore'] = $database->fetchOne('SELECT idanagrafica AS id FROM an_anagrafiche WHERE LOWER(ragione_sociale) = LOWER('.prepare($record['id_fornitore']).')')['id'];
        }

        // Gestione categoria e sottocategoria
        $categoria = null;
        $sottocategoria = null;
        if (!empty($record['id_categoria'])) {
            // Categoria
            $categoria = Categoria::where('nome', strtolower($record['id_categoria']))->first();

            if (empty($categoria)) {
                $categoria = Categoria::build($record['id_categoria']);
            }

            // Sotto-categoria
            if (!empty($record['id_sottocategoria'])) {
                $sottocategoria = Categoria::where('nome', $record['id_sottocategoria'])
                    ->where('parent', $categoria->id)
                    ->first();

                if (empty($sottocategoria)) {
                    $sottocategoria = Categoria::build($record['id_sottocategoria']);
                    $sottocategoria->parent()->associate($categoria);
                    $sottocategoria->save();
                }
            }
        }

        // Gestione um
        $um = null;
        if (!empty($record['um'])) {
            $um = $database->fetchOne('SELECT id FROM `mg_unitamisura` WHERE `valore`='.prepare($record['um']));
            if (empty($um)) {
                $database->query('INSERT INTO `mg_unitamisura` (`valore`) VALUES ('.prepare($record['um']).')');
            }
        }

        // Individuazione articolo e generazione
        $articolo = null;
        // Ricerca sulla base della chiave primaria se presente
        if (!empty($primary_key)) {
            $articolo = Articolo::where($primary_key, $record[$primary_key])->withTrashed()->first();
        }
        if (empty($articolo)) {
            $articolo = Articolo::build($record['codice'], $record['descrizione'], $categoria, $sottocategoria);
        } else {
            $articolo->restore();
        }

        // Individuazione dell'IVA di vendita tramite il relativo Codice
        $aliquota = null;
        if (!empty($record['codice_iva_vendita'])) {
            $aliquota = Aliquota::where('codice', $record['codice_iva_vendita'])->first();
            $articolo->idiva_vendita = $aliquota->id;
        }

        $articolo->attivo = 1;

        // Esportazione della quantità indicata
        $nuova_qta = (float) ($record['qta']);
        $nome_sede = $record['nome_sede'];

        // Aggiornamento dettaglio prezzi
        $dettagli['anagrafica_listino'] = $record['anagrafica_listino'];
        $dettagli['qta_minima'] = $record['qta_minima'];
        $dettagli['qta_massima'] = $record['qta_massima'];
        $dettagli['prezzo_listino'] = $record['prezzo_listino'];
        $dettagli['sconto_listino'] = $record['sconto_listino'];
        $dettagli['dir'] = $record['dir'];
        $dettagli['codice_fornitore'] = $record['codice_fornitore'];
        $dettagli['barcode_fornitore'] = $record['barcode_fornitore'];
        $dettagli['descrizione_fornitore'] = $record['descrizione_fornitore'];
        $dettagli['id_fornitore'] = $record['id_fornitore'];
        $this->aggiornaDettaglioPrezzi($articolo, $dettagli);

        //Gestione immagine
        if (!empty($url) && !empty($record['import_immagine'])) {
            $file_content = file_get_contents($url);

            if (!empty($file_content)) {
                if ($record['import_immagine'] == 2 || $record['import_immagine'] == 4) {
                    Uploads::deleteLinked([
                        'id_module' => Modules::get('Articoli')['id'],
                        'id_record' => $articolo->id,
                    ]);

                    $database->update('mg_articoli', [
                        'immagine' => '',
                    ], [
                        'id' => $articolo->id,
                    ]);
                }

                $name = 'immagine_'.$articolo->id.'.'.Upload::getExtensionFromMimeType($file_content);

                $upload = Uploads::upload($file_content, [
                    'name' => 'Immagine',
                    'category' => 'Immagini',
                    'original_name' => $name,
                    'id_module' => Modules::get('Articoli')['id'],
                    'id_record' => $articolo->id,
                ], [
                    'thumbnails' => true,
                ]);
                $filename = $upload->filename;

                if ($record['import_immagine'] == 1 || $record['import_immagine'] == 2) {
                    if (!empty($filename)) {
                        $database->update('mg_articoli', [
                            'immagine' => $filename,
                        ], [
                            'id' => $articolo->id,
                        ]);
                    }
                }
            }
        }

        unset($record['import_immagine']);
        unset($record['anagrafica_listino']);
        unset($record['qta_minima']);
        unset($record['qta_massima']);
        unset($record['prezzo_listino']);
        unset($record['sconto_listino']);
        unset($record['dir']);
        unset($record['codice_fornitore']);
        unset($record['barcode_fornitore']);
        unset($record['descrizione_fornitore']);
        unset($record['id_fornitore']);

        // Salvataggio delle informazioni generali
        $articolo->fill($record);

        $articolo->fill([
            'id_categoria' => $categoria->id ?: $articolo['id_categoria'],
            'id_sottocategoria' => $sottocategoria->id ?: $articolo['id_sottocategoria'],
        ]);

        // Prezzo di vendita
        if (!empty($record['prezzo_vendita'])) {
            $articolo->setPrezzoVendita($record['prezzo_vendita'], $aliquota ? $aliquota->id : setting('Iva predefinita'));
        }

        $articolo->save();

        // Movimentazione della quantità registrata
        $anagrafica_azienda = Anagrafica::find(setting('Azienda predefinita'));
        $id_sede = 0;
        if (!empty($nome_sede)) {
            $sede = Sede::where('nomesede', $nome_sede)
                ->where('idanagrafica', $anagrafica_azienda->id)
                ->first();
            $id_sede = $sede->id;
        }

        if( isset($record['qta']) ) {
            $giacenze = $articolo->getGiacenze($record['data_qta']);
            $qta_movimento = $nuova_qta - $giacenze[$id_sede][0];

            $articolo->movimenta($qta_movimento, tr('Movimento da importazione'), new Carbon($record['data_qta']), true, [
                'idsede' => $id_sede,
            ]);
        }
    }

    public static function getExample()
    {
        return [
            ['Codice', 'Barcode', 'Immagine', 'Import immagine', 'Descrizione', 'Fornitore predefinito', 'Quantità', 'Unità di misura', 'Prezzo acquisto', 'Prezzo vendita', 'Peso lordo (KG)', 'Volume (M3)', 'Categoria', 'Sottocategoria', 'Ubicazione', 'Note', 'Anagrafica listino', 'Codice fornitore', 'Barcode fornitore', 'Descrizione fornitore', 'Qta minima', 'Qta massima', 'Prezzo listino', 'Sconto listino', 'Cliente/Fornitore listino'],
            ['00004', '719376861871', 'https://immagini.com/immagine.jpg', '1', 'Articolo', 'Mario Rossi', '10', 'Kg', '5.25', '12.72', '10.2', '500', 'Categoria4', 'Sottocategoria2', 'Scaffale 1', 'Articolo di prova', 'Mario Rossi', 'artforn01', '384574557484', 'Articolo di prova fornitore', '', '', '10', '5', 'Fornitore'],
            ['00004', '719376861871', 'https://immagini.com/immagine.jpg', '2', 'Articolo', 'Mario Rossi', '10', 'Kg', '5.25', '12.72', '10.2', '500', 'Categoria4', 'Sottocategoria2', 'Scaffale 1', 'Articolo di prova', 'Mario Rossi', 'artforn01', '384574557484', 'Articolo di prova fornitore', '1', '10', '9', '', 'Fornitore'],
            ['00004', '719376861871', 'https://immagini.com/immagine.jpg', '3', 'Articolo', 'Mario Rossi', '10', 'Kg', '5.25', '12.72', '10.2', '500', 'Categoria4', 'Sottocategoria2', 'Scaffale 1', 'Articolo di prova', 'Mario Rossi', 'artforn01', '384574557484', 'Articolo di prova fornitore', '11', '20', '8', '5', 'Fornitore'],
            ['00004', '719376861871', '', '', 'Articolo', 'Mario Rossi', '10', 'Kg', '5.25', '12.72', '10.2', '500', 'Categoria4', 'Sottocategoria2', 'Scaffale 1', 'Articolo di prova', 'Mario Verdi', '', '', '', '1', '10', '20', '10', 'Cliente'],
            [],
            ['Import immagine = 1 -> Permette di importare l\'immagine come principale dell\'articolo mantenendo gli altri allegati già presenti'],
            ['Import immagine = 2 -> Permette di importare l\'immagine come principale dell\'articolo rimuovendo tutti gli allegati presenti'],
            ['Import immagine = 3 -> Permette di importare l\'immagine come allegato dell\'articolo mantenendo gli altri allegati già presenti'],
            ['Import immagine = 4 -> Permette di importare l\'immagine come allegato dell\'articolo rimuovendo tutti gli allegati presenti'],
        ];
    }

    protected function aggiornaDettaglioPrezzi(Articolo $articolo, $dettagli)
    {
        // Listini
        $anagrafica = Anagrafica::where('ragione_sociale', $dettagli['anagrafica_listino'])->first();

        $dettagli['dir'] = strtolower($dettagli['dir']);
        if ($dettagli['dir'] == 'fornitore') {
            $dettagli['dir'] = 'uscita';
        } elseif ($dettagli['dir'] == 'cliente') {
            $dettagli['dir'] = 'entrata';
        } else {
            $dettagli['dir'] = null;
        }

        // Aggiungo Listino
        if (!empty($anagrafica) && !empty($dettagli['dir']) && $dettagli['prezzo_listino']) {
            $dettaglio_predefinito = DettaglioPrezzo::build($articolo, $anagrafica, $dettagli['dir']);
            $dettaglio_predefinito->sconto_percentuale = $dettagli['sconto_listino'];
            $dettaglio_predefinito->setPrezzoUnitario($dettagli['prezzo_listino']);

            if ($dettagli['qta_minima'] !== null && !empty($dettagli['qta_massima'])) {
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
        $listino_id_fornitore = DettaglioPrezzo::dettaglioPredefinito($articolo->id, $dettagli['id_fornitore'], 'uscita')->first();
        if (!empty($listino_id_fornitore)) {
            $prezzo_acquisto = $listino_id_fornitore->prezzo_unitario - ($listino_id_fornitore->prezzo_unitario*$listino_id_fornitore->sconto_percentuale) / 100;
            $articolo->prezzo_acquisto = $prezzo_acquisto;
            $articolo->id_fornitore = $dettagli['id_fornitore'];
            $articolo->save();
        }
    }
}
