<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'import':
		
		foreach ($data as $key => $value) {
				
			if (!empty($value)){
				
				unset($value['tipologia']);

				$dbo->insert('an_anagrafiche', $data[$key]);
				unset($data[$key]);
				
				//campi extra
				if (!empty($data[$key]['tipologia'])){
					// Aggiornamento della tipologia di anagrafiche
					$dbo->sync('an_tipianagrafiche_anagrafiche', [
						'idanagrafica' => $dbo->lastInsertedID(),
					], [
						'idtipoanagrafica' => (array) $data[$key]['tipologia'],
					]);
				}
			
			}
				
		}
   
		   
        break;
		

}

return [
    [
        'field' => 'codice',
        'label' => 'Codice',
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
        'other' => 'nazione',
        'query' => 'SELECT id as result FROM an_nazioni WHERE LOWER(nome) = LOWER(|value|)',
    ],
    [
        'field' => 'idagente',
        'label' => 'ID Agente',
    ],
    [
        'field' => 'idpagamento_vendite',
        'label' => 'ID Pagamento',
        'other' => 'idpagamento',
    ],
    [
        'field' => 'tipologia',
        'label' => 'Tipologia',
        'other' => 'idtipo',
        'query' => 'SELECT idtipoanagrafica as result FROM an_tipianagrafiche WHERE descrizione = |value|',
    ],
];
