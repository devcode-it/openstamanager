<?php

include_once __DIR__.'/../../core.php';

include_once Modules::filepath('Fatture di vendita', 'modutil.php');

switch (post('op')) {
    case 'add':
        $idanagrafica = post('idanagrafica');
        $nome = post('nome');

        // Verifico se c'è già un agente collegato all'anagrafica cliente, così lo imposto già
        $q = 'SELECT idagente FROM an_anagrafiche WHERE idanagrafica='.prepare($idanagrafica);
        $rs = $dbo->fetchArray($q);
        $idagente = $rs[0]['idagente'];

        // Codice contratto: calcolo il successivo in base al formato specificato
        $rs = $dbo->fetchArray('SELECT numero FROM co_contratti ORDER BY id DESC LIMIT 0,1');
        $numero = Util\Generator::generate(setting('Formato codice contratti'), $rs[0]['numero']);

        // Uso il tipo di pagamento specificato in anagrafica se c'è, altrimenti quello di default
        $rsa = $dbo->fetchArray('SELECT idpagamento_vendite AS idpagamento FROM an_anagrafiche WHERE idanagrafica='.prepare($idanagrafica));

         $idpagamento = (!empty($rsa[0]['idpagamento'])) ? $rsa[0]['idpagamento'] : setting('Tipo di pagamento predefinito');

        if (post('idanagrafica') !== null) {
            $dbo->query('INSERT INTO co_contratti(idanagrafica, nome, numero, idagente, idpagamento, idstato, data_bozza) VALUES ('.prepare($idanagrafica).', '.prepare($nome).', '.prepare($numero).', '.prepare($idagente).', '.prepare($idpagamento).", (SELECT `id` FROM `co_staticontratti` WHERE `descrizione`='Bozza'), NOW())");
            $id_record = $dbo->lastInsertedID();

            // Aggiunta associazioni costi unitari al contratto
            $rsi = $dbo->fetchArray('SELECT * FROM in_tipiintervento WHERE (costo_orario!=0 OR costo_km!=0 OR costo_diritto_chiamata!=0)');

            for ($i = 0; $i < sizeof($rsi); ++$i) {
                $dbo->query('INSERT INTO co_contratti_tipiintervento(idcontratto, idtipointervento, costo_ore, costo_km, costo_dirittochiamata, costo_ore_tecnico, costo_km_tecnico, costo_dirittochiamata_tecnico) VALUES('.prepare($id_record).', '.prepare($rsi[$i]['idtipointervento']).', '.prepare($rsi[$i]['costo_orario']).', '.prepare($rsi[$i]['costo_km']).', '.prepare($rsi[$i]['costo_diritto_chiamata']).', '.prepare($rsi[$i]['costo_orario_tecnico']).', '.prepare($rsi[$i]['costo_km_tecnico']).', '.prepare($rsi[$i]['costo_diritto_chiamata_tecnico']).')');
            }

            flash()->info(tr('Aggiunto contratto numero _NUM_!', [
                '_NUM_' => $numero,
            ]));
        }

        break;

    case 'update':
        if (post('id_record') !== null) {
            $idstato = post('idstato');
            $idanagrafica = post('idanagrafica');
            $idsede = post('idsede');
            $nome = post('nome');
            $idagente = post('idagente');
            $idpagamento = post('idpagamento');
            $numero = post('numero');

            // Se non specifico un budget me lo vado a ricalcolare
            if ($budget != '') {
                $budget = post('budget');
            } else {
                $q = "SELECT (SELECT SUM(subtotale) FROM co_righe_contratti GROUP BY idcontratto HAVING idcontratto=co_contratti.id) AS 'budget' FROM co_contratti WHERE id=".prepare($id_record);
                $rs = $dbo->fetchArray($q);
                $budget = $rs[0]['budget'];
            }

            $data_bozza = post('data_bozza');
            $data_accettazione = post('data_accettazione');
            $data_rifiuto = post('data_rifiuto');
            $data_conclusione = post('data_conclusione');
            $rinnovabile = post('rinnovabile');

            $giorni_preavviso_rinnovo = post('giorni_preavviso_rinnovo');
            $validita = post('validita');
            $idreferente = post('idreferente');
            $esclusioni = post('esclusioni');
            $descrizione = post('descrizione');
            // $ore_lavoro = post('ore_lavoro');

            $costo_orario = post('costo_orario');
            $costo_km = post('costo_km');
            $costo_diritto_chiamata = post('costo_diritto_chiamata');

            $query = 'UPDATE co_contratti SET idanagrafica='.prepare($idanagrafica).', idsede='.prepare($idsede).', idstato='.prepare($idstato).', nome='.prepare($nome).', idagente='.prepare($idagente).', idpagamento='.prepare($idpagamento).', numero='.prepare($numero).', budget='.prepare($budget).', idreferente='.prepare($idreferente).', validita='.prepare($validita).', data_bozza='.prepare($data_bozza).', data_accettazione='.prepare($data_accettazione).', data_rifiuto='.prepare($data_rifiuto).', data_conclusione='.prepare($data_conclusione).', rinnovabile='.prepare($rinnovabile).', giorni_preavviso_rinnovo='.prepare($giorni_preavviso_rinnovo).', esclusioni='.prepare($esclusioni).', descrizione='.prepare($descrizione).' WHERE id='.prepare($id_record);
            // costo_diritto_chiamata='.prepare($costo_diritto_chiamata).', ore_lavoro='.prepare($ore_lavoro).', costo_orario='.prepare($costo_orario).', costo_km='.prepare($costo_km).'

            $dbo->query($query);

            // Aggiornamento sconto
            $tipo_sconto = post('tipo_sconto_generico');
            $sconto = post('sconto_generico');

            $dbo->update('co_contratti', [
                'tipo_sconto_globale' => $tipo_sconto,
                'sconto_globale' => $sconto,
            ], ['id' => $id_record]);

            aggiorna_sconto([
                'parent' => 'co_contratti',
                'row' => 'co_righe_contratti',
            ], [
                'parent' => 'id',
                'row' => 'idcontratto',
            ], $id_record);

            $dbo->query('DELETE FROM my_impianti_contratti WHERE idcontratto='.prepare($id_record));
            foreach ((array) post('matricolaimpianto') as $matricolaimpianto) {
                $dbo->query('INSERT INTO my_impianti_contratti(idcontratto,idimpianto) VALUES('.prepare($id_record).', '.prepare($matricolaimpianto).')');
            }

            // Salvataggio costi attività unitari del contratto
            foreach (post('costo_ore') as $idtipointervento => $valore) {
                $rs = $dbo->fetchArray('SELECT * FROM co_contratti_tipiintervento WHERE idcontratto='.prepare($id_record).' AND idtipointervento='.prepare($idtipointervento));

                // Se non esiste il record lo inserisco...
                if (sizeof($rs) == 0) {
                    // Se almeno un valore è diverso da 0 inserisco l'importo...
                    if (post('costo_ore')[$idtipointervento] != 0 || post('costo_km')[$idtipointervento] != 0 || post('costo_dirittochiamata')[$idtipointervento] != 0) {
                        $dbo->query('INSERT INTO co_contratti_tipiintervento(idcontratto, idtipointervento, costo_ore, costo_km, costo_dirittochiamata, costo_ore_tecnico, costo_km_tecnico, costo_dirittochiamata_tecnico) VALUES('.prepare($id_record).', '.prepare($idtipointervento).', '.prepare(post('costo_ore')[$idtipointervento]).', '.prepare(post('costo_km')[$idtipointervento]).', '.prepare(post('costo_dirittochiamata')[$idtipointervento]).', '.prepare(post('costo_ore_tecnico')[$idtipointervento]).', '.prepare(post('costo_km_tecnico')[$idtipointervento]).', '.prepare(post('costo_dirittochiamata_tecnico')[$idtipointervento]).')');
                    }
                }

                // ...altrimenti...
                else {
                    // Aggiorno il nuovo valore se è diverso da 0...
                    if (post('costo_ore')[$idtipointervento] != 0 || post('costo_km')[$idtipointervento] != 0 || post('costo_dirittochiamata')[$idtipointervento] != 0) {
                        $dbo->query('UPDATE co_contratti_tipiintervento SET costo_ore='.prepare(post('costo_ore')[$idtipointervento]).', costo_km='.prepare(post('costo_km')[$idtipointervento]).', costo_dirittochiamata='.prepare(post('costo_dirittochiamata')[$idtipointervento]).', costo_ore_tecnico='.prepare(post('costo_ore_tecnico')[$idtipointervento]).', costo_km_tecnico='.prepare(post('costo_km_tecnico')[$idtipointervento]).', costo_dirittochiamata_tecnico='.prepare(post('costo_dirittochiamata_tecnico')[$idtipointervento]).' WHERE idcontratto='.prepare($id_record).' AND idtipointervento='.prepare($idtipointervento));
                    }

                    // ...altrimenti cancello l'eventuale riga
                    else {
                        $dbo->query('DELETE FROM co_contratti_tipiintervento WHERE idcontratto='.prepare($id_record).' AND idtipointervento='.prepare($idtipointervento));
                    }
                }
            }

            flash()->info(tr('Contratto modificato correttamente!'));
        }

        break;

    // Aggiungo una riga al contratto
    case 'addriga':
        $idiva = post('idiva');
        $idarticolo = post('idarticolo');
        $descrizione = post('descrizione');

        $qta = post('qta');
        $prezzo = post('prezzo');

        // Calcolo dello sconto
        $sconto_unitario = post('sconto');
        $tipo_sconto = post('tipo_sconto');
        $sconto = calcola_sconto([
            'sconto' => $sconto_unitario,
            'prezzo' => $prezzo,
            'tipo' => $tipo_sconto,
            'qta' => $qta,
        ]);

        $subtot = $prezzo * $qta;

        $um = post('um');

        // Lettura iva dell'articolo
        $rs2 = $dbo->fetchArray('SELECT percentuale, descrizione, indetraibile FROM co_iva WHERE id='.prepare($idiva));
        $iva = ($prezzo * $qta - $sconto) / 100 * $rs2[0]['percentuale'];
        $iva_indetraibile = $iva / 100 * $rs2[0]['indetraibile'];
        $desc_iva = $rs2[0]['descrizione'];

        $dbo->query('INSERT INTO co_righe_contratti(idcontratto, idarticolo, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, um, qta, sconto, sconto_unitario, tipo_sconto, is_descrizione, `order`) VALUES ('.prepare($id_record).', '.prepare($idarticolo).', '.prepare($idiva).', '.prepare($desc_iva).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare($descrizione).', '.prepare($subtot).', '.prepare($um).', '.prepare($qta).', '.prepare($sconto).', '.prepare($sconto_unitario).', '.prepare($tipo_sconto).', '.prepare(empty($qta)).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_contratti AS t WHERE idcontratto='.prepare($id_record).'))');

        // Messaggi informativi
        if (!empty($idarticolo)) {
            flash()->info(tr('Articolo aggiunto!'));
        } elseif (!empty($qta)) {
            flash()->info(tr('Riga aggiunta!'));
        } else {
            flash()->info(tr('Riga descrittiva aggiunta!'));
        }

        break;

    case 'editriga':
        $idriga = post('idriga');

        $rs = $dbo->fetchArray("SELECT * FROM co_righe_contratti WHERE id='".$idriga."'");
        $is_descrizione = $rs[0]['is_descrizione'];

        $idarticolo = post('idarticolo');
        $descrizione = post('descrizione');

        $qta = post('qta');
        $prezzo = post('prezzo');
        $subtot = $prezzo * $qta;

        // Calcolo dello sconto
        $sconto_unitario = post('sconto');
        $tipo_sconto = post('tipo_sconto');
        $sconto = calcola_sconto([
            'sconto' => $sconto_unitario,
            'prezzo' => $prezzo,
            'tipo' => $tipo_sconto,
            'qta' => $qta,
        ]);

        $idiva = post('idiva');
        $um = post('um');

        // Calcolo iva
        $query = 'SELECT percentuale, descrizione, indetraibile FROM co_iva WHERE id='.prepare($idiva);
        $rs = $dbo->fetchArray($query);
        $iva = ($subtot - $sconto) / 100 * $rs[0]['percentuale'];
        $iva_indetraibile = $iva / 100 * $rs[0]['indetraibile'];
        $desc_iva = $rs[0]['descrizione'];

        // Modifica riga generica sul documento
        if ($is_descrizione == 0) {
            $query = 'UPDATE co_righe_contratti SET idarticolo='.prepare($idarticolo).', idiva='.prepare($idiva).', desc_iva='.prepare($desc_iva).', iva='.prepare($iva).', iva_indetraibile='.prepare($iva_indetraibile).', descrizione='.prepare($descrizione).', subtotale='.prepare($subtot).', sconto='.prepare($sconto).', sconto_unitario='.prepare($sconto_unitario).', tipo_sconto='.prepare($tipo_sconto).', um='.prepare($um).', qta='.prepare($qta).' WHERE id='.prepare($idriga);
        } else {
            $query = 'UPDATE co_righe_contratti SET descrizione='.prepare($descrizione).' WHERE id='.prepare($idriga);
        }
        $dbo->query($query);

        flash()->info(tr('Riga modificata!'));

        break;

    // Eliminazione riga
    case 'delriga':
        if (post('idriga') !== null) {
            $idriga = post('idriga');

            $query = 'DELETE FROM `co_righe_contratti` WHERE idcontratto='.prepare($id_record).' AND id='.prepare($idriga);

            if ($dbo->query($query)) {
                flash()->info(tr('Riga eliminata!'));
            }
        }

        // Ricalcolo il budget
        $dbo->query('UPDATE co_contratti SET budget=( SELECT SUM(subtotale) FROM co_righe_contratti GROUP BY idcontratto HAVING idcontratto=co_contratti.id ) WHERE id='.prepare($id_record));

        break;

    // Scollegamento intervento da contratto
    case 'unlink':
        if (get('idcontratto') !== null && get('idintervento') !== null) {
            $idcontratto = get('idcontratto');
            $idintervento = get('idintervento');

            $query = 'DELETE FROM `co_contratti_promemoria` WHERE idcontratto='.prepare($idcontratto).' AND idintervento='.prepare($idintervento);
            $dbo->query($query);

            flash()->info(tr('Intervento _NUM_ rimosso!', [
                '_NUM_' => $idintervento,
            ]));
        }
        break;

    case 'update_position':
        $start = filter('start');
        $end = filter('end');
        $id = filter('id');

        if ($start > $end) {
            $dbo->query('UPDATE `co_righe_contratti` SET `order`=`order` + 1 WHERE `order`>='.prepare($end).' AND `order`<'.prepare($start).' AND `idcontratto`='.prepare($id_record));
            $dbo->query('UPDATE `co_righe_contratti` SET `order`='.prepare($end).' WHERE id='.prepare($id));
        } elseif ($end != $start) {
            $dbo->query('UPDATE `co_righe_contratti` SET `order`=`order` - 1 WHERE `order`>'.prepare($start).' AND `order`<='.prepare($end).' AND `idcontratto`='.prepare($id_record));
            $dbo->query('UPDATE `co_righe_contratti` SET `order`='.prepare($end).' WHERE id='.prepare($id));
        }

        break;

    // eliminazione contratto
    case 'delete':
        $dbo->query('DELETE FROM co_contratti WHERE id='.prepare($id_record));
        $dbo->query('DELETE FROM co_contratti_promemoria WHERE idcontratto='.prepare($id_record));
        $dbo->query('DELETE FROM co_righe_contratti WHERE idcontratto='.prepare($id_record));

        flash()->info(tr('Contratto eliminato!'));

        break;
}

