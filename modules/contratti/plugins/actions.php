<?php

include_once __DIR__.'/../../../core.php';

include_once Modules::filepath('Fatture di vendita', 'modutil.php');

switch (post('op')) {
     /*
        GESTIONE ARTICOLI
    */

    case 'editarticolo':

        $idriga = post('idriga');
        $idarticolo = post('idarticolo');
        $idimpianto = post('idimpianto');
        //$idautomezzo = post('idautomezzo');

        //$idarticolo_originale = post('idarticolo_originale');

        // Leggo la quantità attuale nell'intervento
        $q = 'SELECT qta, idautomezzo, idimpianto FROM co_righe_contratti_articoli WHERE id='.prepare($idriga);
        $rs = $dbo->fetchArray($q);
        $old_qta = $rs[0]['qta'];
        $idimpianto = $rs[0]['idimpianto'];
        //$idautomezzo = $rs[0]['idautomezzo'];

        //$serials = array_column($dbo->select('mg_prodotti', 'serial', ['id_riga_intervento' => $idriga]), 'serial');

        //add_movimento_magazzino($idarticolo_originale, $old_qta, ['idautomezzo' => $idautomezzo, 'idintervento' => $id_record]);

        // Elimino questo articolo dall'intervento
        $dbo->query('DELETE FROM co_righe_contratti_articoli WHERE id='.prepare($idriga));

        // Elimino il collegamento al componente
        //$dbo->query('DELETE FROM my_impianto_componenti WHERE idimpianto='.prepare($idimpianto).' AND idintervento='.prepare($id_record));

        /* Ricollego l'articolo modificato all'intervento */
        /* ci può essere il caso in cui cambio idarticolo e anche qta */

    //no break;

    case 'addarticolo':

        $idarticolo = post('idarticolo');
        //$idautomezzo = post('idautomezzo');
        $descrizione = post('descrizione');
        $idimpianto = post('idimpianto');
        $qta = post('qta');
        $um = post('um');
        $prezzo_vendita = post('prezzo_vendita');
        $idiva = post('idiva');

        $sconto_unitario = $post['sconto'];
        $tipo_sconto = $post['tipo_sconto'];
        $sconto = calcola_sconto([
            'sconto' => $sconto_unitario,
            'prezzo' => $prezzo_vendita,
            'tipo' => $tipo_sconto,
            'qta' => $qta,
        ]);

        $idcontratto_riga = $post['idcontratto_riga'];

        // Decremento la quantità
        //add_movimento_magazzino($idarticolo, -$qta, ['idautomezzo' => $idautomezzo, 'idintervento' => $id_record]);

        // Aggiorno l'automezzo dell'intervento
        //$dbo->query('UPDATE in_interventi SET idautomezzo='.prepare($idautomezzo).' WHERE id='.prepare($id_record).' '.Modules::getAdditionalsQuery($id_module));

        //$rsart = $dbo->fetchArray('SELECT abilita_serial, prezzo_acquisto FROM mg_articoli WHERE id='.prepare($idarticolo));
        //$prezzo_acquisto = $rsart[0]['prezzo_acquisto'];

        //Calcolo iva
        $rs_iva = $dbo->fetchArray('SELECT * FROM co_iva WHERE id='.prepare($idiva));
        $desc_iva = $rs_iva[0]['descrizione'];

        $iva = (($prezzo_vendita * $qta) - $sconto) * $rs_iva[0]['percentuale'] / 100;

        // Aggiunto il collegamento fra l'articolo e l'intervento
        $idriga = $dbo->query('INSERT INTO co_righe_contratti_articoli(idarticolo, id_riga_contratto, idimpianto, idautomezzo, descrizione, prezzo_vendita, prezzo_acquisto, sconto, sconto_unitario, tipo_sconto, idiva, desc_iva, iva, qta, um, abilita_serial) VALUES ('.prepare($idarticolo).', '.prepare($idcontratto_riga).', '.(empty($idimpianto) ? 'NULL' : prepare($idimpianto)).', '.prepare($idautomezzo).', '.prepare($descrizione).', '.prepare($prezzo_vendita).', '.prepare($prezzo_acquisto).', '.prepare($sconto).', '.prepare($sconto_unitario).', '.prepare($tipo_sconto).', '.prepare($idiva).', '.prepare($desc_iva).', '.prepare($iva).', '.prepare($qta).', '.prepare($um).', '.prepare($rsart[0]['abilita_serial']).')');

        /*if (!empty($serials)) {
            if ($old_qta > $qta) {
                $serials = array_slice($serials, 0, $qta);
            }

            $dbo->sync('mg_prodotti', ['id_riga_intervento' => $idriga, 'dir' => 'entrata', 'id_articolo' => $idarticolo], ['serial' => $serials]);
        }*/

        //link_componente_to_articolo($id_record, $idimpianto, $idarticolo, $qta);

        break;

    case 'unlink_articolo':

        $idriga = post('idriga');
        //$idarticolo = post('idarticolo');

        $dbo->query('DELETE FROM co_righe_contratti_articoli WHERE id='.prepare($idriga).' '.Modules::getAdditionalsQuery($id_module));

        // Riporto la merce nel magazzino
        if (!empty($idriga) && !empty($id_record)) {
            // Leggo la quantità attuale nell'intervento
            //$q = 'SELECT qta, idautomezzo, idarticolo, idimpianto FROM co_righe_contratti_articoli WHERE id='.prepare($idriga);
            //$rs = $dbo->fetchArray($q);
            //$qta = $rs[0]['qta'];
            //$idarticolo = $rs[0]['idarticolo'];
            //$idimpianto = $rs[0]['idimpianto'];
            //$idautomezzo = $rs[0]['idautomezzo'];

           // add_movimento_magazzino($idarticolo, $qta, ['idautomezzo' => $idautomezzo, 'idintervento' => $id_record]);

            // Elimino questo articolo dall'intervento
            //$dbo->query('DELETE FROM mg_articoli_interventi WHERE id='.prepare($idriga).' AND idintervento='.prepare($id_record));

            // Elimino il collegamento al componente
            //$dbo->query('DELETE FROM my_impianto_componenti WHERE idimpianto='.prepare($idimpianto).' AND idintervento='.prepare($id_record));

            // Elimino i seriali utilizzati dalla riga
            //$dbo->query('DELETE FROM `mg_prodotti` WHERE id_articolo = '.prepare($idarticolo).' AND id_riga_intervento = '.prepare($id_record));
        }

        break;

     /*
        Gestione righe generiche
    */
    case 'addriga':

        $descrizione = post('descrizione');
        $qta = post('qta');
        $um = post('um');
        $idiva = post('idiva');
        $prezzo_vendita = post('prezzo_vendita');
        $prezzo_acquisto = post('prezzo_acquisto');

        $sconto_unitario = $post['sconto'];
        $tipo_sconto = $post['tipo_sconto'];
        $sconto = calcola_sconto([
            'sconto' => $sconto_unitario,
            'prezzo' => $prezzo_vendita,
            'tipo' => $tipo_sconto,
            'qta' => $qta,
        ]);

        //Calcolo iva
        $rs_iva = $dbo->fetchArray('SELECT * FROM co_iva WHERE id='.prepare($idiva));
        $desc_iva = $rs_iva[0]['descrizione'];

        $iva = (($prezzo_vendita * $qta) - $sconto) * $rs_iva[0]['percentuale'] / 100;

        $idcontratto_riga = $post['idcontratto_riga'];

        $dbo->query('INSERT INTO co_righe_contratti_materiali(descrizione, qta, um, prezzo_vendita, prezzo_acquisto, idiva, desc_iva, iva, sconto, sconto_unitario, tipo_sconto, id_riga_contratto) VALUES ('.prepare($descrizione).', '.prepare($qta).', '.prepare($um).', '.prepare($prezzo_vendita).', '.prepare($prezzo_acquisto).', '.prepare($idiva).', '.prepare($desc_iva).', '.prepare($iva).', '.prepare($sconto).', '.prepare($sconto_unitario).', '.prepare($tipo_sconto).', '.prepare($idcontratto_riga).')');

    break;

    case 'editriga':

    $idriga = post('idriga');
    $descrizione = post('descrizione');
    $qta = post('qta');
    $um = post('um');
    $idiva = post('idiva');
    $prezzo_vendita = post('prezzo_vendita');
    $prezzo_acquisto = post('prezzo_acquisto');

    $sconto_unitario = $post['sconto'];
    $tipo_sconto = $post['tipo_sconto'];
    $sconto = calcola_sconto([
        'sconto' => $sconto_unitario,
        'prezzo' => $prezzo_vendita,
        'tipo' => $tipo_sconto,
        'qta' => $qta,
    ]);

    //Calcolo iva
    $rs_iva = $dbo->fetchArray('SELECT * FROM co_iva WHERE id='.prepare($idiva));
    $desc_iva = $rs_iva[0]['descrizione'];

    $iva = (($prezzo_vendita * $qta) - $sconto) * $rs_iva[0]['percentuale'] / 100;

    $dbo->query('UPDATE  co_righe_contratti_materiali SET '.
        ' descrizione='.prepare($descrizione).','.
        ' qta='.prepare($qta).','.
        ' um='.prepare($um).','.
        ' prezzo_vendita='.prepare($prezzo_vendita).','.
        ' prezzo_acquisto='.prepare($prezzo_acquisto).','.
        ' idiva='.prepare($idiva).','.
        ' desc_iva='.prepare($desc_iva).','.
        ' iva='.prepare($iva).','.
        ' sconto='.prepare($sconto).','.
        ' sconto_unitario='.prepare($sconto_unitario).','.
        ' tipo_sconto='.prepare($tipo_sconto).
        ' WHERE id='.prepare($idriga));

    break;

    case 'delriga':

        $idriga = post('idriga');
        $dbo->query('DELETE FROM co_righe_contratti_materiali WHERE id='.prepare($idriga).' '.Modules::getAdditionalsQuery($id_module));

    break;
}
