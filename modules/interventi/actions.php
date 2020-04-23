<?php

include_once __DIR__.'/../../core.php';

use Modules\Anagrafiche\Anagrafica;
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Emails\Mail;
use Modules\Emails\Template;
use Modules\Interventi\Components\Articolo;
use Modules\Interventi\Components\Riga;
use Modules\Interventi\Components\Sconto;
use Modules\Interventi\Components\Sessione;
use Modules\Interventi\Intervento;
use Modules\Interventi\Stato;
use Modules\TipiIntervento\Tipo as TipoSessione;
use Plugins\PianificazioneInterventi\Promemoria;

switch (post('op')) {
    case 'update':
        $idcontratto = post('idcontratto');
        $id_promemoria = post('idcontratto_riga');

        // Rimozione del collegamento al promemoria
        if (!empty($id_promemoria) && $intervento->id_contratto != $idcontratto) {
            $dbo->update('co_promemoria', ['idintervento' => null], ['idintervento' => $id_record]);
        }

        // Salvataggio modifiche intervento
        $intervento->data_richiesta = post('data_richiesta');
        $intervento->data_scadenza = post('data_scadenza') ?: null;
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
            $template = Template::find($stato['id_email']);

            $mail = Mail::build(auth()->getUser(), $template, $id_record);
            $mail->addReceiver($stato['destinatari']);
            $mail->save();
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
            $data_scadenza = post('data_scadenza') ?: null;

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
            $id_promemoria = post('idcontratto_riga');
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
            $intervento->idsede_destinazione = $idsede_destinazione;
            $intervento->data_scadenza = $data_scadenza;

            $intervento->save();

            // Sincronizzazione con il promemoria indicato
            if (!empty($id_promemoria)) {
                $promemoria = Promemoria::find($id_promemoria);
                $promemoria->pianifica($intervento);
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
        if (!empty(post('orario_inizio')) && !empty(post('orario_fine'))) {
            foreach ($idtecnici as $idtecnico) {
                add_tecnico($id_record, $idtecnico, post('orario_inizio'), post('orario_fine'), $idcontratto);
            }
        }

        if (post('ref') == 'dashboard') {
            flash()->clearMessage('info');
            flash()->clearMessage('warning');
        }

        aggiorna_sedi_movimenti('interventi', $id_record);
        break;

    // Eliminazione intervento
    case 'delete':
        try {
            $intervento->delete();

            // Eliminazione associazioni tra interventi e contratti
            $dbo->query('UPDATE co_promemoria SET idintervento = NULL WHERE idintervento='.prepare($id_record));

            // Elimino il collegamento al componente
            $dbo->query('DELETE FROM my_impianto_componenti WHERE idintervento='.prepare($id_record));

            // Eliminazione associazione tecnici collegati all'intervento
            $dbo->query('DELETE FROM in_interventi_tecnici WHERE idintervento='.prepare($id_record));

            // Eliminazione associazione interventi e my_impianti
            $dbo->query('DELETE FROM my_impianti_interventi WHERE idintervento='.prepare($id_record));

            // Elimino anche eventuali file caricati
            Uploads::deleteLinked([
                'id_module' => $id_module,
                'id_record' => $id_record,
            ]);

            flash()->info(tr('Intervento eliminato!'));
        } catch (InvalidArgumentException $e) {
            flash()->error(tr('Sono stati utilizzati alcuni serial number nel documento: impossibile procedere!'));
        }

        break;

    case 'delete_riga':
        $id_riga = post('idriga');
        $type = post('type');
$riga = $intervento->getRiga($type, $id_riga);

        if (!empty($riga)) {
            try {
                $riga->delete();

                flash()->info(tr('Riga rimossa!'));
            } catch (InvalidArgumentException $e) {
                flash()->error(tr('Alcuni serial number sono già stati utilizzati!'));
            }
        }

        aggiorna_sedi_movimenti('interventi', $id_record);

        break;

    case 'manage_articolo':
        if (post('idriga') != null) {
            $articolo = Articolo::find(post('idriga'));
        } else {
            $originale = ArticoloOriginale::find(post('idarticolo'));
            $articolo = Articolo::build($intervento, $originale);
        }

        $qta = post('qta');

        $articolo->descrizione = post('descrizione');
        $articolo->um = post('um') ?: null;

        $articolo->costo_unitario = post('costo_unitario') ?: 0;
        $articolo->setPrezzoUnitario(post('prezzo_unitario'), post('idiva'));
        $articolo->setSconto(post('sconto'), post('tipo_sconto'));

        try {
            $articolo->qta = $qta;
        } catch (UnexpectedValueException $e) {
            flash()->error(tr('Alcuni serial number sono già stati utilizzati!'));
        }

        $articolo->save();

        if (post('idriga') != null) {
            flash()->info(tr('Articolo modificato!'));
        } else {
            flash()->info(tr('Articolo aggiunto!'));
        }

        // Collegamento all'impianto
        link_componente_to_articolo($id_record, post('idimpianto'), $articolo->idarticolo, $qta);

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

    case 'manage_riga':
        if (post('idriga') != null) {
            $riga = Riga::find(post('idriga'));
        } else {
            $riga = Riga::build($intervento);
        }

        $qta = post('qta');

        $riga->descrizione = post('descrizione');
        $riga->um = post('um') ?: null;

        $riga->costo_unitario = post('costo_unitario') ?: 0;
        $riga->setPrezzoUnitario(post('prezzo_unitario'), post('idiva'));
        $riga->setSconto(post('sconto'), post('tipo_sconto'));

        $riga->qta = $qta;

        $riga->save();

        if (post('idriga') != null) {
            flash()->info(tr('Riga modificata!'));
        } else {
            flash()->info(tr('Riga aggiunta!'));
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
                } elseif ($dbo->query('UPDATE in_interventi SET firma_file='.prepare($firma_file).', firma_data=NOW(), firma_nome = '.prepare($firma_nome).', idstatointervento = (SELECT idstatointervento FROM in_statiintervento WHERE codice = \'OK\') WHERE id='.prepare($id_record))) {
                    flash()->info(tr('Firma salvata correttamente!'));
                    flash()->info(tr('Attività completata!'));

                    $stato = $dbo->selectOne('in_statiintervento', '*', ['codice' => 'OK']);
                    // Notifica chiusura intervento
                    if (!empty($stato['notifica']) && !empty($stato['destinatari'])) {
                        $template = Template::find($stato['id_email']);

                        $mail = Mail::build(auth()->getUser(), $template, $id_record);
                        $mail->addReceiver($stato['destinatari']);
                        $mail->save();
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
            $template = Template::get('Notifica rimozione intervento');

            $mail = Mail::build(auth()->getUser(), $template, $id_record);
            $mail->addReceiver($tecnico['email']);
            $mail->save();
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
