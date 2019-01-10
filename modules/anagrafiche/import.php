<?php

include_once __DIR__.'/../../core.php';

use Modules\Anagrafiche\Anagrafica;

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
        $sede_fields = [
            'piva',
            'codice_fiscale',
            'indirizzo',
            'indirizzo2',
            'citta',
            'cap',
            'provincia',
            'km',
            'id_nazione',
            'telefono',
            'fax',
            'cellulare',
            'email',
            'idzona',
            'gaddress',
            'lat',
            'lng',
        ];

        foreach ($data as $key => $value) {
            if (!empty($value)) {
                $id_tipo_anagrafica = (array) $data[$key]['tipologia'];
                unset($data[$key]['tipologia']);

                $dati_anagrafica = $data[$key];
                $dati_sede = [];
                foreach ($sede_fields as $field) {
                    $dati_sede[$field] = $dati_anagrafica[$field];
                    unset($dati_anagrafica[$field]);
                }

                // Ricerca di eventuale anagrafica corrispondente
                if (!empty($primary_key)) {
                    //impedisco di aggiornare la mia anagrafica azienda
                    if ($dati_anagrafica[$primary_key] != setting('Azienda predefinita'))
                        $anagrafica = Anagrafica::where($primary_key, '=', $dati_anagrafica[$primary_key])->first();
                }

                if (empty($anagrafica)) {
                    $anagrafica = Anagrafica::build($dati_anagrafica['ragione_sociale']);
                }

                $anagrafica->fill($dati_anagrafica);
                $anagrafica->tipologie = (array) $id_tipo_anagrafica;
                $anagrafica->save();

                $sede = $anagrafica->sedeLegale;
                $sede->fill($dati_sede);
                $sede->save();

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
        'field' => 'indirizzo2',
        'label' => 'Civico',
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
        'field' => 'data_nascita',
        'label' => 'Data di nascita',
    ],
    [
        'field' => 'luogo_nascita',
        'label' => 'Luogo di nascita',
    ],
    [
        'field' => 'sesso',
        'label' => 'Sesso',
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
        'query' => 'SELECT idtipoanagrafica as result FROM an_tipianagrafiche WHERE descrizione = |value|',
    ],
];
