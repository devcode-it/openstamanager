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
use Modules\Anagrafiche\Anagrafica;
use Modules\Anagrafiche\Sede;
use Modules\Articoli\Articolo;
use Modules\Articoli\Categoria;
use Modules\Iva\Aliquota;
use Plugins\DettagliArticolo\DettaglioPrezzo;

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
                'field' => 'descrizione',
                'label' => 'Descrizione',
            ],
            [
                'field' => 'qta',
                'label' => 'Quantità',
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
        $number = $this->csv->each(function ($row) {
            return true;
        });

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

            $database->query('DELETE FROM mg_prezzi_articoli WHERE id_articolo = '.prepare($articolo->id).' AND id_anagrafica = '.prepare($anagrafica->id));
        }
    }

    public function import($record)
    {
        $database = database();
        $primary_key = $this->getPrimaryKey();

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

        // Individuazione dell'IVA di vendita tramite il relativo Codice
        $aliquota = null;
        if (!empty($record['codice_iva_vendita'])) {
            $aliquota = Aliquota::where('codice', $record['codice_iva_vendita'])->first();
        }

        // Individuazione articolo e generazione
        $articolo = null;
        // Ricerca sulla base della chiave primaria se presente
        if (!empty($primary_key)) {
            $articolo = Articolo::where($primary_key, $record[$primary_key])->first();
        }
        if (empty($articolo)) {
            $articolo = Articolo::build($record['codice'], $record['descrizione'], $categoria, $sottocategoria);
        }

        $articolo->idiva_vendita = $aliquota->id;
        $articolo->attivo = 1;

        // Prezzo di vendita
        $articolo->setPrezzoVendita($record['prezzo_vendita'], $aliquota->id ? $aliquota->id : setting('Iva predefinita'));

        // Esportazione della quantità indicata
        $qta_registrata = (float) ($record['qta']);
        $nome_sede = $record['nome_sede'];

        // Salvataggio delle informazioni generali
        $articolo->fill([
            'codice' => $record['codice'],
            'descrizione' => $record['descrizione'],
            'prezzo_acquisto' => $record['prezzo_acquisto'],
            'peso_lordo' => $record['peso_lordo'],
            'volume' => $record['volume'],
            'barcode' => $record['barcode'],
            'id_fornitore' => $record['id_fornitore'],
            'ubicazione' => $record['ubicazione'],
            'note' => $record['note'],
        ]);
        $articolo->save();

        // Aggiornamento dettaglio prezzi
        $dettagli['anagrafica_listino'] = $record['anagrafica_listino'];
        $dettagli['qta_minima'] = $record['qta_minima'];
        $dettagli['qta_massima'] = $record['qta_massima'];
        $dettagli['prezzo_listino'] = $record['prezzo_listino'];
        $dettagli['sconto_listino'] = $record['sconto_listino'];
        $dettagli['dir'] = $record['dir'];
        $this->aggiornaDettaglioPrezzi($articolo, $dettagli);

        // Movimentazione della quantità registrata
        $giacenze = $articolo->getGiacenze();
        $anagrafica_azienda = Anagrafica::find(setting('Azienda predefinita'));
        $id_sede = 0;
        if (!empty($nome_sede)) {
            $sede = Sede::where('nomesede', $nome_sede)
                ->where('idanagrafica', $anagrafica_azienda->id)
                ->first();
            $id_sede = $sede->id;
        }

        $qta_movimento = $qta_registrata - $giacenze[$id_sede][0];

        $articolo->movimenta($qta_movimento, tr('Movimento da importazione'), new Carbon(), false, [
            'idsede' => $id_sede,
        ]);
    }

    public static function getExample()
    {
        return [
            ['Codice', 'Barcode', 'Descrizione', 'Fornitore predefinito', 'Quantità', 'Unità di misura', 'Prezzo acquisto', 'Prezzo vendita', 'Peso lordo (KG)', 'Volume (M3)', 'Categoria', 'Sottocategoria', 'Ubicazione', 'Note', 'Anagrafica listino', 'Qta minima', 'Qta massima', 'Prezzo listino', 'Sconto listino', 'Cliente/Fornitore listino'],
            ['00004', '719376861871', 'Articolo', 'Mario Rossi', '10', 'Kg', '5.25', '12.72', '10.2', '500', 'Categoria4', 'Sottocategoria2', 'Scaffale 1', 'Articolo di prova', 'Mario Rossi', '', '', '10', '5', 'Fornitore'],
            ['00004', '719376861871', 'Articolo', 'Mario Rossi', '10', 'Kg', '5.25', '12.72', '10.2', '500', 'Categoria4', 'Sottocategoria2', 'Scaffale 1', 'Articolo di prova', 'Mario Rossi', '1', '10', '9', '', 'Fornitore'],
            ['00004', '719376861871', 'Articolo', 'Mario Rossi', '10', 'Kg', '5.25', '12.72', '10.2', '500', 'Categoria4', 'Sottocategoria2', 'Scaffale 1', 'Articolo di prova', 'Mario Rossi', '11', '20', '8', '5', 'Fornitore'],
            ['00004', '719376861871', 'Articolo', 'Mario Rossi', '10', 'Kg', '5.25', '12.72', '10.2', '500', 'Categoria4', 'Sottocategoria2', 'Scaffale 1', 'Articolo di prova', 'Mario Verdi', '1', '10', '20', '10', 'Cliente'],
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

        if (!empty($anagrafica) && !empty($dettagli['dir'])) {
            $dettaglio_predefinito = DettaglioPrezzo::build($articolo, $anagrafica, $dettagli['dir']);
            $dettaglio_predefinito->sconto_percentuale = $dettagli['sconto_listino'];
            $dettaglio_predefinito->setPrezzoUnitario($dettagli['prezzo_listino']);

            if ($dettagli['qta_minima'] !== null && !empty($dettagli['qta_massima'])) {
                $dettaglio_predefinito->minimo = $dettagli['qta_minima'];
                $dettaglio_predefinito->massimo = $dettagli['qta_massima'];
            }

            $dettaglio_predefinito->save();
        }
    }
}
