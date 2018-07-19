<?php

include_once __DIR__.'/../../core.php';

include_once Modules::filepath('Fatture di vendita', 'modutil.php');

switch (post('op')) {
    case 'add':
        $idanagrafica = post('idanagrafica');
        $nome = post('nome');

        $idtipointervento = post('idtipointervento');
        $rs = $dbo->fetchArray('SELECT costo_orario, costo_diritto_chiamata FROM in_tipiintervento WHERE idtipointervento='.prepare($idtipointervento));
        $costo_orario = $rs[0]['costo_orario'];
        $costo_diritto_chiamata = $rs[0]['costo_diritto_chiamata'];

        // Verifico se c'è già un agente o un metodo di pagamento collegato all'anagrafica cliente, così lo imposto già
        $q = 'SELECT idagente, idpagamento_vendite AS idpagamento FROM an_anagrafiche WHERE idanagrafica='.prepare($idanagrafica);
        $rs = $dbo->fetchArray($q);
        $idagente = $rs[0]['idagente'];
        $idpagamento = $rs[0]['idpagamento'];

        // Codice preventivo: calcolo il successivo in base al formato specificato
        $numeropreventivo_template = setting('Formato codice preventivi');
        $numeropreventivo_template = str_replace('#', '%', $numeropreventivo_template);

        // Codice preventivo: calcolo il successivo in base al formato specificato
        $rs = $dbo->fetchArray('SELECT numero FROM co_preventivi WHERE numero=(SELECT MAX(CAST(numero AS SIGNED)) FROM co_preventivi) AND numero LIKE('.prepare($numeropreventivo_template).') ORDER BY numero DESC LIMIT 0,1');
        $numero = Util\Generator::generate(setting('Formato codice preventivi'), $rs[0]['numero']);

        if (!is_numeric($numero)) {
            $rs = $dbo->fetchArray('SELECT numero FROM co_preventivi WHERE numero LIKE('.prepare($numeropreventivo_template).') ORDER BY numero DESC LIMIT 0,1');
            $numero = Util\Generator::generate(setting('Formato codice preventivi'), $rs[0]['numero']);
        }

        $idiva = setting('Iva predefinita');
        $rs_iva = $dbo->fetchArray('SELECT descrizione, percentuale, indetraibile FROM co_iva WHERE id='.prepare($idiva));

        // Se al preventivo non è stato associato un pagamento predefinito al cliente leggo il pagamento dalle impostazioni
        if ($idpagamento == '') {
            $idpagamento = setting('Tipo di pagamento predefinito');
        }

        $dbo->query('INSERT INTO co_preventivi(idanagrafica, nome, numero, idagente, idstato, idtipointervento, data_bozza, data_conclusione, idiva, idpagamento) VALUES ('.prepare($idanagrafica).', '.prepare($nome).', '.prepare($numero).', '.prepare($idagente).", (SELECT `id` FROM `co_statipreventivi` WHERE `descrizione`='Bozza'), ".prepare($idtipointervento).', NOW(), DATE_ADD(NOW(), INTERVAL +1 MONTH), '.prepare($idiva).', '.prepare($idpagamento).')');
        $id_record = $dbo->lastInsertedID();

        /*
        // inserisco righe standard preventivo
        // ore lavoro
        $costo = $costo_orario;
        $iva = $costo / 100 * $rs_iva[0]['percentuale'];
        $iva_indetraibile = $iva / 100 * $rs_iva[0]['indetraibile'];
        $ore = $dbo->fetchArray("SELECT `id` FROM `mg_unitamisura` WHERE `valore`='ore'");
        $dbo->query('INSERT INTO co_righe_preventivi(idpreventivo, idarticolo, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, um, qta, sconto, sconto_unitario, tipo_sconto, `order`) VALUES ('.prepare($id_record).", '0', ".prepare($idiva).', '.prepare($rs_iva[0]['descrizione']).', '.prepare($iva).', '.prepare($iva_indetraibile).", 'Ore lavoro', ".prepare($costo).', '.prepare('ore').", 1, 0, 0, 'UNT', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_preventivi AS t WHERE idpreventivo=".prepare($id_record).'))');

        // diritto chiamata
        $costo = $costo_diritto_chiamata;
        $iva = $costo / 100 * $rs_iva[0]['percentuale'];
        $iva_indetraibile = $iva / 100 * $rs_iva[0]['indetraibile'];
        $dbo->query('INSERT INTO co_righe_preventivi(idpreventivo, idarticolo, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, um, qta, sconto, sconto_unitario, tipo_sconto, `order`) VALUES ('.prepare($id_record).", '0', ".prepare($idiva).', '.prepare($rs_iva[0]['descrizione']).', '.prepare($iva).', '.prepare($iva_indetraibile).", 'Diritto chiamata', ".prepare($costo).", '', 1, 0, 0, 'UNT', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_preventivi AS t WHERE idpreventivo=".prepare($id_record).'))');
        */

        flash()->info(tr('Aggiunto preventivo numero _NUM_!', [
            '_NUM_' => $numero,
        ]));

        break;

    case 'update':
        if (isset($id_record)) {
            $idstato = post('idstato');
            $nome = post('nome');
            $idanagrafica = post('idanagrafica');
            $idagente = post('idagente');
            $idreferente = post('idreferente');
            $idpagamento = post('idpagamento');
            $idporto = post('idporto');
            $tempi_consegna = post('tempi_consegna');
            $numero = post('numero');

            $tipo_sconto = post('tipo_sconto_generico');
            $sconto = post('sconto_generico');

            // $budget = post('budget');
            // $budget = str_replace( ",", ".", $budget );

            $data_bozza = post('data_bozza');
            $data_accettazione = post('data_accettazione');
            $data_rifiuto = post('data_rifiuto');
            $data_conclusione = post('data_conclusione');
            $esclusioni = post('esclusioni');
            $descrizione = post('descrizione');
            $validita = post('validita');
            $idtipointervento = post('idtipointervento');

            // $costo_diritto_chiamata = post('costo_diritto_chiamata');
            // $ore_lavoro = str_replace( ",", ".", post('ore_lavoro') );
            // $costo_orario = post('costo_orario');
            // $costo_km = post('costo_km');

            $idiva = post('idiva');

            $query = 'UPDATE co_preventivi SET idstato='.prepare($idstato).','.
                ' nome='.prepare($nome).','.
                ' idanagrafica='.prepare($idanagrafica).','.
                ' idagente='.prepare($idagente).','.
                ' idreferente='.prepare($idreferente).','.
                ' idpagamento='.prepare($idpagamento).','.
                ' idporto='.prepare($idporto).','.
                ' tempi_consegna='.prepare($tempi_consegna).','.
                ' numero='.prepare($numero).','.
                ' data_bozza='.prepare($data_bozza).','.
                ' data_accettazione='.prepare($data_accettazione).','.
                ' data_rifiuto='.prepare($data_rifiuto).','.
                ' data_conclusione='.prepare($data_conclusione).','.
                ' esclusioni='.prepare($esclusioni).','.
                ' descrizione='.prepare($descrizione).','.
                ' tipo_sconto_globale='.prepare($tipo_sconto).','.
                ' sconto_globale='.prepare($sconto).','.
                ' validita='.prepare($validita).','.
                ' idtipointervento='.prepare($idtipointervento).','.
                ' idiva='.prepare($idiva).' WHERE id='.prepare($id_record);
            $dbo->query($query);

            aggiorna_sconto([
                'parent' => 'co_preventivi',
                'row' => 'co_righe_preventivi',
            ], [
                'parent' => 'id',
                'row' => 'idpreventivo',
            ], $id_record);

            // update_budget_preventivo( $id_record );
            flash()->info(tr('Preventivo modificato correttamente!'));
        }
        break;

    case 'addintervento':
        if (post('idintervento') !== null) {
            // Selezione costi da intervento
            $idintervento = post('idintervento');
            $rs = $dbo->fetchArray('SELECT * FROM in_interventi WHERE id='.prepare($idintervento));
            $costo_km = $rs[0]['prezzo_km_unitario'];
            $costo_orario = $rs[0]['prezzo_ore_unitario'];

            $query = 'INSERT INTO co_preventivi_interventi(idpreventivo, idintervento) VALUES('.prepare($id_record).', '.prepare($idintervento).')';

            $dbo->query($query);

            // Imposto il preventivo nello stato "In lavorazione" se inizio ad aggiungere interventi
            $dbo->query("UPDATE `co_preventivi` SET idstato=(SELECT `id` FROM `co_statipreventivi` WHERE `descrizione`='In lavorazione') WHERE `id`=".prepare($id_record));

            flash()->info(tr('Intervento _NUM_ aggiunto!', [
                '_NUM_' => $rs[0]['codice'],
            ]));
        }
        break;

    // Scollegamento articolo da ordine
    case 'unlink_articolo':
        if (post('idriga') !== null) {
            $idriga = post('idriga');
            $idarticolo = post('idarticolo');

            // Leggo la quantità di questo articolo nell'ordine
            $query = 'SELECT qta, subtotale FROM co_righe_preventivi WHERE id='.prepare($idriga);
            $rs = $dbo->fetchArray($query);
            $qta = floatval($rs[0]['qta']);
            $subtotale = $rs[0]['subtotale'];

            // Elimino la riga dal preventivo
            $dbo->query('DELETE FROM co_righe_preventivi WHERE id='.prepare($idriga));

            flash()->info(tr('Riga rimossa!'));
        }
        break;

    // Scollegamento intervento da preventivo
    case 'unlink':
        if (isset($_GET['idpreventivo']) && isset($_GET['idintervento'])) {
            $idintervento = get('idintervento');

            $query = 'DELETE FROM `co_preventivi_interventi` WHERE idpreventivo='.prepare($id_record).' AND idintervento='.prepare($idintervento);
            $dbo->query($query);

            flash()->info(tr('Intervento _NUM_ rimosso!', [
                '_NUM_' => $idintervento,
            ]));
        }
        break;

    // eliminazione preventivo
    case 'delete':
        $dbo->query('DELETE FROM co_preventivi WHERE id='.prepare($id_record));
        $dbo->query('DELETE FROM co_preventivi_interventi WHERE idpreventivo='.prepare($id_record));

        flash()->info(tr('Preventivo eliminato!'));

        break;

    // Aggiungo una riga al preventivo
    case 'addriga':
        $idarticolo = post('idarticolo');
        $idiva = post('idiva');
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
        $rs2 = $dbo->fetchArray('SELECT descrizione, percentuale, indetraibile FROM co_iva WHERE id='.prepare($idiva));
        $iva = ($subtot - $sconto) / 100 * $rs2[0]['percentuale'];
        $iva_indetraibile = $iva / 100 * $rs2[0]['indetraibile'];

        $dbo->query('INSERT INTO co_righe_preventivi(idpreventivo, idarticolo, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, um, qta, sconto, sconto_unitario, tipo_sconto, is_descrizione, `order`) VALUES ('.prepare($id_record).', '.prepare($idarticolo).', '.prepare($idiva).', '.prepare($rs2[0]['descrizione']).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare($descrizione).', '.prepare($subtot).', '.prepare($um).', '.prepare($qta).', '.prepare($sconto).', '.prepare($sconto_unitario).', '.prepare($tipo_sconto).', '.prepare(empty($qta)).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_preventivi AS t WHERE idpreventivo='.prepare($id_record).'))');

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
        $descrizione = post('descrizione');

        //Info riga Preventivo
        $rs = $dbo->fetchArray('SELECT * FROM co_righe_preventivi WHERE id='.prepare($idriga));
        $is_descrizione = $rs[0]['is_descrizione'];

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
        $rs2 = $dbo->fetchArray('SELECT descrizione, percentuale, indetraibile FROM co_iva WHERE id='.prepare($idiva));
        $iva = ($subtot - $sconto) / 100 * $rs2[0]['percentuale'];
        $iva_indetraibile = $iva / 100 * $rs2[0]['indetraibile'];
        $desc_iva = $rs2[0]['descrizione'];

        if ($is_descrizione == 0) {
            // Modifica riga generica sul documento
            $query = 'UPDATE co_righe_preventivi SET idiva='.prepare($idiva).', desc_iva='.prepare($desc_iva).', iva='.prepare($iva).', iva_indetraibile='.prepare($iva_indetraibile).', descrizione='.prepare($descrizione).', subtotale='.prepare($subtot).', sconto='.prepare($sconto).', sconto_unitario='.prepare($sconto_unitario).', tipo_sconto='.prepare($tipo_sconto).', um='.prepare($um).', qta='.prepare($qta).' WHERE id='.prepare($idriga);
        } else {
            $query = 'UPDATE co_righe_preventivi SET descrizione='.prepare($descrizione).' WHERE id='.prepare($idriga);
        }
        $dbo->query($query);

        flash()->info('Riga modificata!');
        break;

    case 'update_position':
        $start = filter('start');
        $end = filter('end');
        $id = filter('id');

        if ($start > $end) {
            $dbo->query('UPDATE `co_righe_preventivi` SET `order`=`order` + 1 WHERE `order`>='.prepare($end).' AND `order`<'.prepare($start).' AND `idpreventivo`='.prepare($id_record));
            $dbo->query('UPDATE `co_righe_preventivi` SET `order`='.prepare($end).' WHERE id='.prepare($id));
        } elseif ($end != $start) {
            $dbo->query('UPDATE `co_righe_preventivi` SET `order`=`order` - 1 WHERE `order`>'.prepare($start).' AND `order`<='.prepare($end).' AND `idpreventivo`='.prepare($id_record));
            $dbo->query('UPDATE `co_righe_preventivi` SET `order`='.prepare($end).' WHERE id='.prepare($id));
        }

        break;
}

if (post('op') !== null && post('op') != 'update') {
    aggiorna_sconto([
        'parent' => 'co_preventivi',
        'row' => 'co_righe_preventivi',
    ], [
        'parent' => 'id',
        'row' => 'idpreventivo',
    ], $id_record);
}
