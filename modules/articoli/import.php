<?php

include_once __DIR__.'/../../core.php';

include_once Modules::filepath('Articoli', 'modutil.php');

switch (post('op')) {
    case 'example':
        return [
            ['Codice', 'Descrizione', 'Quantità', 'Unità di misura', 'Prezzo acquisto', 'Prezzo vendita', 'Peso lordo (KG)', 'Volume (M3)', 'Categoria', 'Note'],
            ['00004', 'Articolo', '10', 'Kg', '5,25', '12,72', '10,2', '500', 'Categoria4', 'Articolo di prova'],
        ];
    break;

    case 'import':

        foreach ($data as $key => $value) {
            if (!empty($value)) {
                $qta = $data[$key]['qta'];
                unset($data[$key]['qta']);

                $data[$key]['attivo'] = 1;
                $data[$key]['prezzo_acquisto'] = $data[$key]['prezzo_acquisto'];
                $data[$key]['prezzo_vendita'] = $data[$key]['prezzo_vendita'];
                $data[$key]['peso_lordo'] = $data[$key]['peso_lordo'];
                $data[$key]['volume'] = $data[$key]['volume'];

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
        'field' => 'note',
        'label' => 'Note',
    ],
];
