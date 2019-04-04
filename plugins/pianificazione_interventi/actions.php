<?php

include_once __DIR__.'/../../core.php';

$operazione = filter('op');

// Pianificazione intervento
switch ($operazione) {
    case 'add-promemoria':
        $dbo->insert('co_promemoria', [
            'idcontratto' => $id_parent,
            'data_richiesta' => filter('data_richiesta'),
            'idtipointervento' => filter('idtipointervento'),
        ]);
        $id_record = $dbo->lastInsertedID();

        echo $id_record;
    break;

    case 'edit-promemoria':
        $dbo->update('co_promemoria', [
            'data_richiesta' => post('data_richiesta'),
            'idtipointervento' => post('idtipointervento'),
            'richiesta' => post('richiesta'),
            'idimpianti' => implode(',', post('idimpianti')),
        ], ['id' => $id_record]);

        flash()->info(tr('Promemoria inserito!'));

        break;

    // Eliminazione pianificazione
    case 'delete-promemoria':
        $id = post('id');

        $dbo->query('DELETE FROM `co_promemoria` WHERE id='.prepare($id));
        $dbo->query('DELETE FROM `co_promemoria_righe` WHERE id_promemoria='.prepare($id));
        $dbo->query('DELETE FROM `co_promemoria_articoli` WHERE id_promemoria='.prepare($id));

        flash()->info(tr('Pianificazione eliminata!'));

        break;

    // Eliminazione tutti i promemoria di questo contratto con non hanno l'intervento associato
    case 'delete-non-associati':
        $dbo->query('DELETE FROM `co_promemoria_righe` WHERE id_promemoria IN (SELECT id FROM `co_promemoria` WHERE idcontratto = :id_contratto AND idintervento IS NULL)', [
            ':id_contratto' => $id_record,
        ]);

        $dbo->query('DELETE FROM `co_promemoria_articoli` WHERE id_promemoria IN (SELECT id FROM `co_promemoria` WHERE idcontratto = :id_contratto AND idintervento IS NULL)', [
            ':id_contratto' => $id_record,
        ]);

        $dbo->query('DELETE FROM `co_promemoria` WHERE idcontratto = :id_contratto AND idintervento IS NULL', [
            ':id_contratto' => $id_record,
        ]);

        flash()->info(tr('Tutti i promemoria non associati sono stati eliminati!'));

        break;

    // pianificazione ciclica
    case 'pianificazione':
        $intervallo = post('intervallo');
        $min_date = post('data_inizio');

        // if principale
        if (!empty($id_record) && !empty($intervallo) && post('pianifica_promemoria')) {
            $qp = 'SELECT *, (SELECT idanagrafica FROM co_contratti WHERE id = '.$id_parent.' ) AS idanagrafica, (SELECT data_conclusione FROM co_contratti WHERE id = '.$id_parent.' ) AS data_conclusione, (SELECT descrizione FROM in_tipiintervento WHERE idtipointervento=co_promemoria.idtipointervento) AS tipointervento FROM co_promemoria WHERE co_promemoria.id = '.$id_record;
            $rsp = $dbo->fetchArray($qp);

            $idtipointervento = $rsp[0]['idtipointervento'];
            $idsede = $rsp[0]['idsede'];
            $richiesta = $rsp[0]['richiesta'];

            $data_richiesta = $rsp[0]['data_richiesta'];
            $idimpianti = $rsp[0]['idimpianti'];

            // mi serve per la pianificazione dei promemoria
            $data_conclusione = $rsp[0]['data_conclusione'];

            // mi serve per la pianificazione interventi
            $idanagrafica = $rsp[0]['idanagrafica'];

            // se voglio pianificare anche le date precedenti ad oggi (parto da questo promemoria)
            //if ($data_inizio) {
            // oggi
            //$min_date = date('Y-m-d');
            //} else {
            //questo promemoria
            //$min_date = date('Y-m-d', strtotime($data_richiesta));
            //}
            $data_richiesta = $min_date;

            // inizio controllo data_conclusione, data valida e maggiore della $min_date
            if ((date('Y', strtotime($data_conclusione)) > 1970) && (date('Y-m-d', strtotime($min_date)) <= date('Y-m-d', strtotime($data_conclusione)))) {
                $i = 0;
                // Ciclo partendo dalla data_richiesta fino all data conclusione del contratto
                while (date('Y-m-d', strtotime($data_richiesta)) <= date('Y-m-d', strtotime($data_conclusione))) {
                    // calcolo nuova data richiesta, non considero l'intervallo al primo ciclo
                    $data_richiesta = date('Y-m-d', strtotime($data_richiesta.' + '.(($i == 0) ? 0 : $intervallo).' days'));
                    ++$i;

                    // controllo nuova data richiesta --> solo  date maggiori o uguali di data richiesta iniziale ma che non superano la data di fine del contratto
                    if ((date('Y-m-d', strtotime($data_richiesta)) >= $min_date) && (date('Y-m-d', strtotime($data_richiesta)) <= date('Y-m-d', strtotime($data_conclusione)))) {
                        // Controllo che non esista già un promemoria idcontratto, idtipointervento e data_richiesta.
                        if (count($dbo->fetchArray("SELECT id FROM co_promemoria WHERE data_richiesta = '".$data_richiesta."' AND idtipointervento = '".$idtipointervento."' AND idcontratto = '".$id_parent."' ")) == 0) {
                            // inserisco il nuovo promemoria
                            $query = 'INSERT INTO `co_promemoria`(`idcontratto`, `idtipointervento`, `data_richiesta`, `richiesta`, `idsede`, `idimpianti` ) VALUES('.prepare($id_parent).', '.prepare($idtipointervento).', '.prepare($data_richiesta).', '.prepare($richiesta).', '.prepare($idsede).', '.prepare($idimpianti).')';

                            if ($dbo->query($query)) {
                                $idriga = $dbo->lastInsertedID();

                                // copio anche righe materiali nel nuovo promemoria
                                $dbo->query('INSERT INTO co_promemoria_righe (descrizione, qta,um,prezzo_vendita,prezzo_acquisto,idiva,	desc_iva,iva,id_promemoria,sconto,sconto_unitario,tipo_sconto) SELECT descrizione, qta,um,prezzo_vendita,prezzo_acquisto,idiva,	desc_iva,iva,'.$idriga.',sconto,sconto_unitario,tipo_sconto FROM co_promemoria_righe WHERE id_promemoria = '.$id_record.'  ');

                                // copio righe articoli nel nuovo promemoria
                                $dbo->query('INSERT INTO co_promemoria_articoli (idarticolo, id_promemoria,descrizione,prezzo_acquisto,prezzo_vendita,sconto,	sconto_unitario,	tipo_sconto,idiva,desc_iva,iva,idautomezzo, qta, um, abilita_serial, idimpianto) SELECT idarticolo, '.$idriga.',descrizione,prezzo_acquisto,prezzo_vendita,sconto,sconto_unitario,tipo_sconto,idiva,desc_iva,iva,idautomezzo, qta, um, abilita_serial, idimpianto FROM co_promemoria_articoli WHERE id_promemoria = '.$id_record.'  ');

                                // Copia degli allegati
                                Uploads::copy([
                                    'id_plugin' => Plugins::get('Pianificazione interventi')['id'],
                                    'id_record' => $id_record,
                                ], [
                                    'id_plugin' => Plugins::get('Pianificazione interventi')['id'],
                                    'id_record' => $idriga,
                                ]);

                                flash()->info(tr('Promemoria intervento pianificato!'));
                            }
                        } else {
                            flash()->warning(tr('Esiste già un promemoria pianificato per il _DATE_', [
                                '_DATE_' => Translator::dateToLocale($data_richiesta),
                            ]));
                        }

                        // Controllo che non esista già un intervento collegato a questo promemoria e, se ho spuntato di creare l'intervento, creo già anche quello
                        if ((empty($dbo->fetchArray("SELECT idintervento FROM co_promemoria WHERE id = '".((empty($idriga)) ? $id_record : $idriga)."'")[0]['idintervento'])) && (post('pianifica_intervento'))) {
                            // pianificare anche l' intervento?
                            // if (post('pianifica_intervento')) {
                            /*$orario_inizio = post('orario_inizio');
                            $orario_fine = post('orario_fine');*/

                            // $idanagrafica = 2;

                            // intervento sempre nello stato "In programmazione"
                            $idstatointervento = 'WIP';

                            $codice = \Modules\Interventi\Intervento::getNextCodice();

                            // Creo intervento
                            $dbo->insert('in_interventi', [
                                'idanagrafica' => $idanagrafica,
                                'idclientefinale' => post('idclientefinale') ?: 0,
                                'idstatointervento' => $idstatointervento,
                                'idtipointervento' => $idtipointervento,
                                'idsede' => $idsede ?: 0,
                                'idautomezzo' => $idautomezzo ?: 0,

                                'codice' => $codice,
                                'data_richiesta' => $data_richiesta,
                                'richiesta' => $richiesta,
                            ]);

                            $idintervento = $dbo->lastInsertedID();

                            $idtecnici = post('idtecnico');

                            // aggiungo i tecnici
                            foreach ($idtecnici as $idtecnico) {
                                add_tecnico($idintervento, $idtecnico, $data_richiesta.' '.post('orario_inizio'), $data_richiesta.' '.post('orario_fine'), $id_parent);
                            }

                            // collego l'intervento ai promemoria
                            $dbo->query('UPDATE co_promemoria SET idintervento='.prepare($idintervento).' WHERE id='.prepare(((empty($idriga)) ? $id_record : $idriga)));

                            // copio le righe dal promemoria all'intervento
                            $dbo->query('INSERT INTO in_righe_interventi (descrizione, qta,um,prezzo_vendita,prezzo_acquisto,idiva,desc_iva,iva,idintervento,sconto,sconto_unitario,tipo_sconto) SELECT descrizione, qta,um,prezzo_vendita,prezzo_acquisto,idiva,desc_iva,iva,'.$idintervento.',sconto,sconto_unitario,tipo_sconto FROM co_promemoria_righe WHERE id_promemoria = '.$id_record.'  ');

                            // copio gli articoli dal promemoria all'intervento
                            $dbo->query('INSERT INTO mg_articoli_interventi (idarticolo, idintervento,descrizione,prezzo_acquisto,prezzo_vendita,sconto,	sconto_unitario,	tipo_sconto,idiva,desc_iva,iva,idautomezzo, qta, um, abilita_serial, idimpianto) SELECT idarticolo, '.$idintervento.',descrizione,prezzo_acquisto,prezzo_vendita,sconto,sconto_unitario,tipo_sconto,idiva,desc_iva,iva,idautomezzo, qta, um, abilita_serial, idimpianto FROM co_promemoria_articoli WHERE id_promemoria = '.$id_record.'  ');

                            // Copia degli allegati
                            Uploads::copy([
                                'id_plugin' => Plugins::get('Pianificazione interventi')['id'],
                                'id_record' => $id_record,
                            ], [
                                'id_module' => Modules::get('Interventi')['id'],
                                'id_record' => $idintervento,
                            ]);

                            // Decremento la quantità per ogni articolo copiato
                            $rs_articoli = $dbo->fetchArray('SELECT * FROM mg_articoli_interventi WHERE idintervento = '.$idintervento.' ');
                            foreach ($rs_articoli as $rs_articolo) {
                                add_movimento_magazzino($rs_articolo['idarticolo'], -$rs_articolo['qta'], ['idautomezzo' => $rs_articolo['idautomezzo'], 'idintervento' => $idintervento]);
                            }

                            // Collego gli impianti del promemoria all' intervento appena inserito
                            if (!empty($idimpianti)) {
                                $rs_idimpianti = explode(',', $idimpianti);
                                foreach ($rs_idimpianti as $idimpianto) {
                                    $dbo->query('INSERT INTO my_impianti_interventi (idintervento, idimpianto) VALUES ('.$idintervento.', '.prepare($idimpianto).' )');
                                }
                            }

                            flash()->info(tr('Interventi pianificati correttamente'));
                        } elseif (post('pianifica_intervento')) {
                            flash()->warning(tr('Esiste già un intervento pianificato per il _DATE_', [
                                '_DATE_' => Translator::dateToLocale($data_richiesta),
                            ]));
                        }
                    }
                }
                // fine ciclo while
            } else {
                flash()->error(tr("Nessuna data di conclusione del contratto oppure quest'ultima è già trascorsa, impossibile pianificare nuovi promemoria"));
            }
            // fine controllo data_conclusione
        } else {
            flash()->warning(tr('Nessun promemoria pianificato'));
        }
    break;

    /*
        GESTIONE ARTICOLI
    */

    case 'editarticolo':
        $idriga = post('idriga');
        $idarticolo = post('idarticolo');
        $idimpianto = post('idimpianto');

        // Leggo la quantità attuale nell'intervento
        $q = 'SELECT qta, idautomezzo, idimpianto FROM co_promemoria_articoli WHERE id='.prepare($idriga);
        $rs = $dbo->fetchArray($q);
        $old_qta = $rs[0]['qta'];
        $idimpianto = $rs[0]['idimpianto'];

        // Elimino questo articolo dall'intervento
        $dbo->query('DELETE FROM co_promemoria_articoli WHERE id='.prepare($idriga));

    // no break;

    case 'addarticolo':
        $idarticolo = post('idarticolo');
        // $idautomezzo = post('idautomezzo');
        $descrizione = post('descrizione');
        $idimpianto = post('idimpianto');
        $qta = post('qta');
        $um = post('um');
        $prezzo_vendita = post('prezzo_vendita');
        $idiva = post('idiva');

        $sconto_unitario = post('sconto');
        $tipo_sconto = post('tipo_sconto');
        $sconto = calcola_sconto([
            'sconto' => $sconto_unitario,
            'prezzo' => $prezzo_vendita,
            'tipo' => $tipo_sconto,
            'qta' => $qta,
        ]);

        // Calcolo iva
        $rs_iva = $dbo->fetchArray('SELECT * FROM co_iva WHERE id='.prepare($idiva));
        $desc_iva = $rs_iva[0]['descrizione'];

        $iva = (($prezzo_vendita * $qta) - $sconto) * $rs_iva[0]['percentuale'] / 100;

        // Aggiunto il collegamento fra l'articolo e l'intervento
        $idriga = $dbo->query('INSERT INTO co_promemoria_articoli(idarticolo, id_promemoria, idimpianto, idautomezzo, descrizione, prezzo_vendita, prezzo_acquisto, sconto, sconto_unitario, tipo_sconto, idiva, desc_iva, iva, qta, um, abilita_serial) VALUES ('.prepare($idarticolo).', '.prepare($id_record).', '.(empty($idimpianto) ? 'NULL' : prepare($idimpianto)).', '.prepare($idautomezzo).', '.prepare($descrizione).', '.prepare($prezzo_vendita).', '.prepare($prezzo_acquisto).', '.prepare($sconto).', '.prepare($sconto_unitario).', '.prepare($tipo_sconto).', '.prepare($idiva).', '.prepare($desc_iva).', '.prepare($iva).', '.prepare($qta).', '.prepare($um).', '.prepare($rsart[0]['abilita_serial']).')');

        break;

    case 'unlink_articolo':
        $idriga = post('idriga');

        $dbo->query('DELETE FROM co_promemoria_articoli WHERE id='.prepare($idriga));

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

        $sconto_unitario = post('sconto');
        $tipo_sconto = post('tipo_sconto');
        $sconto = calcola_sconto([
            'sconto' => $sconto_unitario,
            'prezzo' => $prezzo_vendita,
            'tipo' => $tipo_sconto,
            'qta' => $qta,
        ]);

        // Calcolo iva
        $rs_iva = $dbo->fetchArray('SELECT * FROM co_iva WHERE id='.prepare($idiva));
        $desc_iva = $rs_iva[0]['descrizione'];

        $iva = (($prezzo_vendita * $qta) - $sconto) * $rs_iva[0]['percentuale'] / 100;

        $dbo->query('INSERT INTO co_promemoria_righe(descrizione, qta, um, prezzo_vendita, prezzo_acquisto, idiva, desc_iva, iva, sconto, sconto_unitario, tipo_sconto, id_promemoria) VALUES ('.prepare($descrizione).', '.prepare($qta).', '.prepare($um).', '.prepare($prezzo_vendita).', '.prepare($prezzo_acquisto).', '.prepare($idiva).', '.prepare($desc_iva).', '.prepare($iva).', '.prepare($sconto).', '.prepare($sconto_unitario).', '.prepare($tipo_sconto).', '.prepare($id_record).')');

        break;

    case 'editriga':
        $idriga = post('idriga');
        $descrizione = post('descrizione');
        $qta = post('qta');
        $um = post('um');
        $idiva = post('idiva');
        $prezzo_vendita = post('prezzo_vendita');
        $prezzo_acquisto = post('prezzo_acquisto');

        $sconto_unitario = post('sconto');
        $tipo_sconto = post('tipo_sconto');
        $sconto = calcola_sconto([
            'sconto' => $sconto_unitario,
            'prezzo' => $prezzo_vendita,
            'tipo' => $tipo_sconto,
            'qta' => $qta,
        ]);

        // Calcolo iva
        $rs_iva = $dbo->fetchArray('SELECT * FROM co_iva WHERE id='.prepare($idiva));
        $desc_iva = $rs_iva[0]['descrizione'];

        $iva = (($prezzo_vendita * $qta) - $sconto) * $rs_iva[0]['percentuale'] / 100;

        $dbo->query('UPDATE  co_promemoria_righe SET '.
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

        $dbo->query('DELETE FROM co_promemoria_righe WHERE id='.prepare($idriga));

        break;
}
