<?php

include_once __DIR__.'/../../core.php';

include_once Modules::filepath('Fatture di vendita', 'modutil.php');

switch (post('op')) {
    case 'crea_fattura':
        $id_documento_cliente = [];
        $totale_n_ddt = 0;

        // Informazioni della fattura
        $tipo_documento = $dbo->selectOne('co_tipidocumento', 'id', ['descrizione' => 'Fattura immediata di vendita'])['id'];
        $dir = 'entrata';
        $idiva = get_var('Iva predefinita');
        $data = date('Y-m-d');

        // Segmenti
        $id_fatture = Modules::get('Fatture di vendita')['id'];
        if (!isset($_SESSION['module_'.$id_fatture]['id_segment'])) {
            $segments = Modules::getSegments($id_fatture);
            $_SESSION['module_'.$id_fatture]['id_segment'] = isset($segments[0]['id']) ? $segments[0]['id'] : null;
        }
        $id_segment = $_SESSION['m'.$id_fatture]['id_segment'];

        // Lettura righe selezionate
        foreach ($id_records as $id) {
            $id_anagrafica = $dbo->selectOne('dt_ddt', 'idanagrafica', ['id' => $id])['idanagrafica'];

            $righe = $dbo->fetchArray('SELECT * FROM dt_righe_ddt WHERE idddt='.prepare($id).' AND idddt NOT IN (SELECT idddt FROM co_righe_documenti WHERE idddt IS NOT NULL)');

            // Proseguo solo se i ddt scelti sono fatturabili
            if (!empty($righe)) {
                $id_documento = $id_documento_cliente[$id_anagrafica];

                // Se non c'è già una fattura appena creata per questo cliente, creo una fattura nuova
                if (empty($id_documento)) {
                    $numero = get_new_numerofattura($data);
                    $numero_esterno = get_new_numerosecondariofattura($data);

                    $idconto = setting('Conto predefinito fatture di vendita');

                    $campo = ($dir == 'entrata') ? 'idpagamento_vendite' : 'idpagamento_acquisti';

                    // Tipo di pagamento predefinito dall'anagrafica
                    $query = 'SELECT id FROM co_pagamenti WHERE id=(SELECT '.$campo.' AS pagamento FROM an_anagrafiche WHERE idanagrafica='.prepare($id_anagrafica).')';
                    $rs = $dbo->fetchArray($query);
                    $idpagamento = $rs[0]['id'];

                    // Se alla non è stato associato un pagamento predefinito al cliente, leggo il pagamento dalle impostazioni
                    if (empty($idpagamento)) {
                        $idpagamento = setting('Tipo di pagamento predefinito');
                    }

                    // Creazione nuova fattura
                    $dbo->insert('co_documenti', [
                        'numero' => $numero,
                        'numero_esterno' => $numero_esterno,
                        'idanagrafica' => $id_anagrafica,
                        'idconto' => $idconto,
                        'idtipodocumento' => $tipo_documento,
                        'idpagamento' => $idpagamento,
                        'data' => $data,
                        'id_segment' => $id_segment,
                        '#idstatodocumento' => "(SELECT `id` FROM `co_statidocumento` WHERE `descrizione`='Bozza')",
                        '#idsede' => 'IFNULL((SELECT idsede_fatturazione FROM an_anagrafiche WHERE idanagrafica='.prepare($id_anagrafica).'), 0)',
                    ]);

                    $id_documento = $dbo->lastInsertedID();
                    $id_documento_cliente[$id_anagrafica] = $id_documento;
                }

                // Inserimento righe
                foreach ($righe as $riga) {
                    ++$totale_n_ddt;

                    $qta = $riga['qta'] - $riga['qta_evasa'];

                    if ($qta > 0) {
                        $dbo->insert('co_righe_documenti', [
                            'iddocumento' => $id_documento,
                            'idarticolo' => $riga['idarticolo'],
                            'idddt' => $id,
                            'idiva' => $riga['idiva'],
                            'desc_iva' => $riga['desc_iva'],
                            'iva' => $riga['iva'],
                            'iva_indetraibile' => $riga['iva_indetraibile'],
                            'descrizione' => $riga['descrizione'],
                            'is_descrizione' => $riga['is_descrizione'],
                            'subtotale' => $riga['subtotale'],
                            'sconto' => $riga['sconto'],
                            'sconto_unitario' => $riga['sconto_unitario'],
                            'tipo_sconto' => $riga['tipo_sconto'],
                            'um' => $riga['um'],
                            'qta' => $qta,
                            'abilita_serial' => $riga['abilita_serial'],
                            '#order' => '(SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento='.prepare($id_documento).')',
                        ]);
                        $id_riga_documento = $dbo->lastInsertedID();

                        // Copia dei serial tra le righe
                        if (!empty($riga['idarticolo'])) {
                            $dbo->query('INSERT INTO mg_prodotti (id_riga_documento, id_articolo, dir, serial, lotto, altro) SELECT '.prepare($id_riga_documento).', '.prepare($riga['idarticolo']).', '.prepare($dir).', serial, lotto, altro FROM mg_prodotti AS t WHERE id_riga_ddt='.prepare($riga['id']));
                        }

                        // Aggiorno la quantità evasa
                        $dbo->query('UPDATE dt_righe_ddt SET qta_evasa = qta WHERE id='.prepare($riga['id']));

                        // Aggiorno lo stato ddt
                        $dbo->query('UPDATE dt_ddt SET idstatoddt = (SELECT id FROM dt_statiddt WHERE descrizione="Fatturato") WHERE id='.prepare($id));
                    }

                    // Ricalcolo inps, ritenuta e bollo
                    ricalcola_costiagg_fattura($id_documento);
                }
            }
        }

        if ($totale_n_ddt > 0) {
            flash()->info(tr('_NUM_ ddt fatturati!', [
                '_NUM_' => $totale_n_ddt,
            ]));
        } else {
            flash()->warning(tr('Nessun ddt fatturato!'));
        }

    break;

    case 'delete-bulk':

        if ($debug) {
            foreach ($id_records as $id) {
                $dbo->query('DELETE  FROM dt_ddt  WHERE id = '.prepare($id).Modules::getAdditionalsQuery($id_module));
                $dbo->query('DELETE FROM dt_righe_ddt WHERE idddt='.prepare($id).Modules::getAdditionalsQuery($id_module));
                $dbo->query('DELETE FROM mg_movimenti WHERE idddt='.prepare($id).Modules::getAdditionalsQuery($id_module));
            }

            flash()->info(tr('Ddt eliminati!'));
        } else {
            flash()->warning(tr('Procedura in fase di sviluppo. Nessuna modifica apportata.'));
        }

    break;
}

$operations = [
    'delete-bulk' => tr('Elimina selezionati'),
];

if (Modules::get('Ddt di vendita')['id'] == $id_module) {
    $operations['crea_fattura'] = [
        'text' => tr('Crea fattura'),
        'data' => [
            'msg' => tr('Vuoi davvero creare una fattura per questi interventi?'),
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-warning',
            'blank' => false,
        ],
    ];
}

return $operations;
