<?php

include_once __DIR__.'/../../core.php';

use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Interventi\Components\Articolo;
use Modules\Interventi\Intervento;

switch (post('op')) {
    case 'update':
        $idpreventivo = post('idpreventivo');
        $idcontratto = post('idcontratto');
        $idcontratto_riga = post('idcontratto_riga');

        $idtipointervento = post('idtipointervento');

        $data_richiesta = post('data_richiesta');
        $richiesta = post('richiesta');
        $idsede = post('idsede');

        // Collegamento intervento a contratto (se impostato)
        // Oltre al collegamento al contratto, l'intervento è collegato ad una riga di pianificazione, perciò è importante considerarla se è impostata
        $array = [
            'idintervento' => $id_record,
            'idtipointervento' => $idtipointervento,
            'data_richiesta' => $data_richiesta,
            'richiesta' => $richiesta,
            'idsede' => $idsede ?: 0,
        ];
        // Creazione nuova pianificazione se non era impostata
        if (!empty($idcontratto) && empty($idcontratto_riga)) {
            // Se questo intervento era collegato ad un altro contratto aggiorno le informazioni...
            $rs = $dbo->fetchArray('SELECT id FROM co_promemoria WHERE idintervento='.prepare($id_record));
            if (empty($rs)) {
                $dbo->insert('co_promemoria', array_merge(['idcontratto' => $idcontratto], $array));
            }

            // ...altrimenti se sto cambiando contratto aggiorno solo l'id del nuovo contratto
            else {
                $dbo->update('co_promemoria', ['idcontratto' => $idcontratto], ['idintervento' => $id_record]);
            }
        }

        // Pianificazione già impostata, aggiorno solo il codice intervento
        elseif (!empty($idcontratto) && !empty($idcontratto_riga)) {
            $dbo->update('co_promemoria', $array, ['idcontratto' => $idriga, 'id' => $idcontratto_riga]);
        }

        // Se non è impostato nessun contratto o riga, tolgo il collegamento dell'intervento al contratto
        elseif (empty($idcontratto)) {
            $dbo->update('co_promemoria', ['idintervento' => null], ['idintervento' => $id_record]);
        }

        $tipo_sconto = post('tipo_sconto_globale');
        $sconto = post('sconto_globale');

        // Salvataggio modifiche intervento
        $dbo->update('in_interventi', [
            'data_richiesta' => $data_richiesta,
            'richiesta' => $richiesta,
            'descrizione' => post('descrizione'),
            'informazioniaggiuntive' => post('informazioniaggiuntive'),

            'idanagrafica' => post('idanagrafica'),
            'idclientefinale' => post('idclientefinale'),
            'idreferente' => post('idreferente'),
            'idtipointervento' => $idtipointervento,

            'idstatointervento' => post('idstatointervento'),
            'idsede' => $idsede,
            'idautomezzo' => post('idautomezzo'),
            'id_preventivo' => $idpreventivo,

            'sconto_globale' => $sconto,
            'tipo_sconto_globale' => $tipo_sconto,

            'id_documento_fe' => post('id_documento_fe'),
            'codice_cup' => post('codice_cup'),
            'codice_cig' => post('codice_cig'),
        ], ['id' => $id_record]);

        $stato = $dbo->selectOne('in_statiintervento', '*', ['idstatointervento' => post('idstatointervento')]);
        // Notifica chiusura intervento
        if (!empty($stato['notifica']) && !empty($stato['destinatari']) && $stato['idstatointervento'] != $record['idstatointervento']) {
            $n = new Notifications\EmailNotification();

            $n->setTemplate($stato['id_email'], $id_record);
            $n->setReceivers($stato['destinatari']);

            if ($n->send()) {
                flash()->info(tr('Notifica inviata'));
            } else {
                flash()->warning(tr("Errore nell'invio della notifica"));
            }
        }

        flash()->info(tr('Informazioni salvate correttamente!'));

        break;

    case 'add':
        if (post('id_intervento') == null) {
            $formato = setting('Formato codice intervento');
            $template = str_replace('#', '%', $formato);

            $rs = $dbo->fetchArray('SELECT codice FROM in_interventi WHERE codice=(SELECT MAX(CAST(codice AS SIGNED)) FROM in_interventi) AND codice LIKE '.prepare(Util\Generator::complete($template)).' ORDER BY codice DESC LIMIT 0,1');
            if (!empty($rs[0]['codice'])) {
                $codice = Util\Generator::generate($formato, $rs[0]['codice']);
            }

            if (empty($codice)) {
                $rs = $dbo->fetchArray('SELECT codice FROM in_interventi WHERE codice LIKE '.prepare(Util\Generator::complete($template)).' ORDER BY codice DESC LIMIT 0,1');
                $codice = Util\Generator::generate($formato, $rs[0]['codice']);
            }

            // Informazioni di base
            $idpreventivo = post('idpreventivo');
            $idcontratto = post('idcontratto');
            $idcontratto_riga = post('idcontratto_riga');
            $idtipointervento = post('idtipointervento');
            $idsede = post('idsede');
            $data_richiesta = post('data_richiesta');
            $richiesta = post('richiesta');
            $idautomezzo = null;

            if (!empty($codice) && !empty(post('idanagrafica')) && !empty(post('idtipointervento'))) {
                // Salvataggio modifiche intervento
                $dbo->insert('in_interventi', [
                    'idanagrafica' => post('idanagrafica'),
                    'idclientefinale' => post('idclientefinale') ?: 0,
                    'idstatointervento' => post('idstatointervento'),
                    'idtipointervento' => $idtipointervento,
                    'idsede' => $idsede ?: 0,
                    'idautomezzo' => $idautomezzo ?: 0,
                    'id_preventivo' => $idpreventivo,

                    'codice' => $codice,
                    'data_richiesta' => $data_richiesta,
                    'richiesta' => $richiesta,
                ]);

                $id_record = $dbo->lastInsertedID();

                flash()->info(tr('Aggiunto nuovo intervento!'));
            }

            // Collego l'intervento al contratto
            if (!empty($idcontratto)) {
                $array = [
                    'idintervento' => $id_record,
                    'idtipointervento' => $idtipointervento,
                    'data_richiesta' => $data_richiesta,
                    'richiesta' => $richiesta,
                    'idsede' => $idsede ?: 0,
                ];

                // Se è specificato che l'intervento fa parte di una pianificazione aggiorno il codice dell'intervento sulla riga della pianificazione
                if (!empty($idcontratto_riga)) {
                    $dbo->update('co_promemoria', $array, ['idcontratto' => $idcontratto, 'id' => $idcontratto_riga]);

                    //copio le righe dal promemoria all'intervento
                    $dbo->query('INSERT INTO in_righe_interventi (descrizione, qta,um,prezzo_vendita,prezzo_acquisto,idiva,desc_iva,iva,idintervento,sconto,sconto_unitario,tipo_sconto) SELECT descrizione, qta,um,prezzo_vendita,prezzo_acquisto,idiva,desc_iva,iva,'.$id_record.',sconto,sconto_unitario,tipo_sconto FROM co_promemoria_righe WHERE id_promemoria = '.$idcontratto_riga.'  ');

                    //copio  gli articoli dal promemoria all'intervento
                    $dbo->query('INSERT INTO mg_articoli_interventi (idarticolo, idintervento,descrizione,prezzo_acquisto,prezzo_vendita,sconto,	sconto_unitario,	tipo_sconto,idiva,desc_iva,iva,idautomezzo, qta, um, abilita_serial, idimpianto) SELECT idarticolo, '.$id_record.',descrizione,prezzo_acquisto,prezzo_vendita,sconto,sconto_unitario,tipo_sconto,idiva,desc_iva,iva,idautomezzo, qta, um, abilita_serial, idimpianto FROM co_promemoria_articoli WHERE id_promemoria = '.$idcontratto_riga.'  ');

                    // Copia degli allegati
                    $alleagti = Uploads::copy([
                        'id_plugin' => Plugins::get('Pianificazione interventi')['id'],
                        'id_record' => $idcontratto_riga,
                    ], [
                        'id_module' => $id_module,
                        'id_record' => $id_record,
                    ]);

                    if (!$alleagti) {
                        $errors = error_get_last();
                        flash()->warning(tr('Errore durante la copia degli allegati'));
                    }

                    // Decremento la quantità per ogni articolo copiato
                    $rs_articoli = $dbo->fetchArray('SELECT * FROM mg_articoli_interventi WHERE idintervento = '.$id_record.' ');
                    foreach ($rs_articoli as $rs_articolo) {
                        add_movimento_magazzino($rs_articolo['idarticolo'], -$rs_articolo['qta'], ['idautomezzo' => $rs_articolo['idautomezzo'], 'idintervento' => $id_record]);
                    }
                } else {
                    $dbo->insert('co_promemoria', [
                        'idcontratto' => $idcontratto,
                        'idintervento' => $id_record,
                        'idtipointervento' => $idtipointervento,
                        'data_richiesta' => $data_richiesta,
                        'richiesta' => $richiesta,
                        'idsede' => $idsede ?: 0,
                    ]);
                }
            }

            if (!empty(post('idordineservizio'))) {
                $dbo->query('UPDATE co_ordiniservizio SET idintervento='.prepare($id_record).' WHERE id='.prepare(post('idordineservizio')));
            }

            // Collegamenti intervento/impianti
            $impianti = (array) post('idimpianti');
            if (!empty($impianti)) {
                foreach ($impianti as $impianto) {
                    $dbo->insert('my_impianti_interventi', [
                        'idintervento' => $id_record,
                        'idimpianto' => $impianto,
                    ]);
                }

                // Collegamenti intervento/componenti
                $componenti = (array) post('componenti');
                foreach ($componenti as $componente) {
                    $dbo->insert('my_componenti_interventi', [
                        'id_intervento' => $id_record,
                        'id_componente' => $componente,
                    ]);
                }
            }
        } else {
            $id_record = post('id_intervento');

            $idcontratto = $dbo->fetchOne('SELECT idcontratto FROM co_promemoria WHERE idintervento = :id', [
                ':id' => $id_record,
            ])['idcontratto'];
        }

        // Collegamenti tecnici/interventi
        $idtecnici = post('idtecnico');
        foreach ($idtecnici as $idtecnico) {
            add_tecnico($id_record, $idtecnico, post('orario_inizio'), post('orario_fine'), $idcontratto);
        }

        if (post('ref') == 'dashboard') {
            flash()->clearMessage('info');
            flash()->clearMessage('warning');
        }

        break;

    // Eliminazione intervento
    case 'delete':
        // Elimino anche eventuali file caricati
        Uploads::deleteLinked([
            'id_module' => $id_module,
            'id_record' => $id_record,
        ]);

        $codice = $dbo->fetchArray('SELECT codice FROM in_interventi WHERE id='.prepare($id_record))[0]['codice'];

        /*
            Riporto in magazzino gli articoli presenti nell'intervento in cancellazine
        */
        // Leggo la quantità attuale nell'intervento
        $q = 'SELECT qta, idautomezzo, idarticolo FROM mg_articoli_interventi WHERE idintervento='.prepare($id_record);
        $rs = $dbo->fetchArray($q);

        for ($i = 0; $i < count($rs); ++$i) {
            $qta = $rs[$i]['qta'];
            $idautomezzo = $rs[$i]['idautomezzo'];
            $idarticolo = $rs[$i]['idarticolo'];

            add_movimento_magazzino($idarticolo, $qta, ['idautomezzo' => $idautomezzo, 'idintervento' => $id_record]);
        }

        // Eliminazione associazioni tra interventi e contratti
        $query = 'UPDATE co_promemoria SET idintervento = NULL WHERE idintervento='.prepare($id_record);
        $dbo->query($query);

        // Eliminazione dell'intervento
        $query = 'DELETE FROM in_interventi WHERE id='.prepare($id_record).' '.Modules::getAdditionalsQuery($id_module);
        $dbo->query($query);

        // Elimino i collegamenti degli articoli a questo intervento
        $dbo->query('DELETE FROM mg_articoli_interventi WHERE idintervento='.prepare($id_record));

        // Elimino il collegamento al componente
        $dbo->query('DELETE FROM my_impianto_componenti WHERE idintervento='.prepare($id_record));

        // Eliminazione associazione tecnici collegati all'intervento
        $query = 'DELETE FROM in_interventi_tecnici WHERE idintervento='.prepare($id_record);
        $dbo->query($query);

        // Eliminazione righe aggiuntive dell'intervento
        $query = 'DELETE FROM in_righe_interventi WHERE idintervento='.prepare($id_record);
        $dbo->query($query);

        // Eliminazione associazione interventi e articoli
        $query = 'DELETE FROM mg_articoli_interventi WHERE idintervento='.prepare($id_record);
        $dbo->query($query);

        // Eliminazione associazione interventi e my_impianti
        $query = 'DELETE FROM my_impianti_interventi WHERE idintervento='.prepare($id_record);
        $dbo->query($query);

        // Eliminazione movimenti riguardanti l'intervento cancellato
        $dbo->query('DELETE FROM mg_movimenti WHERE idintervento='.prepare($id_record));

        flash()->info(tr('Intervento _NUM_ eliminato!', [
            '_NUM_' => "'".$codice."'",
        ]));

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

        //Calcolo iva
        $rs_iva = $dbo->fetchArray('SELECT * FROM co_iva WHERE id='.prepare($idiva));
        $desc_iva = $rs_iva[0]['descrizione'];

        $iva = (($prezzo_vendita * $qta) - $sconto) * $rs_iva[0]['percentuale'] / 100;

        $dbo->query('INSERT INTO in_righe_interventi(descrizione, qta, um, prezzo_vendita, prezzo_acquisto, idiva, desc_iva, iva, sconto, sconto_unitario, tipo_sconto, idintervento) VALUES ('.prepare($descrizione).', '.prepare($qta).', '.prepare($um).', '.prepare($prezzo_vendita).', '.prepare($prezzo_acquisto).', '.prepare($idiva).', '.prepare($desc_iva).', '.prepare($iva).', '.prepare($sconto).', '.prepare($sconto_unitario).', '.prepare($tipo_sconto).', '.prepare($id_record).')');

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

        //Calcolo iva
        $rs_iva = $dbo->fetchArray('SELECT * FROM co_iva WHERE id='.prepare($idiva));
        $desc_iva = $rs_iva[0]['descrizione'];

        $iva = (($prezzo_vendita * $qta) - $sconto) * $rs_iva[0]['percentuale'] / 100;

        $dbo->query('UPDATE in_righe_interventi SET '.
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
        $dbo->query('DELETE FROM in_righe_interventi WHERE id='.prepare($idriga).' '.Modules::getAdditionalsQuery($id_module));

        break;

    /*
        GESTIONE ARTICOLI
    */

    case 'editarticolo':
        $idriga = post('idriga');
        $idarticolo = post('idarticolo');
        $idimpianto = post('idimpianto');
        $idautomezzo = post('idautomezzo');

        $idarticolo_originale = post('idarticolo_originale');

        // Leggo la quantità attuale nell'intervento
        $q = 'SELECT qta, idautomezzo, idimpianto FROM mg_articoli_interventi WHERE idarticolo='.prepare($idarticolo_originale).' AND idintervento='.prepare($id_record);
        $rs = $dbo->fetchArray($q);
        $old_qta = $rs[0]['qta'];
        $idimpianto = $rs[0]['idimpianto'];
        $idautomezzo = $rs[0]['idautomezzo'];

        $serials = array_column($dbo->select('mg_prodotti', 'serial', ['id_riga_intervento' => $idriga]), 'serial');

        add_movimento_magazzino($idarticolo_originale, $old_qta, ['idautomezzo' => $idautomezzo, 'idintervento' => $id_record]);

        // Elimino questo articolo dall'intervento
        $dbo->query('DELETE FROM mg_articoli_interventi WHERE id='.prepare($idriga));

        // Elimino il collegamento al componente
        $dbo->query('DELETE FROM my_impianto_componenti WHERE idimpianto='.prepare($idimpianto).' AND idintervento='.prepare($id_record));

        /* Ricollego l'articolo modificato all'intervento */
        /* ci può essere il caso in cui cambio idarticolo e anche qta */

        // no break
    case 'addarticolo':
        $originale = ArticoloOriginale::find(post('idarticolo'));
        $intervento = Intervento::find($id_record);
        $articolo = Articolo::build($intervento, $originale, post('idautomezzo'));

        $articolo->qta = post('qta');
        $articolo->descrizione = post('descrizione');
        $articolo->prezzo_unitario_vendita = post('prezzo_vendita');
        $articolo->prezzo_acquisto = post('prezzo_acquisto');
        $articolo->um = post('um');

        $articolo->sconto_unitario = post('sconto');
        $articolo->tipo_sconto = post('tipo_sconto');
        $articolo->id_iva = post('idiva');

        $articolo->save();

        // Aggiorno l'automezzo dell'intervento
        $dbo->query('UPDATE in_interventi SET idautomezzo='.prepare(post('idautomezzo')).' WHERE id='.prepare($id_record).' '.Modules::getAdditionalsQuery($id_module));

        if (!empty($serials)) {
            if ($old_qta > $qta) {
                $serials = array_slice($serials, 0, $qta);
            }

            $articolo->serials = $serials;
        }

        link_componente_to_articolo($id_record, $idimpianto, $idarticolo, $qta);

        break;

    case 'unlink_articolo':
        $idriga = post('idriga');
        $idarticolo = post('idarticolo');

        // Riporto la merce nel magazzino
        if (!empty($idriga) && !empty($id_record)) {
            // Leggo la quantità attuale nell'intervento
            $q = 'SELECT qta, idautomezzo, idarticolo, idimpianto FROM mg_articoli_interventi WHERE id='.prepare($idriga);
            $rs = $dbo->fetchArray($q);
            $qta = $rs[0]['qta'];
            $idarticolo = $rs[0]['idarticolo'];
            $idimpianto = $rs[0]['idimpianto'];
            $idautomezzo = $rs[0]['idautomezzo'];

            add_movimento_magazzino($idarticolo, $qta, ['idautomezzo' => $idautomezzo, 'idintervento' => $id_record]);

            // Elimino questo articolo dall'intervento
            $dbo->query('DELETE FROM mg_articoli_interventi WHERE id='.prepare($idriga).' AND idintervento='.prepare($id_record));

            // Elimino il collegamento al componente
            $dbo->query('DELETE FROM my_impianto_componenti WHERE idimpianto='.prepare($idimpianto).' AND idintervento='.prepare($id_record));

            // Elimino i seriali utilizzati dalla riga
            $dbo->query('DELETE FROM `mg_prodotti` WHERE id_articolo = '.prepare($idarticolo).' AND id_riga_intervento = '.prepare($id_record));
        }

        break;

    case 'add_serial':
        $idriga = post('idriga');
        $idarticolo = post('idarticolo');

        $serials = (array) post('serial');
        foreach ($serials as $key => $value) {
            if (empty($value)) {
                unset($serials[$key]);
            }
        }

        $dbo->sync('mg_prodotti', ['id_riga_intervento' => $idriga, 'dir' => 'entrata', 'id_articolo' => $idarticolo], ['serial' => $serials]);

        break;

    case 'firma':
        if (directory($docroot.'/files/interventi')) {
            if (post('firma_base64') != '') {
                // Salvataggio firma
                $firma_file = 'firma_'.time().'.jpg';
                $firma_nome = post('firma_nome');

                $data = explode(',', post('firma_base64'));

                $img = Intervention\Image\ImageManagerStatic::build(base64_decode($data[1]));
                $img->resize(680, 202, function ($constraint) {
                    $constraint->aspectRatio();
                });

                if (!$img->save($docroot.'/files/interventi/'.$firma_file)) {
                    flash()->error(tr('Impossibile creare il file!'));
                } elseif ($dbo->query('UPDATE in_interventi SET firma_file='.prepare($firma_file).', firma_data=NOW(), firma_nome = '.prepare($firma_nome).', idstatointervento = "OK" WHERE id='.prepare($id_record))) {
                    flash()->info(tr('Firma salvata correttamente!'));
                    flash()->info(tr('Attività completata!'));

                    $stato = $dbo->selectOne('in_statiintervento', '*', ['idstatointervento' => 'OK']);
                    // Notifica chiusura intervento
                    if (!empty($stato['notifica']) && !empty($stato['destinatari'])) {
                        $n = new Notifications\EmailNotification();

                        $n->setTemplate($stato['id_email'], $id_record);
                        $n->setReceivers($stato['destinatari']);

                        if ($n->send()) {
                            flash()->info(tr('Notifica inviata'));
                        } else {
                            flash()->warning(tr("Errore nell'invio della notifica"));
                        }
                    }
                } else {
                    flash()->error(tr('Errore durante il salvataggio della firma nel database!'));
                }
            } else {
                flash()->error(tr('Errore durante il salvataggio della firma!').tr('La firma risulta vuota').'...');
            }
        } else {
            flash()->error(tr("Non è stato possibile creare la cartella _DIRECTORY_ per salvare l'immagine della firma!", [
                '_DIRECTORY_' => '<b>/files/interventi</b>',
            ]));
        }

        break;

    // OPERAZIONI PER AGGIUNTA NUOVA SESSIONE DI LAVORO
    case 'add_sessione':
        $id_tecnico = post('id_tecnico');

        // Verifico se l'intervento è collegato ad un contratto
        $rs = $dbo->fetchArray('SELECT idcontratto FROM co_promemoria WHERE idintervento='.prepare($id_record));
        $idcontratto = $rs[0]['idcontratto'];

        $ore = 1;

        $inizio = date('Y-m-d H:\0\0');
        $fine = date_modify(date_create(date('Y-m-d H:\0\0')), '+'.$ore.' hours')->format('Y-m-d H:\0\0');

        add_tecnico($id_record, $id_tecnico, $inizio, $fine, $idcontratto);
        break;

    // RIMOZIONE SESSIONE DI LAVORO
    case 'delete_sessione':
        $id_sessione = post('id_sessione');

        $tecnico = $dbo->fetchOne('SELECT an_anagrafiche.email FROM an_anagrafiche INNER JOIN in_interventi_tecnici ON in_interventi_tecnici.idtecnico = an_anagrafiche.idanagrafica WHERE in_interventi_tecnici.id = '.prepare($id_sessione));

        $dbo->query('DELETE FROM in_interventi_tecnici WHERE id='.prepare($id_sessione));

        // Notifica nuovo intervento al tecnico
        if (!empty($tecnico['email'])) {
            $n = new Notifications\EmailNotification();

            $n->setTemplate('Notifica rimozione intervento', $id_record);
            $n->setReceivers($tecnico['email']);

            if ($n->send()) {
                flash()->info(tr('Notifica inviata'));
            } else {
                flash()->warning(tr("Errore nell'invio della notifica"));
            }
        }

        break;

    case 'edit_sessione':
        $id_sessione = post('id_sessione');

        // Lettura delle date di inizio e fine intervento
        $orario_inizio = post('orario_inizio');
        $orario_fine = post('orario_fine');

        // Ricalcolo le ore lavorate
        $ore = calcola_ore_intervento($orario_inizio, $orario_fine);

        $km = post('km');

        // Lettura tariffe in base al tipo di intervento ed al tecnico
        $idtipointervento_tecnico = post('idtipointerventot');
        $rs = $dbo->fetchArray('SELECT * FROM in_interventi_tecnici WHERE idtecnico='.prepare(post('idtecnico')).' AND idintervento='.prepare($id_record));

        if ($idtipointervento_tecnico != $rs[0]['idtipointervento']) {
            $rsc = $dbo->fetchArray('SELECT * FROM in_tariffe WHERE idtecnico='.prepare(post('idtecnico')).' AND idtipointervento='.prepare($idtipointervento_tecnico));

            if ($rsc[0]['costo_ore'] != 0 || $rsc[0]['costo_km'] != 0 || $rsc[0]['costo_dirittochiamata'] != 0 || $rsc[0]['costo_ore_tecnico'] != 0 || $rsc[0]['costo_km_tecnico'] != 0 || $rsc[0]['costo_dirittochiamata_tecnico'] != 0) {
                $prezzo_ore_unitario = $rsc[0]['costo_ore'];
                $prezzo_km_unitario = $rsc[0]['costo_km'];
                $prezzo_dirittochiamata = $rsc[0]['costo_dirittochiamata'];

                $prezzo_ore_unitario_tecnico = $rsc[0]['costo_ore_tecnico'];
                $prezzo_km_unitario_tecnico = $rsc[0]['costo_km_tecnico'];
                $prezzo_dirittochiamata_tecnico = $rsc[0]['costo_dirittochiamata_tecnico'];
            }

            // ...altrimenti se non c'è una tariffa per il tecnico leggo i costi globali
            else {
                $rsc = $dbo->fetchArray('SELECT * FROM in_tipiintervento WHERE idtipointervento='.prepare($idtipointervento_tecnico));

                $prezzo_ore_unitario = $rsc[0]['costo_orario'];
                $prezzo_km_unitario = $rsc[0]['costo_km'];
                $prezzo_dirittochiamata = $rsc[0]['costo_diritto_chiamata'];

                $prezzo_ore_unitario_tecnico = $rsc[0]['costo_orario_tecnico'];
                $prezzo_km_unitario_tecnico = $rsc[0]['costo_km_tecnico'];
                $prezzo_dirittochiamata_tecnico = $rsc[0]['costo_diritto_chiamata_tecnico'];
            }
        } else {
            $prezzo_ore_unitario = $rs[0]['prezzo_ore_unitario'];
            $prezzo_km_unitario = $rs[0]['prezzo_km_unitario'];
            $prezzo_dirittochiamata = $rs[0]['prezzo_dirittochiamata'];
            $prezzo_ore_unitario_tecnico = $rs[0]['prezzo_ore_unitario_tecnico'];
            $prezzo_km_unitario_tecnico = $rs[0]['prezzo_km_unitario_tecnico'];
            $prezzo_dirittochiamata_tecnico = $rs[0]['prezzo_dirittochiamata_tecnico'];
        }

        // Totali
        $prezzo_ore_consuntivo = $prezzo_ore_unitario * $ore;
        $prezzo_km_consuntivo = $prezzo_km_unitario * $km;

        $prezzo_ore_consuntivo_tecnico = $prezzo_ore_unitario_tecnico * $ore;
        $prezzo_km_consuntivo_tecnico = $prezzo_km_unitario_tecnico * $km;

        // Sconti
        $sconto_unitario = post('sconto');
        $tipo_sconto = post('tipo_sconto');
        $sconto = calcola_sconto([
            'sconto' => $sconto_unitario,
            'prezzo' => $prezzo_ore_consuntivo,
            'tipo' => $tipo_sconto,
        ]);

        $scontokm_unitario = post('sconto_km');
        $tipo_scontokm = post('tipo_sconto_km');
        $scontokm = ($tipo_scontokm == 'PRC') ? ($prezzo_km_consuntivo * $scontokm_unitario) / 100 : $scontokm_unitario;

        $dbo->update('in_interventi_tecnici', [
            'idtipointervento' => $idtipointervento_tecnico,

            'orario_inizio' => $orario_inizio,
            'orario_fine' => $orario_fine,
            'ore' => $ore,
            'km' => $km,

            'prezzo_ore_unitario' => $prezzo_ore_unitario,
            'prezzo_km_unitario' => $prezzo_km_unitario,
            'prezzo_dirittochiamata' => $prezzo_dirittochiamata,
            'prezzo_ore_unitario_tecnico' => $prezzo_ore_unitario_tecnico,
            'prezzo_km_unitario_tecnico' => $prezzo_km_unitario_tecnico,
            'prezzo_dirittochiamata_tecnico' => $prezzo_dirittochiamata_tecnico,

            'prezzo_ore_consuntivo' => $prezzo_ore_consuntivo,
            'prezzo_km_consuntivo' => $prezzo_km_consuntivo,
            'prezzo_ore_consuntivo_tecnico' => $prezzo_ore_consuntivo_tecnico,
            'prezzo_km_consuntivo_tecnico' => $prezzo_km_consuntivo_tecnico,

            'sconto' => $sconto,
            'sconto_unitario' => $sconto_unitario,
            'tipo_sconto' => $tipo_sconto,

            'scontokm' => $scontokm,
            'scontokm_unitario' => $scontokm_unitario,
            'tipo_scontokm' => $tipo_scontokm,
        ], ['id' => $id_sessione]);

        break;
}
