<?php

include_once __DIR__.'/../../core.php';

include $docroot.'/modules/articoli/modutil.php';
include_once $docroot.'/modules/fatture/modutil.php';

$module = Modules::getModule($id_module);

if ($module['name'] == 'Ordini cliente') {
    $dir = 'entrata';
} else {
    $dir = 'uscita';
}

switch (post('op')) {
    case 'add':
        $idanagrafica = post('idanagrafica');

        $data = $post['data'];

        // Leggo se l'ordine è cliente o fornitore
        $rs = $dbo->fetchArray('SELECT id FROM or_tipiordine WHERE dir='.prepare($dir));
        $idtipoordine = $rs[0]['id'];

        if (isset($post['idanagrafica'])) {
            $numero = get_new_numeroordine($data);
            if ($dir == 'entrata') {
                $numero_esterno = get_new_numerosecondarioordine($data);
            } else {
                $numero_esterno = '';
            }

            $campo = ($dir == 'entrata') ? 'idpagamento_vendite' : 'idpagamento_acquisti';

            // Tipo di pagamento predefinito dall'anagrafica
            $query = 'SELECT id FROM co_pagamenti WHERE id=(SELECT '.$campo.' AS pagamento FROM an_anagrafiche WHERE idanagrafica='.prepare($idanagrafica).')';
            $rs = $dbo->fetchArray($query);
            $idpagamento = $rs[0]['id'];

            // Se l'ordine è un ordine cliente e non è stato associato un pagamento predefinito al cliente leggo il pagamento dalle impostazioni
            if ($dir == 'entrata' && $idpagamento == '') {
                $idpagamento = get_var('Tipo di pagamento predefinito');
            }

            $query = 'INSERT INTO or_ordini( numero, numero_esterno, idanagrafica, idtipoordine, idpagamento, data, idstatoordine ) VALUES ( '.prepare($numero).', '.prepare($numero_esterno).', '.prepare($idanagrafica).', '.prepare($idtipoordine).', '.prepare($idpagamento).', '.prepare($data).", (SELECT `id` FROM `or_statiordine` WHERE `descrizione`='Non evaso') )";
            $dbo->query($query);

            $id_record = $dbo->lastInsertedID();

            $_SESSION['infos'][] = str_replace('_NUM_', $numero, _('Aggiunto ordine numero _NUM_!'));
        }
        break;

    case 'update':
        if ($id_record != '') {
            $numero_esterno = post('numero_esterno');
            $numero = post('numero');
            $data = $post['data'];
            $idanagrafica = post('idanagrafica');
            $note = post('note');
            $idstatoordine = post('idstatoordine');
            $idpagamento = post('idpagamento');
            $idsede = post('idsede');
            $idconto = post('idconto');
            $idagente = post('idagente');
            $totale_imponibile = get_imponibile_ordine($id_record);
            $totale_ordine = get_totale_ordine($id_record);

            $tipo_sconto = $post['tipo_sconto_generico'];
            $sconto = $post['sconto_generico'];

            if ($dir == 'uscita') {
                $idrivalsainps = post('idrivalsainps');
                $idritenutaacconto = post('idritenutaacconto');
                $bollo = post('bollo');
            } else {
                $idrivalsainps = 0;
                $idritenutaacconto = 0;
                $bollo = 0;
            }

            // Leggo la descrizione del pagamento
            $query = 'SELECT descrizione FROM co_pagamenti WHERE id='.prepare($idpagamento);
            $rs = $dbo->fetchArray($query);
            $pagamento = $rs[0]['descrizione'];

            // Query di aggiornamento
            $query = 'UPDATE or_ordini SET idanagrafica='.prepare($idanagrafica).','.
                ' numero='.prepare($numero).','.
                ' data='.prepare($data).','.
                ' idagente='.prepare($idagente).','.
                ' idstatoordine='.prepare($idstatoordine).','.
                ' idpagamento='.prepare($idpagamento).','.
                ' idsede='.prepare($idsede).','.
                ' numero_esterno='.prepare($numero_esterno).','.
                ' note='.prepare($note).','.
                ' idconto='.prepare($idconto).','.
                ' idrivalsainps='.prepare($idrivalsainps).','.
                ' idritenutaacconto='.prepare($idritenutaacconto).','.
                ' tipo_sconto_globale='.prepare($tipo_sconto).','.
                ' sconto_globale='.prepare($sconto).','.
                ' bollo=0, rivalsainps=0, ritenutaacconto=0 WHERE id='.prepare($id_record);

            if ($dbo->query($query)) {
                aggiorna_sconto([
                    'parent' => 'or_ordini',
                    'row' => 'or_righe_ordini',
                ], [
                    'parent' => 'id',
                    'row' => 'idordine',
                ], $id_record);

                $query = 'SELECT descrizione FROM or_statiordine WHERE id='.prepare($idstatoordine);
                $rs = $dbo->fetchArray($query);

                // Ricalcolo inps, ritenuta e bollo (se l'ordine non è stato evaso)
                if ($dir == 'entrata') {
                    if ($rs[0]['descrizione'] != 'Pagato') {
                        ricalcola_costiagg_ordine($id_record);
                    }
                } else {
                    if ($rs[0]['descrizione'] != 'Pagato') {
                        ricalcola_costiagg_ordine($id_record, $idrivalsainps, $idritenutaacconto, $bollo);
                    }
                }

                $_SESSION['infos'][] = _('Ordine modificato correttamente!');
            }
        }
        break;

    case 'addarticolo':
        if ($id_record != '' && isset($post['idarticolo'])) {
            $idarticolo = post('idarticolo');
            $idiva = post('idiva');
            $descrizione = post('descrizione');
            $qta = post('qta');
            $prezzo_vendita = post('prezzo');

            // Calcolo dello sconto
            $sconto_unitario = $post['sconto'];
            $tipo_sconto = $post['tipo_sconto'];
            $sconto = ($tipo_sconto == 'PRC') ? ($prezzo * $sconto_unitario) / 100 : $sconto_unitario;
            $sconto = $sconto * $qta;

            // Calcolo idgruppo per questo inserimento
            $ridgruppo = $dbo->fetchArray('SELECT IFNULL(MAX(idgruppo) + 1, 0) AS idgruppo FROM or_righe_ordini WHERE idordine = '.prepare($id_record));
            $idgruppo = $ridgruppo[0]['idgruppo'];

            add_articolo_inordine($id_record, $idarticolo, $descrizione, $idiva, $qta, $prezzo_vendita * $qta, $sconto, $sconto_unitario, $tipo_sconto, '', '', '', $idgruppo);

            $_SESSION['infos'][] = _('Articolo aggiunto!');
        }
        ricalcola_costiagg_ordine($id_record);
        break;

    case 'addriga':
        if ($id_record != '') {
            // Selezione costi da intervento
            $descrizione = post('descrizione');
            $prezzo = post('prezzo');
            $qta = post('qta');
            $idiva = post('idiva');
            $um = post('um');
            $subtot = $prezzo * $qta;

            // Calcolo dello sconto
            $sconto_unitario = $post['sconto'];
            $tipo_sconto = $post['tipo_sconto'];
            $sconto = ($tipo_sconto == 'PRC') ? ($prezzo * $sconto_unitario) / 100 : $sconto_unitario;
            $sconto = $sconto * $qta;

            // Calcolo iva
            $query = 'SELECT descrizione, percentuale, indetraibile FROM co_iva WHERE id='.prepare($idiva);
            $rs = $dbo->fetchArray($query);
            $iva = ($subtot - $sconto) / 100 * $rs[0]['percentuale'];
            $iva_indetraibile = $iva / 100 * $rs[0]['indetraibile'];

            $query = 'INSERT INTO or_righe_ordini(idordine, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, sconto, sconto_unitario, tipo_sconto, um, qta, idgruppo, `order`) VALUES('.prepare($id_record).', '.prepare($idiva).', '.prepare($rs[0]['descrizione']).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare($descrizione).', '.prepare($subtot).', '.prepare($sconto).', '.prepare($sconto_unitario).', '.prepare($tipo_sconto).', '.prepare($um).', '.prepare($qta).', (SELECT IFNULL(MAX(`idgruppo`) + 1, 0) FROM or_righe_ordini AS t WHERE idordine='.prepare($id_record).'), (SELECT IFNULL(MAX(`order`) + 1, 0) FROM or_righe_ordini AS t WHERE idordine='.prepare($id_record).'))';

            if ($dbo->query($query)) {
                $_SESSION['infos'][] = _('Riga aggiunta!');

                // Ricalcolo inps, ritenuta e bollo
                if ($dir == 'entrata') {
                    ricalcola_costiagg_ordine($id_record);
                } else {
                    ricalcola_costiagg_ordine($id_record);
                }
            }
        }
        break;

    // Scollegamento articolo da ordine
    case 'unlink_articolo':
        $idarticolo = post('idarticolo');
        $idriga = post('idriga');

        if ($id_record != '' && $idarticolo != '') {
            rimuovi_articolo_daordine($idarticolo, $id_record, $idriga);

            // if( $dbo->query($query) ){
                // Ricalcolo inps, ritenuta e bollo
            if ($dir == 'entrata') {
                ricalcola_costiagg_ordine($id_record);
            } else {
                ricalcola_costiagg_ordine($id_record, 0, 0, 0);
            }

            $_SESSION['infos'][] = _('Articolo rimosso!');
        }

        break;

    // Scollegamento riga generica da ordine
    case 'unlink_riga':
        $idriga = post('idriga');

        if ($id_record != '' && $idriga != '') {
            $query = 'DELETE FROM or_righe_ordini WHERE idordine='.prepare($id_record).' AND id='.prepare($idriga);

            $dbo->query($query);

            // Ricalcolo inps, ritenuta e bollo
            if ($dir == 'entrata') {
                ricalcola_costiagg_ordine($id_record);
            } else {
                ricalcola_costiagg_ordine($id_record, 0, 0, 0);
            }

            $_SESSION['infos'][] = _('Riga rimossa!');
        }
        break;

    // Modifica riga
    case 'editriga':
        if (isset($post['idriga'])) {
            $idriga = post('idriga');
            $descrizione = post('descrizione');
            $prezzo = post('prezzo');
            $qta = post('qta');
            $idiva = post('idiva');
            $um = post('um');
            $subtot = $prezzo * $qta;

            // Calcolo dello sconto
            $sconto_unitario = $post['sconto'];
            $tipo_sconto = $post['tipo_sconto'];
            $sconto = ($tipo_sconto == 'PRC') ? ($prezzo * $sconto_unitario) / 100 : $sconto_unitario;
            $sconto = $sconto * $qta;

            // Calcolo iva
            $query = 'SELECT * FROM co_iva WHERE id='.prepare($idiva);
            $rs = $dbo->fetchArray($query);
            $iva = ($subtot - $sconto) / 100 * $rs[0]['percentuale'];
            $iva_indetraibile = $iva / 100 * $rs[0]['indetraibile'];
            $desc_iva = $rs[0]['descrizione'];

            // Lettura idarticolo dalla riga documento
            $rs = $dbo->fetchArray('SELECT idgruppo, idordine, idarticolo, qta, abilita_serial FROM or_righe_ordini WHERE id='.prepare($idriga));
            $idarticolo = $rs[0]['idarticolo'];
            $old_qta = $rs[0]['qta'];
            $idgruppo = $rs[0]['idgruppo'];
            $idordine = $rs[0]['idordine'];
            $abilita_serial = $rs[0]['abilita_serial'];

            // Modifica riga generica sul documento
            $query = 'UPDATE or_righe_ordini SET idiva='.prepare($idiva).', desc_iva='.prepare($rs[0]['descrizione']).', iva='.prepare($iva).', iva_indetraibile='.prepare($iva_indetraibile).', descrizione='.prepare($descrizione).', subtotale='.prepare($subtot).', sconto='.prepare($sconto).', sconto_unitario='.prepare($sconto_unitario).', tipo_sconto='.prepare($tipo_sconto).', um='.prepare($um).' WHERE idgruppo='.prepare($idgruppo).' AND idordine='.prepare($idordine);
            if ($dbo->query($query)) {
                // Modifica della quantità
                $dbo->query('UPDATE or_righe_ordini SET qta='.prepare($qta).' WHERE idgruppo='.prepare($idgruppo));

                // Modifica per gestire i serial
                if (!empty($idarticolo)) {
                    $new_qta = $qta - $old_qta;
                    $new_qta = ($old_qta < $qta) ? $new_qta : -$new_qta;

                    if (!empty($abilita_serial)) {
                        if ($old_qta < $qta) {
                            for ($i = 0; $i < $new_qta; ++$i) {
                                $dbo->query('INSERT INTO or_righe_ordini(idordine, idarticolo, idconto, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, sconto, sconto_unitario, tipo_sconto, um, qta, idgruppo, `order`) SELECT idordine, idarticolo, idconto, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, sconto, sconto_unitario, tipo_sconto, um, qta, idgruppo, `order` FROM or_righe_ordini WHERE id='.prepare($idriga));
                            }
                        } else {
                            if ($dir == 'uscita') {
                                if ($new_qta > $dbo->fetchArray("SELECT COUNT(*) AS rimovibili FROM or_righe_ordini WHERE serial NOT IN (SELECT serial FROM vw_serials WHERE dir = 'entrata') AND idgruppo=".prepare($idgruppo).' AND idordine='.prepare($idordine))[0]['rimovibili']) {
                                    $_SESSION['errors'][] = _('Alcuni serial number sono già stati utilizzati!');

                                    return;
                                } else {
                                    $deletes = $dbo->fetchArray('SELECT id FROM or_righe_ordini AS t WHERE idgruppo = '.prepare($idgruppo).' AND idordine='.prepare($idordine)." AND serial NOT IN (SELECT serial FROM vw_serials WHERE dir = 'entrata') ORDER BY serial ASC LIMIT ".$new_qta);
                                }
                            } else {
                                $deletes = $dbo->fetchArray('SELECT id FROM or_righe_ordini AS t WHERE idgruppo = '.prepare($idgruppo).' AND idordine='.prepare($idordine).' ORDER BY serial ASC LIMIT '.$new_qta);
                            }

                            foreach ((array) $deletes as $delete) {
                                $dbo->query('DELETE FROM or_righe_ordini WHERE id = '.prepare($delete['id']));
                            }
                        }
                    }
                }

                $_SESSION['infos'][] = _('Riga modificata!');

                // Ricalcolo inps, ritenuta e bollo
                if ($dir == 'entrata') {
                    ricalcola_costiagg_ordine($id_record);
                } else {
                    ricalcola_costiagg_ordine($id_record);
                }
            }
        }
        break;

    // eliminazione ordine
    case 'delete':
        if ($dir == 'uscita') {
            $non_rimovibili = $dbo->fetchArray("SELECT COUNT(*) AS non_rimovibili FROM or_righe_ordini WHERE serial IN (SELECT serial FROM vw_serials WHERE dir = 'entrata') AND idordine=".prepare($id_record))[0]['non_rimovibili'];
            if ($non_rimovibili != 0) {
                $_SESSION['errors'][] = _('Alcuni serial number sono già stati utilizzati!');

                return;
            }
        }

        $dbo->query('DELETE FROM or_ordini WHERE id='.prepare($id_record));
        $dbo->query('DELETE FROM or_righe_ordini WHERE idordine='.prepare($id_record));
        $_SESSION['infos'][] = _('Ordine eliminato!');

        break;

    case 'add_serial':
        $idgruppo = $post['idgruppo'];
        $serial = $post['serial'];

        $q = 'SELECT * FROM or_righe_ordini WHERE idordine='.prepare($id_record).' AND idgruppo='.prepare($idgruppo).' ORDER BY id';
        $rs = $dbo->fetchArray($q);

        foreach ($rs as $i => $r) {
            $dbo->query('UPDATE or_righe_ordini SET serial='.prepare($serial[$i]).' WHERE id='.prepare($r['id']));
        }

        break;

    case 'update_position':
        $start = filter('start');
        $end = filter('end');
        $id = filter('id');

        if ($start > $end) {
            $dbo->query('UPDATE `or_righe_ordini` SET `order`=`order` + 1 WHERE `order`>='.prepare($end).' AND `order`<'.prepare($start).' AND `idordine`='.prepare($id_record));
            $dbo->query('UPDATE `or_righe_ordini` SET `order`='.prepare($end).' WHERE id='.prepare($id));
        } elseif ($end != $start) {
            $dbo->query('UPDATE `or_righe_ordini` SET `order`=`order` - 1 WHERE `order`>'.prepare($start).' AND `order`<='.prepare($end).' AND `idordine`='.prepare($id_record));
            $dbo->query('UPDATE `or_righe_ordini` SET `order`='.prepare($end).' WHERE id='.prepare($id));
        }

        break;
}

if (post('op') !== null && post('op') != 'update') {
    aggiorna_sconto([
        'parent' => 'or_ordini',
        'row' => 'or_righe_ordini',
    ], [
        'parent' => 'id',
        'row' => 'idordine',
    ], $id_record);
}
