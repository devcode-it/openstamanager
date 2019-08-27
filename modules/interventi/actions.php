<?php

include_once __DIR__.'/../../core.php';

use Models\Mail;
use Models\MailTemplate;
use Modules\Anagrafiche\Anagrafica;
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Interventi\Components\Articolo;
use Modules\Interventi\Components\Riga;
use Modules\Interventi\Components\Sconto;
use Modules\Interventi\Components\Sessione;
use Modules\Interventi\Intervento;
use Modules\Interventi\Stato;
use Modules\TipiIntervento\Tipo as TipoSessione;
use Notifications\EmailNotification;

switch (post('op')) {
    case 'update':
        $idcontratto = post('idcontratto');
        $idcontratto_riga = post('idcontratto_riga');

        // Rimozione del collegamento al promemoria
        if (!empty($idcontratto_riga) && $intervento->id_contratto != $idcontratto) {
            $dbo->update('co_promemoria', ['idintervento' => null], ['idintervento' => $id_record]);
        }

        // Salvataggio modifiche intervento
        $intervento->data_richiesta = post('data_richiesta');
        $intervento->data_scadenza = post('data_scadenza');
        $intervento->richiesta = post('richiesta');
        $intervento->descrizione = post('descrizione');
        $intervento->informazioniaggiuntive = post('informazioniaggiuntive');

        $intervento->idanagrafica = post('idanagrafica');
        $intervento->idclientefinale = post('idclientefinale');
        $intervento->idreferente = post('idreferente');
        $intervento->idtipointervento = post('idtipointervento');

        $intervento->idstatointervento = post('idstatointervento');
        $intervento->idsede_partenza = post('idsede_partenza');
        $intervento->idsede_destinazione = post('idsede_destinazione');
        $intervento->id_preventivo = post('idpreventivo');
        $intervento->id_contratto = $idcontratto;

        $intervento->id_documento_fe = post('id_documento_fe');
        $intervento->num_item = post('num_item');
        $intervento->codice_cup = post('codice_cup');
        $intervento->codice_cig = post('codice_cig');
        $intervento->save();

        // Notifica chiusura intervento
        $stato = $dbo->selectOne('in_statiintervento', '*', ['idstatointervento' => post('idstatointervento')]);
        if (!empty($stato['notifica']) && !empty($stato['destinatari']) && $stato['idstatointervento'] != $record['idstatointervento']) {
            $template = MailTemplate::find($stato['id_email']);

            $mail = Mail::build(auth()->getUser(), $template, $id_record);
            $mail->addReceiver($stato['destinatari']);
            $mail->save();

            $email = EmailNotification::build($mail);
            if ($email->send()) {
                flash()->info(tr('Notifica inviata'));
            } else {
                flash()->warning(tr("Errore nell'invio della notifica"));
            }
        }
        aggiorna_sedi_movimenti('interventi', $id_record);
        flash()->info(tr('Informazioni salvate correttamente!'));

        break;

    case 'add':
        if (post('id_intervento') == null) {
            $idanagrafica = post('idanagrafica');
            $idtipointervento = post('idtipointervento');
            $idstatointervento = post('idstatointervento');
            $data_richiesta = post('data_richiesta');
            $data_scadenza = post('data_scadenza');

            $anagrafica = Anagrafica::find($idanagrafica);
            $tipo = TipoSessione::find($idtipointervento);
            $stato = Stato::find($idstatointervento);

            $intervento = Intervento::build($anagrafica, $tipo, $stato, $data_richiesta);
            $id_record = $intervento->id;

            aggiorna_sedi_movimenti('interventi', $id_record);

            flash()->info(tr('Aggiunto nuovo intervento!'));

            // Informazioni di base
            $idpreventivo = post('idpreventivo');
            $idcontratto = post('idcontratto');
            $idcontratto_riga = post('idcontratto_riga');
            $idtipointervento = post('idtipointervento');
            $idsede_partenza = post('idsede_partenza');
            $idsede_destinazione = post('idsede_destinazione');
            $richiesta = post('richiesta');

            if (post('idclientefinale')) {
                $intervento->idclientefinale = post('idclientefinale');
            }

            if (post('idsede_destinazione')) {
                $intervento->idsede_destinazione = post('idsede_destinazione');
            }

            $intervento->id_preventivo = post('idpreventivo');
            $intervento->id_contratto = post('idcontratto');
            $intervento->richiesta = $richiesta;
            $intervento->data_scadenza = $data_scadenza;

            $intervento->save();

            // Se è specificato che l'intervento fa parte di una pianificazione aggiorno il codice dell'intervento sulla riga della pianificazione
            if (!empty($idcontratto_riga)) {
                $dbo->update('co_promemoria', [
                    'idintervento' => $id_record,
                    'idtipointervento' => $idtipointervento,
                    'data_richiesta' => $data_richiesta,
                    'richiesta' => $richiesta,
                    'idsede_destinazione' => $idsede_destinazione ?: 0,
                ], ['idcontratto' => $idcontratto, 'id' => $idcontratto_riga]);

                //copio le righe dal promemoria all'intervento
                $dbo->query('INSERT INTO in_righe_interventi (descrizione, qta,um,prezzo_vendita,prezzo_acquisto,idiva,desc_iva,iva,idintervento,sconto,sconto_unitario,tipo_sconto) SELECT descrizione, qta,um,prezzo_vendita,prezzo_acquisto,idiva,desc_iva,iva,'.$id_record.',sconto,sconto_unitario,tipo_sconto FROM co_promemoria_righe WHERE id_promemoria = '.$idcontratto_riga);

                //copio  gli articoli dal promemoria all'intervento
                $dbo->query('INSERT INTO mg_articoli_interventi (idarticolo, idintervento,descrizione,prezzo_acquisto,prezzo_vendita,sconto,	sconto_unitario,	tipo_sconto,idiva,desc_iva,iva, qta, um, abilita_serial, idimpianto) SELECT idarticolo, '.$id_record.',descrizione,prezzo_acquisto,prezzo_vendita,sconto,sconto_unitario,tipo_sconto,idiva,desc_iva,iva, qta, um, abilita_serial, idimpianto FROM co_promemoria_articoli WHERE id_promemoria = '.$idcontratto_riga);

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
                    add_movimento_magazzino($rs_articolo['idarticolo'], -$rs_articolo['qta'], ['idintervento' => $id_record]);
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
        aggiorna_sedi_movimenti('interventi', $id_record);
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
        $q = 'SELECT qta, idarticolo FROM mg_articoli_interventi WHERE idintervento='.prepare($id_record);
        $rs = $dbo->fetchArray($q);

        for ($i = 0; $i < count($rs); ++$i) {
            $qta = $rs[$i]['qta'];
            $idarticolo = $rs[$i]['idarticolo'];

            add_movimento_magazzino($idarticolo, $qta, ['idintervento' => $id_record]);
        }

        // Eliminazione associazioni tra interventi e contratti
        $query = 'UPDATE co_promemoria SET idintervento = NULL WHERE idintervento='.prepare($id_record);
        $dbo->query($query);

        // Eliminazione dell'intervento
        $query = 'DELETE FROM in_interventi WHERE id='.prepare($id_record);
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

        aggiorna_sedi_movimenti('interventi', $id_record);
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

        aggiorna_sedi_movimenti('interventi', $id_record);
        break;

    case 'delriga':
        $idriga = post('idriga');
        $dbo->query('DELETE FROM in_righe_interventi WHERE id='.prepare($idriga));

        break;

    case 'manage_sconto':
        if (post('idriga') != null) {
            $sconto = Sconto::find(post('idriga'));
        } else {
            $sconto = Sconto::build($intervento);
        }

        $sconto->descrizione = post('descrizione');
        $sconto->id_iva = post('idiva');

        $sconto->sconto_unitario = post('sconto_unitario');
        $sconto->tipo_sconto = 'UNT';

        $sconto->save();

        if (post('idriga') != null) {
            flash()->info(tr('Sconto/maggiorazione modificato!'));
        } else {
            flash()->info(tr('Sconto/maggiorazione aggiunto!'));
        }

        break;

    /*
        GESTIONE ARTICOLI
    */

    case 'editarticolo':
        $idriga = post('idriga');
        $idarticolo = post('idarticolo');
        $idimpianto = post('idimpianto');

        $idarticolo_originale = post('idarticolo_originale');

        // Leggo la quantità attuale nell'intervento
        $q = 'SELECT qta, idimpianto FROM mg_articoli_interventi WHERE idarticolo='.prepare($idarticolo_originale).' AND idintervento='.prepare($id_record);
        $rs = $dbo->fetchArray($q);
        $old_qta = $rs[0]['qta'];
        $idimpianto = $rs[0]['idimpianto'];

        $serials = array_column($dbo->select('mg_prodotti', 'serial', ['id_riga_intervento' => $idriga]), 'serial');

        add_movimento_magazzino($idarticolo_originale, $old_qta, ['idintervento' => $id_record]);

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
        $articolo = Articolo::build($intervento, $originale);

        $articolo->qta = post('qta');
        $articolo->descrizione = post('descrizione');
        $articolo->prezzo_unitario_vendita = post('prezzo_vendita');
        $articolo->prezzo_acquisto = post('prezzo_acquisto');
        $articolo->um = post('um');

        $articolo->sconto_unitario = post('sconto');
        $articolo->tipo_sconto = post('tipo_sconto');
        $articolo->id_iva = post('idiva');

        $articolo->save();

        aggiorna_sedi_movimenti('interventi', $id_record);

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
            $q = 'SELECT qta, idarticolo, idimpianto FROM mg_articoli_interventi WHERE id='.prepare($idriga);
            $rs = $dbo->fetchArray($q);
            $qta = $rs[0]['qta'];
            $idarticolo = $rs[0]['idarticolo'];
            $idimpianto = $rs[0]['idimpianto'];

            add_movimento_magazzino($idarticolo, $qta, ['idintervento' => $id_record]);

            // Elimino questo articolo dall'intervento
            $dbo->query('DELETE FROM mg_articoli_interventi WHERE id='.prepare($idriga).' AND idintervento='.prepare($id_record));

            // Elimino il collegamento al componente
            $dbo->query('DELETE FROM my_impianto_componenti WHERE idimpianto='.prepare($idimpianto).' AND idintervento='.prepare($id_record));

            // Elimino i seriali utilizzati dalla riga
            $dbo->query('DELETE FROM `mg_prodotti` WHERE id_articolo = '.prepare($idarticolo).' AND id_riga_intervento = '.prepare($id_record));
        }
        aggiorna_sedi_movimenti('interventi', $id_record);
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
        aggiorna_sedi_movimenti('interventi', $id_record);
        break;

    case 'firma':
        if (directory($docroot.'/files/interventi')) {
            if (post('firma_base64') != '') {
                // Salvataggio firma
                $firma_file = 'firma_'.time().'.jpg';
                $firma_nome = post('firma_nome');

                $data = explode(',', post('firma_base64'));

                $img = Intervention\Image\ImageManagerStatic::make(base64_decode($data[1]));
                $img->resize(680, 202, function ($constraint) {
                    $constraint->aspectRatio();
                });

                if (!$img->save($docroot.'/files/interventi/'.$firma_file)) {
                    flash()->error(tr('Impossibile creare il file!'));
                } elseif ($dbo->query('UPDATE in_interventi SET firma_file='.prepare($firma_file).', firma_data=NOW(), firma_nome = '.prepare($firma_nome).', idstatointervento = (SELECT idstatointervento FROM in_statiintervento WHERE descrizione = \'Completato\') WHERE id='.prepare($id_record))) {
                    flash()->info(tr('Firma salvata correttamente!'));
                    flash()->info(tr('Attività completata!'));

                    $stato = $dbo->selectOne('in_statiintervento', '*', ['descrizione' => 'Completato']);
                    // Notifica chiusura intervento
                    if (!empty($stato['notifica']) && !empty($stato['destinatari'])) {
                        $template = MailTemplate::find($stato['id_email']);

                        $mail = Mail::build(auth()->getUser(), $template, $id_record);
                        $mail->addReceiver($stato['destinatari']);
                        $mail->save();

                        $email = EmailNotification::build($mail);
                        if ($email->send()) {
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

        $idcontratto = $intervento['id_contratto'];

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

        // Notifica rimozione dell' intervento al tecnico
        if (!empty($tecnico['email'])) {
            $template = MailTemplate::get('Notifica rimozione intervento');

            $mail = Mail::build(auth()->getUser(), $template, $id_record);
            $mail->addReceiver($tecnico['email']);
            $mail->save();

            $email = EmailNotification::build($mail);
            if ($email->send()) {
                flash()->info(tr('Notifica inviata'));
            } else {
                flash()->warning(tr("Errore nell'invio della notifica"));
            }
        }

        break;

    case 'edit_sessione':
        $id_sessione = post('id_sessione');
        $sessione = Sessione::find($id_sessione);

        $sessione->orario_inizio = post('orario_inizio');
        $sessione->orario_fine = post('orario_fine');
        $sessione->km = post('km');

        $id_tipo = post('idtipointerventot');
        $sessione->setTipo($id_tipo);

        // Prezzi
        $sessione->prezzo_ore_unitario = post('prezzo_ore_unitario');
        $sessione->prezzo_km_unitario = post('prezzo_km_unitario');
        $sessione->prezzo_dirittochiamata = post('prezzo_dirittochiamata');

        // Sconto orario
        $sessione->sconto_unitario = post('sconto');
        $sessione->tipo_sconto = post('tipo_sconto');

        // Sconto chilometrico
        $sessione->scontokm_unitario = post('sconto_km');
        $sessione->tipo_scontokm = post('tipo_sconto_km');

        $sessione->save();

        break;
}