// Rinnovo contratto
switch (get('op')) {
    case 'renew':
        $rs = $dbo->fetchArray('SELECT *, DATEDIFF(data_conclusione, data_accettazione) AS giorni FROM co_contratti WHERE id='.prepare($id_record));

        if (sizeof($rs) == 1) {
            // Verifico se il rinnovo contratto è un numero accettabile con la differenza di data inizio e data fine
            if ($rs[0]['giorni'] > 0 && $rs[0]['giorni'] < 365 * 10) {
                $giorni_add = $rs[0]['giorni'];
            } else {
                $giorni_add = 0;
            }

            // Calcolo numero successivo contratti
            $rs2 = $dbo->fetchArray('SELECT MAX(CAST(numero AS UNSIGNED)) AS maxn FROM co_contratti');
            $numero = $rs2[0]['maxn'] + 1;

            if ($dbo->query('INSERT INTO co_contratti(numero, nome, idagente, data_bozza, data_accettazione, data_rifiuto, data_conclusione, rinnovabile, giorni_preavviso_rinnovo, budget, descrizione, idstato, idreferente, validita, esclusioni, idanagrafica, idpagamento, costo_diritto_chiamata, ore_lavoro, costo_orario, costo_km, idcontratto_prev) VALUES('.prepare($numero).', '.prepare($rs[0]['nome']).', '.prepare($rs[0]['idagente']).', NOW(), '.prepare(date('Y-m-d', strtotime($rs[0]['data_conclusione'].' +1 day'))).', "", '.prepare(date('Y-m-d', strtotime($rs[0]['data_conclusione'].' +'.$giorni_add.' day'))).', '.prepare($rs[0]['rinnovabile']).', '.prepare($rs[0]['giorni_preavviso_rinnovo']).', '.prepare($rs[0]['budget']).', '.prepare($rs[0]['descrizione']).', '.prepare($rs[0]['idstato']).', '.prepare($rs[0]['idreferente']).', '.prepare($rs[0]['validita']).', '.prepare($rs[0]['esclusioni']).', '.prepare($rs[0]['idanagrafica']).', '.prepare($rs[0]['idpagamento']).', '.prepare($rs[0]['costo_diritto_chiamata']).', '.prepare($rs[0]['ore_lavoro']).', '.prepare($rs[0]['costo_orario']).', '.prepare($rs[0]['costo_km']).', '.prepare($id_record).')')) {
                $new_idcontratto = $dbo->lastInsertedID();

                $dbo->query('INSERT INTO co_contratti_tipiintervento(idcontratto, idtipointervento, costo_ore, costo_km, costo_dirittochiamata, costo_ore_tecnico, costo_km_tecnico, costo_dirittochiamata_tecnico) SELECT '.prepare($new_idcontratto).', idtipointervento, costo_ore, costo_km, costo_dirittochiamata, costo_ore_tecnico, costo_km_tecnico, costo_dirittochiamata_tecnico FROM co_contratti_tipiintervento AS z WHERE idcontratto='.prepare($id_record));

                // Replico le righe del contratto
                $rs = $dbo->fetchArray('SELECT * FROM co_righe_contratti WHERE idcontratto='.prepare($id_record));

                for ($i = 0; $i < sizeof($rs); ++$i) {
                    $dbo->query('INSERT INTO co_righe_contratti(idcontratto, descrizione, subtotale, um, qta) VALUES('.prepare($new_idcontratto).', '.prepare($rs[$i]['descrizione']).', '.prepare($rs[$i]['subtotale']).', '.prepare($rs[$i]['um']).', '.prepare($rs[$i]['qta']).')');
                }

                // Replicazione degli impianti
                $impianti = $dbo->fetchArray('SELECT idimpianto FROM my_impianti_contratti WHERE idcontratto='.prepare($id_record));
                $dbo->sync('my_impianti_contratti', ['idcontratto' => $new_idcontratto], ['idimpianto' => array_column($impianti, 'idimpianto')]);

                flash()->info(tr('Contratto rinnovato!'));

                redirect($rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$new_idcontratto);
            } else {
                flash()->error(tr('Errore durante il rinnovo del contratto!'));
            }
        }

        break;
}

if (post('op') !== null && post('op') != 'update') {
    aggiorna_sconto([
        'parent' => 'co_contratti',
        'row' => 'co_righe_contratti',
    ], [
        'parent' => 'id',
        'row' => 'idcontratto',
    ], $id_record);
}
