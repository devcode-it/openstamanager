<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'example':

        $module = filter('module');

        $list = [
            ['Codice', 'Barcode', 'Descrizione', 'Fornitore', 'Quantità', 'Unità di misura', 'Prezzo acquisto', 'Prezzo vendita', 'Peso lordo (KG)', 'Volume (M3)', 'Categoria', 'Sottocategoria', 'Ubicazione', 'Note'],
            ['00004', '719376861871', 'Articolo', 'Mario Rossi', '10', 'Kg', '5,25', '12,72', '10,2', '500', 'Categoria4', 'Sottocategoria2', 'Scaffale 1', 'Articolo di prova'],
        ];

        directory('../../files/'.$module);

        $fp = fopen('../../files/'.$module.'/'.$module.'.csv', 'w');

        foreach ($list as $fields) {
            fputcsv($fp, $fields, ';');
        }

        fclose($fp);
        exit;

    break;

    case 'import':

        foreach ($data as $key => $value) {
            if (!empty($value)) {
                $qta = $data[$key]['qta'];
                unset($data[$key]['qta']);

                $data[$key]['attivo'] = 1;
                if (!empty($data[$key]['prezzo_acquisto'])) {
                    $data[$key]['prezzo_acquisto'] = $data[$key]['prezzo_acquisto'];
                }
                if (!empty($data[$key]['prezzo_vendita'])) {
                    $data[$key]['prezzo_vendita'] = $data[$key]['prezzo_vendita'];
                }
                if (!empty($data[$key]['peso_lordo'])) {
                    $data[$key]['peso_lordo'] = $data[$key]['peso_lordo'];
                }
                if (!empty($data[$key]['volume'])) {
                    $data[$key]['volume'] = $data[$key]['volume'];
                }

                // Categorie
                if (!empty($data[$key]['id_categoria'])) {
                    $rs_cat = $dbo->select('mg_categorie', 'id', [
                        'nome' => $data[$key]['id_categoria'],
                    ]);

                    if (empty($rs_cat[0]['id'])) {
                        $dbo->insert('mg_categorie', [
                            'nome' => $data[$key]['id_categoria'],
                        ]);
                        $data[$key]['id_categoria'] = $dbo->lastInsertedID();
                    } else {
                        $data[$key]['id_categoria'] = $rs_cat[0]['id'];
                    }
                }

                // Sottocategorie
                if (!empty($data[$key]['id_sottocategoria'])) {
                    $rs_cat2 = $dbo->select('mg_categorie', 'id', [
                        'nome' => $data[$key]['id_sottocategoria'],
                        'parent' => $data[$key]['id_categoria'],
                    ]);

                    if (empty($rs_cat2[0]['id'])) {
                        $dbo->insert('mg_categorie', [
                            'nome' => $data[$key]['id_sottocategoria'],
                            'parent' => $data[$key]['id_categoria'],
                        ]);
                        $data[$key]['id_sottocategoria'] = $dbo->lastInsertedID();
                    } else {
                        $data[$key]['id_sottocategoria'] = $rs_cat2[0]['id'];
                    }
                }

                // Um
                if (!empty($data[$key]['um'])) {
                    $rs_um = $dbo->select('mg_unitamisura', 'id', [
                        'valore' => $data[$key]['um'],
                    ]);

                    if (empty($rs_um[0]['id'])) {
                        $dbo->insert('mg_unitamisura', [
                            'valore' => $data[$key]['um'],
                        ]);
                    }
                }

                // Codice --> ID IVA vendita
                if (!empty($data[$key]['idiva_vendita'])) {
                    $rs_iva = $dbo->select('co_iva', 'id', [
                        'codice' => $data[$key]['idiva_vendita'],
                    ]);

                    if (!empty($rs_iva[0]['id'])) {
                        $data[$key]['idiva_vendita'] = $rs_iva[0]['id'];
                    }
                }

                // Insert o update
                $insert = true;
                if (!empty($primary_key)) {
                    $rs = $dbo->select('mg_articoli', $primary_key, [
                        $primary_key => $data[$key][$primary_key],
                    ]);

                    $insert = !in_array($data[$key][$primary_key], $rs[0]);
                }

                // Insert
                if ($insert) {
                    $data[$key]['id_categoria'] = (empty($data[$key]['id_categoria'])) ? 0 : $data[$key]['id_categoria'];
                    $dbo->insert('mg_articoli', $data[$key]);
                    add_movimento_magazzino($dbo->lastInsertedID(), $qta, [], 'Movimento da import', date());
                }
                // Update
                else {
                    $dbo->update('mg_articoli', $data[$key], [$primary_key => $data[$key][$primary_key]]);

                    $rs = $dbo->select('mg_articoli', 'id', [
                        $primary_key => $data[$key][$primary_key],
                    ]);

                    add_movimento_magazzino($rs[0]['id'], $qta, [], 'Movimento da import', date());
                }

                unset($data[$key]);
            }
        }

        break;
}

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
        'query' => 'SELECT idanagrafica as result FROM an_anagrafiche WHERE LOWER(ragione_sociale) = LOWER(|value|)',
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
