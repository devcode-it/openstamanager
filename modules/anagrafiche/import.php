<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'example':

        $module = filter('module');

        $list = [
            ['Codice', 'Ragione sociale', 'Partita IVA', 'Nazione', 'Indirizzo', 'CAP', 'Città', 'Provincia', 'Telefono', 'Fax', 'Cellulare', 'Email', 'IBAN', 'Note', 'Tipologia'],
            ['00001', 'Cliente', '12345678910', 'ITALIA', 'Via Giuseppe Mazzini, 123', '12345', 'Este', 'PD', '786 543 21', '123 456 78', '321 123 456 78', 'email@cliente.it', 'IT60 X054 2811 1010 0000 0123 456', 'Anagrafica di esempio', 'Cliente'],
        ];

        directory('../../files/'.$module);

        $fp = fopen('../../files/'.$module.'/'.$module.'.csv', 'w');
        fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));

        foreach ($list as $fields) {
            fputcsv($fp, $fields, ';');
        }

        fclose($fp);
        exit;

    break;

    case 'import':

        foreach ($data as $key => $value) {
            if (!empty($value)) {
                $idtipoanagrafica = (array) $data[$key]['tipologia'];
                unset($data[$key]['tipologia']);

                // Insert o update
                $insert = true;
                if (!empty($primary_key)) {
                    $rs = $dbo->select('an_anagrafiche', $primary_key, [
                        $primary_key => $data[$key][$primary_key],
                    ]);

                    $insert = !in_array($data[$key][$primary_key], $rs[0]);
                }

                // Insert
                if ($insert) {
                    $dbo->insert('an_anagrafiche', $data[$key]);

                    // Campi extra
                    if (count($idtipoanagrafica) > 0) {
                        // Aggiornamento della tipologia di anagrafiche
                        $dbo->sync('an_tipianagrafiche_anagrafiche', [
                            'idanagrafica' => $dbo->lastInsertedID(),
                        ], [
                            'idtipoanagrafica' => (array) $idtipoanagrafica,
                        ]);
                    }
                }

                // Update
                else {
                    $dbo->update('an_anagrafiche', $data[$key], [$primary_key => $data[$key][$primary_key]]);
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
        'field' => 'ragione_sociale',
        'label' => 'Ragione sociale',
    ],
    [
        'field' => 'provincia',
        'label' => 'Provincia',
    ],
    [
        'field' => 'citta',
        'label' => 'Città',
    ],
    [
        'field' => 'telefono',
        'label' => 'Telefono',
    ],
    [
        'field' => 'indirizzo',
        'label' => 'Indirizzo',
    ],
    [
        'field' => 'cap',
        'label' => 'CAP',
    ],
    [
        'field' => 'cellulare',
        'label' => 'Cellulare',
    ],
    [
        'field' => 'fax',
        'label' => 'Fax',
    ],
    [
        'field' => 'email',
        'label' => 'Email',
    ],
    [
        'field' => 'codice_fiscale',
        'label' => 'Codice Fiscale',
    ],
    [
        'field' => 'piva',
        'label' => 'Partita IVA',
    ],
    [
        'field' => 'codiceiban',
        'label' => 'IBAN',
    ],
    [
        'field' => 'note',
        'label' => 'Note',
    ],
    [
        'field' => 'id_nazione',
        'label' => 'Nazione',
        'names' => [
            'Nazione',
            'id_nazione',
            'idnazione',
            'nazione',
        ],
        'query' => 'SELECT id as result FROM an_nazioni WHERE LOWER(nome) = LOWER(|value|)',
    ],
    [
        'field' => 'idagente',
        'label' => 'ID Agente',
    ],
    [
        'field' => 'idpagamento_vendite',
        'label' => 'ID Pagamento',
        'names' => [
            'Pagamento',
            'ID Pagamento',
            'id_pagamento',
            'idpagamento_vendite',
            'idpagamento',
        ],
    ],
    [
        'field' => 'tipologia',
        'label' => 'Tipologia',
        'names' => [
            'Tipologia',
            'tipologia',
            'idtipo',
        ],
        'query' => 'SELECT id as result FROM an_tipianagrafiche WHERE descrizione = |value|',
    ],
];
