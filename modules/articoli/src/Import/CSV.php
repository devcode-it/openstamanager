<?php

namespace Modules\Articoli\Import;

use Imports\CSVImport;
use Modules\Anagrafiche\Anagrafica;

class CSV extends CSVImport
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
                    'id_categoria',
                    'idcategoria',
                ],
            ],
            [
                'field' => 'id_sottocategoria',
                'label' => 'Sottocategoria',
                'names' => [
                    'Sottocategoria',
                    'id_sottocategoria',
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
                'label' => 'Fornitore',
                'names' => [
                    'id_fornitore',
                    'Id Fornitore',
                    'Fornitore',
                ],
            ],
            [
                'field' => 'idiva_vendita',
                'label' => 'Codice IVA vendita',
                'names' => [
                    'Codice IVA vendita',
                    'idiva_vendita',
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
        ];
    }

    public function import($record)
    {
        $database = database();
        $primary_key = $this->getPrimaryKey();

        $qta = $record['qta'];
        unset($record['qta']);

        $record['attivo'] = 1;

        // Fix per campi con contenuti derivati da query implicite
        if (!empty($record['id_fornitore'])) {
            $record['id_fornitore'] = $database->fetchOne('SELECT idanagrafica AS id FROM an_anagrafiche WHERE LOWER(ragione_sociale) = LOWER('.prepare($record['v']).')')['id'];
        }

        // Categorie
        if (!empty($record['id_categoria'])) {
            $rs_cat = $database->select('mg_categorie', 'id', [
                'nome' => $record['id_categoria'],
            ]);

            if (empty($rs_cat[0]['id'])) {
                $database->insert('mg_categorie', [
                    'nome' => $record['id_categoria'],
                ]);
                $record['id_categoria'] = $database->lastInsertedID();
            } else {
                $record['id_categoria'] = $rs_cat[0]['id'];
            }
        }

        // Sottocategorie
        if (!empty($record['id_sottocategoria'])) {
            $rs_cat2 = $database->select('mg_categorie', 'id', [
                'nome' => $record['id_sottocategoria'],
                'parent' => $record['id_categoria'],
            ]);

            if (empty($rs_cat2[0]['id'])) {
                $database->insert('mg_categorie', [
                    'nome' => $record['id_sottocategoria'],
                    'parent' => $record['id_categoria'],
                ]);
                $record['id_sottocategoria'] = $database->lastInsertedID();
            } else {
                $record['id_sottocategoria'] = $rs_cat2[0]['id'];
            }
        }

        // Um
        if (!empty($record['um'])) {
            $rs_um = $database->select('mg_unitamisura', 'id', [
                'valore' => $record['um'],
            ]);

            if (empty($rs_um[0]['id'])) {
                $database->insert('mg_unitamisura', [
                    'valore' => $record['um'],
                ]);
            }
        }

        // Codice --> ID IVA vendita
        if (!empty($record['idiva_vendita'])) {
            $rs_iva = $database->select('co_iva', 'id', [
                'codice' => $record['idiva_vendita'],
            ]);

            if (!empty($rs_iva[0]['id'])) {
                $record['idiva_vendita'] = $rs_iva[0]['id'];
            }
        }

        // Insert o update
        $insert = true;
        if (!empty($primary_key)) {
            $rs = $database->select('mg_articoli', $primary_key, [
                $primary_key => $record[$primary_key],
            ]);

            $insert = !in_array($record[$primary_key], $rs[0]);
        }

        // Insert
        if ($insert) {
            $record['id_categoria'] = (empty($record['id_categoria'])) ? 0 : $record['id_categoria'];
            $database->insert('mg_articoli', $record);
            add_movimento_magazzino($database->lastInsertedID(), $qta, [], 'Movimento da import', date());
        }
        // Update
        else {
            $database->update('mg_articoli', $record, [$primary_key => $record[$primary_key]]);

            $rs = $database->select('mg_articoli', 'id', [
                $primary_key => $record[$primary_key],
            ]);

            add_movimento_magazzino($rs[0]['id'], $qta, [], 'Movimento da import', date());
        }
    }

    public static function getExample()
    {
        return [
            ['Codice', 'Barcode', 'Descrizione', 'Fornitore', 'Quantità', 'Unità di misura', 'Prezzo acquisto', 'Prezzo vendita', 'Peso lordo (KG)', 'Volume (M3)', 'Categoria', 'Sottocategoria', 'Ubicazione', 'Note'],
            ['00004', '719376861871', 'Articolo', 'Mario Rossi', '10', 'Kg', '5,25', '12,72', '10,2', '500', 'Categoria4', 'Sottocategoria2', 'Scaffale 1', 'Articolo di prova'],
        ];
    }
}
