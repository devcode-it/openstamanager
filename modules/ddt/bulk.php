<?php

include_once __DIR__.'/../../core.php';

include_once Modules::filepath('Fatture di vendita', 'modutil.php');

switch (post('op')) {
    case 'creafatturavendita':
        $iddocumento_cliente = [];
        $totale_n_ddt = 0;
        $dir = 'entrata';

        // Lettura righe selezionate
        for ($r = 0; $r < sizeof($id_records); ++$r) {
            $idiva = get_var('Iva predefinita');
            $idddt = $id_records[$r];

            $rs_idanagrafica = $dbo->fetchArray("SELECT idanagrafica FROM in_interventi WHERE id='".$id_records[$r]."'");
            $idanagrafica = $rs_idanagrafica[0]['idanagrafica'];

            $q = 'SELECT
					*, dt_righe_ddt.id AS idriga
                FROM
					dt_righe_ddt INNER JOIN dt_ddt ON dt_righe_ddt.idddt=dt_ddt.id
				WHERE
					idddt='.prepare($idddt).'
					AND idddt NOT IN (SELECT idddt FROM co_righe_documenti WHERE idddt IS NOT NULL)
				ORDER BY
					dt_ddt.data ASC';

            $rsi = $dbo->fetchArray($q);
            $n_ddt = sizeof($rsi);
            $totale_n_ddt += $n_ddt;

            // Proseguo solo se i ddt scelti sono fatturabili
            if ($n_ddt > 0) {
                //Se non c'è già una fattura appena creata per questo cliente, creo una fattura nuova
                if (empty($iddocumento_cliente[$idanagrafica])) {
                    $data = date('Y-m-d');
                    $dir = 'entrata';
                    $idtipodocumento = '2';

                    if (empty($_SESSION['m'.Modules::get('Fatture di vendita')['id']]['id_segment'])) {
                        $rs = $dbo->fetchArray('SELECT id  FROM zz_segments WHERE predefined = 1 AND id_module = '.prepare(Modules::get('Fatture di vendita')['id']).'LIMIT 0,1');
                        $_SESSION['m'.Modules::get('Fatture di vendita')['id']]['id_segment'] = $rs[0]['id'];
                    }

                    $id_segment = $_SESSION['m'.Modules::get('Fatture di vendita')['id']]['id_segment'];

                    $numero = get_new_numerofattura($data);

                    $numero_esterno = get_new_numerosecondariofattura($data);
                    $idconto = get_var('Conto predefinito fatture di vendita');

                    $campo = ($dir == 'entrata') ? 'idpagamento_vendite' : 'idpagamento_acquisti';

                    // Tipo di pagamento predefinito dall'anagrafica
                    $query = 'SELECT id FROM co_pagamenti WHERE id=(SELECT '.$campo.' AS pagamento FROM an_anagrafiche WHERE idanagrafica='.prepare($idanagrafica).')';
                    $rs = $dbo->fetchArray($query);
                    $idpagamento = $rs[0]['id'];

                    // Se alla non è stato associato un pagamento predefinito al cliente, leggo il pagamento dalle impostazioni
                    if ($idpagamento == '') {
                        $idpagamento = get_var('Tipo di pagamento predefinito');
                    }

                    // Creazione nuova fattura
                    $dbo->query('INSERT INTO co_documenti (numero, numero_esterno, idanagrafica, idconto, idtipodocumento, idpagamento, data, idstatodocumento, idsede) VALUES ('.prepare($numero).', '.prepare($numero_esterno).', '.prepare($idanagrafica).', '.prepare($idconto).', '.prepare($idtipodocumento).', '.prepare($idpagamento).', '.prepare($data).", (SELECT `id` FROM `co_statidocumento` WHERE `descrizione`='Bozza'), (SELECT idsede_fatturazione FROM an_anagrafiche WHERE idanagrafica=".prepare($idanagrafica).') )');
                    $iddocumento = $dbo->lastInsertedID();
                    $iddocumento_cliente[$idanagrafica] = $iddocumento;
                }

                // Inserimento righe
                for ($i = 0; $i < sizeof($rsi); ++$i) {
                    $qta = $rsi[$i]['qta'] - $rsi[$i]['qta_evasa'];

                    if ($qta > 0) {
                        $dbo->query('
							INSERT INTO co_righe_documenti(
									iddocumento,
									idarticolo,
									idddt,
									idiva,
									desc_iva,
									iva,
									iva_indetraibile,
									is_descrizione,
									descrizione,
									subtotale,
									sconto,
									sconto_unitario,
									sconto_prc,
									tipo_sconto,
									idgruppo,
									abilita_serial,
									um,
									qta,
									`order`)
								VALUES(
									'.$iddocumento_cliente[$idanagrafica].',
									'.prepare($rsi[$i]['idarticolo']).',
									'.prepare($rsi[$i]['idddt']).',
									'.prepare($rsi[$i]['idiva']).',
									'.prepare($rsi[$i]['desc_iva']).',
									'.prepare($rsi[$i]['iva']).',
									'.prepare($rsi[$i]['iva_indetraibile']).',
									'.prepare($rsi[$i]['is_descrizione']).',
									'.prepare($rsi[$i]['descrizione']).',
									'.prepare($rsi[$i]['subtotale']).',
									'.prepare($rsi[$i]['sconto']).',
									'.prepare($rsi[$i]['sconto_unitario']).',
									'.prepare($rsi[$i]['sconto_prc']).',
									'.prepare($rsi[$i]['tipo_sconto']).',
									'.prepare($rsi[$i]['idgruppo']).',
									'.prepare($rsi[$i]['abilita_serial']).',
									'.prepare($rsi[$i]['um']).',
									'.prepare($qta).',
									(SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento='.prepare($iddocumento).')
								)');

                        // Aggiorno la quantità evasa
                        $dbo->query('UPDATE dt_righe_ddt SET qta_evasa = qta WHERE id='.prepare($rsi[$i]['idriga']));

                        // Aggiorno lo stato ddt
                        $dbo->query('UPDATE dt_ddt SET idstatoddt = (SELECT id FROM dt_statiddt WHERE descrizione="Fatturato") WHERE id='.prepare($rsi[$i]['idddt']));
                    }

                    // Ricalcolo inps, ritenuta e bollo
                    if ($dir == 'entrata') {
                        ricalcola_costiagg_fattura($iddocumento_cliente[$idanagrafica]);
                    } else {
                        ricalcola_costiagg_fattura($iddocumento_cliente[$idanagrafica], 0, 0, 0);
                    }
                }
            }
        }

        if ($totale_n_ddt > 0) {
            $_SESSION['infos'][] = tr('_NUM_ ddt fatturati!', [
                '_NUM_' => $totale_n_ddt,
            ]);
        } else {
            $_SESSION['warnings'][] = tr('Nessun ddt fatturato!');
        }

    break;

    case 'delete-bulk':

        if ($debug) {
            foreach ($id_records as $id) {
                $dbo->query('DELETE  FROM dt_ddt  WHERE id = '.prepare($id).Modules::getAdditionalsQuery($id_module));
                $dbo->query('DELETE FROM dt_righe_ddt WHERE idddt='.prepare($id).Modules::getAdditionalsQuery($id_module));
                $dbo->query('DELETE FROM mg_movimenti WHERE idddt='.prepare($id).Modules::getAdditionalsQuery($id_module));
            }

            $_SESSION['infos'][] = tr('Ddt eliminati!');
        } else {
            $_SESSION['warnings'][] = tr('Procedura in fase di sviluppo. Nessuna modifica apportata.');
        }

    break;
}

return [
    'delete-bulk' => tr('Elimina selezionati'),

    'creafatturavendita' => [
        'text' => tr('Crea fattura'),
        'data' => [
            'msg' => tr('Vuoi davvero creare una fattura per questi interventi?'),
            'button' => tr('Procedi'),
            'class' => 'btn btn-lg btn-warning',
            'blank' => false,
        ],
    ],
];
