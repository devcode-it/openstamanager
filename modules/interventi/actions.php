<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

include_once __DIR__.'/../../core.php';

use Carbon\CarbonPeriod;
use Modules\Anagrafiche\Anagrafica;
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Emails\Mail;
use Modules\Emails\Template;
use Modules\Impianti\Impianto;
use Modules\Interventi\Components\Articolo;
use Modules\Interventi\Components\Riga;
use Modules\Interventi\Components\Sconto;
use Modules\Interventi\Components\Sessione;
use Modules\Interventi\Intervento;
use Modules\Interventi\Stato;
use Modules\TipiIntervento\Tipo as TipoSessione;
use Plugins\ComponentiImpianti\Componente;
use Plugins\ListinoClienti\DettaglioPrezzo;
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
        $intervento->codice = post('codice');
        $intervento->data_richiesta = post('data_richiesta');
        $intervento->data_scadenza = post('data_scadenza') ?: null;
        $intervento->richiesta = post('richiesta');
        $intervento->descrizione = post('descrizione');
        $intervento->informazioniaggiuntive = post('informazioniaggiuntive');

        $intervento->idanagrafica = post('idanagrafica');
        $intervento->idclientefinale = post('idclientefinale');
        $intervento->idreferente = post('idreferente');
        $intervento->idagente = post('idagente');
        $intervento->idtipointervento = post('idtipointervento');

        $intervento->idstatointervento = post('idstatointervento');
        $intervento->idsede_partenza = post('idsede_partenza');
        $intervento->idsede_destinazione = post('idsede_destinazione');
        $intervento->id_preventivo = post('idpreventivo');
        $intervento->id_contratto = $idcontratto;
        $intervento->id_ordine = post('idordine');

        $intervento->id_documento_fe = post('id_documento_fe');
        $intervento->num_item = post('num_item');
        $intervento->codice_cup = post('codice_cup');
        $intervento->codice_cig = post('codice_cig');
        $intervento->save();

        $tecnici_assegnati = (array) post('tecnici_assegnati');

        
        $tecnici_presenti_array = $dbo->select('in_interventi_tecnici_assegnati', 'id_tecnico', ['id_intervento' => $intervento->id]);

        foreach($tecnici_presenti_array as $tecnico_presente) {
            $tecnici_presenti[] = $tecnico_presente['id_tecnico'];

            // Notifica rimozione tecnico assegnato
            if (setting('Notifica al tecnico la rimozione dell\'assegnazione dall\'attività')) {
                if (!in_array($tecnico_presente['id_tecnico'], $tecnici_assegnati)){
                    $tecnico = Anagrafica::find($tecnico_presente['id_tecnico']);
                    if (!empty($tecnico['email'])) {
                        $template = Template::pool('Notifica rimozione intervento');

                        if (!empty($template)) {
                            $mail = Mail::build(auth()->getUser(), $template, $intervento->id);
                            $mail->addReceiver($tecnico['email']);
                            $mail->save();
                        }
                    }
                }
            }
        }

        foreach ($tecnici_assegnati as $tecnico_assegnato){

            // Notifica aggiunta tecnico assegnato
            if (setting('Notifica al tecnico l\'assegnazione all\'attività')) {
                if (!in_array($tecnico_assegnato, $tecnici_presenti)){
                    $tecnico = Anagrafica::find($tecnico_assegnato);

                    if (!empty($tecnico['email'])) {
                        $template = Template::pool('Notifica intervento');

                        if (!empty($template)) {
                            $mail = Mail::build(auth()->getUser(), $template, $intervento->id);
                            $mail->addReceiver($tecnico['email']);
                            $mail->save();
                        }
                    }
                }
            }

        }

        // Assegnazione dei tecnici all'intervento
        $dbo->sync('in_interventi_tecnici_assegnati', [
            'id_intervento' => $id_record,
        ], [
            'id_tecnico' => $tecnici_assegnati,
        ]);

        // Notifica cambio stato intervento
        $stato = $dbo->selectOne('in_statiintervento', '*', ['idstatointervento' => post('idstatointervento')]);
        if (!empty($stato['notifica']) && $stato['idstatointervento'] != $record['idstatointervento']) {
            $template = Template::find($stato['id_email']);

            if (!empty($stato['destinatari'])) {
                $mail = Mail::build(auth()->getUser(), $template, $id_record);
                $mail->addReceiver($stato['destinatari']);
                $mail->save();
            }

            if (!empty($stato['notifica_cliente'])) {
                if (!empty($intervento->anagrafica->email)) {
                    $mail = Mail::build(auth()->getUser(), $template, $id_record);
                    $mail->addReceiver($intervento->anagrafica->email);
                    $mail->save();
                }
            }

            $tecnici_intervento = [];
            if (!empty($stato['notifica_tecnico_sessione'])) {
                $tecnici_intervento = $dbo->select('in_interventi_tecnici', 'idtecnico', ['idintervento' => $id_record]);
            }

            $tecnici_assegnati = [];
            if (!empty($stato['notifica_tecnico_assegnato'])) {
                $tecnici_assegnati = $dbo->select('in_interventi_tecnici_assegnati', 'id_tecnico AS idtecnico', ['id_intervento' => $id_record]);
            }
                
            $tecnici = array_unique(array_merge($tecnici_intervento, $tecnici_assegnati), SORT_REGULAR);

            foreach ($tecnici as $tecnico) {
                $mail_tecnico = $dbo->selectOne('an_anagrafiche', '*', ['idanagrafica' => $tecnico]);
                if (!empty($mail_tecnico['email'])) {
                    $mail = Mail::build(auth()->getUser(), $template, $id_record);
                    $mail->addReceiver($mail_tecnico['email']);
                    $mail->save();
                }
            }
        }

        flash()->info(tr('Attività modificata correttamente!'));

        break;

    case 'add':
        if (post('id_intervento') == null) {
            $idanagrafica = post('idanagrafica');
            $idtipointervento = post('idtipointervento');
            $idstatointervento = post('idstatointervento');
            $data_richiesta = post('data_richiesta');
            $data_scadenza = post('data_scadenza') ?: null;
            $id_segment = post('id_segment');

            $anagrafica = Anagrafica::find($idanagrafica);
            $tipo = TipoSessione::find($idtipointervento);
            $stato = Stato::find($idstatointervento);

            $intervento = Intervento::build($anagrafica, $tipo, $stato, $data_richiesta, $id_segment);
            $id_record = $intervento->id;

            flash()->info(tr('Aggiunto nuovo intervento!'));

            // Informazioni di base
            $idpreventivo = post('idpreventivo');
            $idcontratto = post('idcontratto');
            $id_promemoria = post('idcontratto_riga');
            $idtipointervento = post('idtipointervento');
            $idsede_partenza = post('idsede_partenza');
            $idsede_destinazione = post('idsede_destinazione') ?: 0;

            if (post('idclientefinale')) {
                $intervento->idclientefinale = post('idclientefinale');
            }

            $intervento->id_preventivo = post('idpreventivo');
            $intervento->id_contratto = post('idcontratto');
            $intervento->id_ordine = post('idordine');
            $intervento->idreferente = post('idreferente');
            $intervento->richiesta = post('richiesta');
            $intervento->idsede_destinazione = $idsede_destinazione;
            $intervento->data_scadenza = $data_scadenza;

            $intervento->save();

            // Sincronizzazione con il promemoria indicato
            if (!empty($id_promemoria)) {
                $promemoria = Promemoria::find($id_promemoria);
                $promemoria->pianifica($intervento, false);
            }

            // Collegamenti intervento/impianti
            $impianti = (array) post('idimpianti');
            if (!empty($impianti)) {
                $impianti = array_unique($impianti);
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

            $intervento = Intervento::find($id_record);
            $intervento->richiesta = post('richiesta');
            $intervento->save();

            $idcontratto = $dbo->fetchOne('SELECT idcontratto FROM co_promemoria WHERE idintervento = :id', [
                ':id' => $id_record,
            ])['idcontratto'];
        }

        // Collegamenti tecnici/interventi
        if (!empty(post('orario_inizio')) && !empty(post('orario_fine'))) {
            $idtecnici = post('idtecnico');
            foreach ($idtecnici as $idtecnico) {
                add_tecnico($id_record, $idtecnico, post('orario_inizio'), post('orario_fine'), $idcontratto);
            }
        }

        // Assegnazione dei tecnici all'intervento
        $tecnici_assegnati = (array) post('tecnici_assegnati');
        $dbo->sync('in_interventi_tecnici_assegnati', [
            'id_intervento' => $id_record,
        ], [
            'id_tecnico' => $tecnici_assegnati,
        ]);

        foreach ($tecnici_assegnati as $tecnico_assegnato){
            $tecnico = Anagrafica::find($tecnico_assegnato);

            // Notifica al tecnico
            if (setting('Notifica al tecnico l\'assegnazione all\'attività')) {
                if (!empty($tecnico['email'])) {
                    $template = Template::pool('Notifica intervento');

                    if (!empty($template)) {
                        $mail = Mail::build(auth()->getUser(), $template, $intervento->id);
                        $mail->addReceiver($tecnico['email']);
                        $mail->save();
                    }
                }
            }
        }

        if (!empty(post('ricorsiva'))) {
            $periodicita = post('periodicita');
            $data = post('data_inizio_ricorrenza');
            $interval = post('tipo_periodicita') != 'manual' ? post('tipo_periodicita') : 'days';
            $stato = Stato::find(post('idstatoricorrenze'));

            // Estraggo le date delle ricorrenze
            if (post('metodo_ricorrenza') == 'data') {
                $data_fine = post('data_fine_ricorrenza');
                while (strtotime($data) <= strtotime($data_fine)) {
                    $data = date('Y-m-d', strtotime('+'.$periodicita.' '.$interval.'', strtotime($data)));
                    $w = date('w', strtotime($data));
                    //Escludo sabato e domenica
                    if ($w == '6') {
                        $data = date('Y-m-d', strtotime('+2 day', strtotime($data)));
                    } elseif ($w == '0') {
                        $data = date('Y-m-d', strtotime('+1 day', strtotime($data)));
                    }
                    if ($data <= $data_fine) {
                        $date_ricorrenze[] = $data;
                    }
                }
            } else {
                $ricorrenze = post('numero_ricorrenze');
                for ($i = 0; $i < $ricorrenze; ++$i) {
                    $data = date('Y-m-d', strtotime('+'.$periodicita.' '.$interval.'', strtotime($data)));
                    $w = date('w', strtotime($data));
                    //Escludo sabato e domenica
                    if ($w == '6') {
                        $data = date('Y-m-d', strtotime('+2 day', strtotime($data)));
                    } elseif ($w == '0') {
                        $data = date('Y-m-d', strtotime('+1 day', strtotime($data)));
                    }

                    $date_ricorrenze[] = $data;
                }
            }

            foreach ($date_ricorrenze as $data_ricorrenza) {
                $intervento = Intervento::find($id_record);
                $new = $intervento->replicate();
                // Calcolo il nuovo codice
                $new->codice = Intervento::getNextCodice($data_ricorrenza, $new->id_segment);
                $new->data_richiesta = $data_ricorrenza;
                $new->idstatointervento = $stato->idstatointervento;
                $new->save();
                $idintervento = $new->id;

                // Inserimento sessioni
                if (!empty(post('riporta_sessioni'))) {
                    $numero_sessione = 0;
                    $sessioni = $intervento->sessioni;
                    foreach ($sessioni as $sessione) {
                        // Se è la prima sessione che copio importo la data con quella della richiesta
                        if ($numero_sessione == 0) {
                            $orario_inizio = date('Y-m-d', strtotime($data_ricorrenza)).' '.date('H:i:s', strtotime($sessione->orario_inizio));
                        } else {
                            $diff = strtotime($sessione->orario_inizio) - strtotime($inizio_old);
                            $orario_inizio = date('Y-m-d H:i:s', (strtotime($new_sessione->orario_inizio) + $diff));
                        }

                        $diff_fine = strtotime($sessione->orario_fine) - strtotime($sessione->orario_inizio);
                        $orario_fine = date('Y-m-d H:i:s', (strtotime($orario_inizio) + $diff_fine));

                        $new_sessione = $sessione->replicate();
                        $new_sessione->idintervento = $new->id;
                        $new_sessione->orario_inizio = $orario_inizio;
                        $new_sessione->orario_fine = $orario_fine;
                        $new_sessione->save();

                        ++$numero_sessione;
                        $inizio_old = $sessione->orario_inizio;
                    }
                }

                // Assegnazione dei tecnici all'intervento
                $tecnici_assegnati = (array) post('tecnici_assegnati');
                $dbo->sync('in_interventi_tecnici_assegnati', [
                    'id_intervento' => $new->id,
                ], [
                    'id_tecnico' => $tecnici_assegnati,
                ]);

                ++$n_ricorrenze;
            }

            flash()->info(tr('Aggiunte _NUM_ nuove ricorrenze!', [
              '_NUM_' => $n_ricorrenze,
            ]));
        }

        if (post('ref') == 'dashboard') {
            flash()->clearMessage('info');
            flash()->clearMessage('warning');
        }

        break;

    // Eliminazione intervento
    case 'delete':
        try {
            // Eliminazione associazioni tra interventi e contratti
            $dbo->query('UPDATE co_promemoria SET idintervento = NULL WHERE idintervento='.prepare($id_record));

            $intervento->delete();

            // Elimino il collegamento al componente
            $dbo->query('DELETE FROM my_componenti WHERE id_intervento='.prepare($id_record));

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
        $id_righe = (array)post('righe');
        
        foreach ($id_righe as $id_riga) {
            $riga = Articolo::find($id_riga) ?: Riga::find($id_riga);
            $riga = $riga ?: Sconto::find($id_riga);
            try {
                $riga->delete();
            } catch (InvalidArgumentException $e) {
                flash()->error(tr('Alcuni serial number sono già stati utilizzati!'));
            }

            $riga = null;
        }

        flash()->info(tr('Righe eliminate!'));

        break;

    // Duplicazione riga
    case 'copy_riga':
        $id_righe = (array)post('righe');
        
        foreach ($id_righe as $id_riga) {
            $riga = Articolo::find($id_riga) ?: Riga::find($id_riga);
            $riga = $riga ?: Sconto::find($id_riga);

            $new_riga = $riga->replicate();
            $new_riga->setDocument($intervento);
            $new_riga->qta_evasa = 0;
            $new_riga->save();

            if ($new_riga->isArticolo()) {
                $new_riga->movimenta($new_riga->qta);
            }

            $riga = null;
        }

        flash()->info(tr('Righe duplicate!'));

        break;

    case 'manage_barcode':
        foreach (post('qta') as $id_articolo => $qta) {
            if ($id_articolo == '-id-') {
                continue;
            }

            // Dati di input
            $sconto = post('sconto')[$id_articolo];
            $tipo_sconto = post('tipo_sconto')[$id_articolo];
            $prezzo_unitario = post('prezzo_unitario')[$id_articolo];
            $id_dettaglio_fornitore = post('id_dettaglio_fornitore')[$id_articolo];
            $id_iva = $originale->idiva_vendita ? $originale->idiva_vendita : setting('Iva predefinita');

            // Creazione articolo
            $originale = ArticoloOriginale::find($id_articolo);
            $articolo = Articolo::build($intervento, $originale);
            $articolo->id_dettaglio_fornitore = $id_dettaglio_fornitore ?: null;

            $articolo->setPrezzoUnitario($prezzo_unitario, $id_iva);
            $articolo->costo_unitario = $originale->prezzo_acquisto;
            $articolo->setSconto($sconto, $tipo_sconto);
            $articolo->qta = $qta;

            $articolo->save();
        }

        flash()->info(tr('Articoli aggiunti!'));

        break;

    case 'manage_articolo':
        if (post('idriga') != null) {
            $articolo = Articolo::find(post('idriga'));
        } else {
            $originale = ArticoloOriginale::find(post('idarticolo'));
            $articolo = Articolo::build($intervento, $originale);
            $articolo->id_dettaglio_fornitore = post('id_dettaglio_fornitore') ?: null;
        }

        $qta = post('qta');

        $articolo->idsede_partenza = post('idsede_partenza');
        $articolo->descrizione = post('descrizione');
        $articolo->note = post('note');
        $articolo->um = post('um') ?: null;
        $articolo->idimpianto = post('id_impianto') ?: null;

        $articolo->costo_unitario = post('costo_unitario') ?: 0;
        $articolo->setPrezzoUnitario(post('prezzo_unitario'), post('idiva'));
        $articolo->setSconto(post('sconto'), post('tipo_sconto'));
        $articolo->setProvvigione(post('provvigione'), post('tipo_provvigione'));

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

        // Collegamento all'Impianto tramite generazione Componente
        $id_impianto = post('id_impianto');
        $impianto = Impianto::find($id_impianto);
        if (!empty($impianto)) {
            // Data di inizio dell'intervento (data_richiesta in caso di assenza di sessioni)
            $data_registrazione = $intervento->inizio ?: $intervento->data_richiesta;

            // Creazione in base alla quantità
            for ($q = 0; $q < $articolo->qta; ++$q) {
                $componente = Componente::build($impianto, $articolo->articolo, $data_registrazione);
            }
        }

        break;

    case 'manage_sconto':
        if (post('idriga') != null) {
            $sconto = Sconto::find(post('idriga'));
        } else {
            $sconto = Sconto::build($intervento);
        }

        $sconto->descrizione = post('descrizione');
        $sconto->setScontoUnitario(post('sconto_unitario'), post('idiva'));
        $sconto->note = post('note');
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
        $riga->note = post('note');
        $riga->um = post('um') ?: null;

        $riga->costo_unitario = post('costo_unitario') ?: 0;
        $riga->setPrezzoUnitario(post('prezzo_unitario'), post('idiva'));
        $riga->setSconto(post('sconto'), post('tipo_sconto'));
        $riga->setProvvigione(post('provvigione'), post('tipo_provvigione'));

        $riga->qta = $qta;

        $riga->save();

        if (post('idriga') != null) {
            flash()->info(tr('Riga modificata!'));
        } else {
            flash()->info(tr('Riga aggiunta!'));
        }

        break;

    case 'add_serial':
        $articolo = Articolo::find(post('idriga'));

        $serials = (array) post('serial');
        $articolo->serials = $serials;

        break;

    // Aggiunta di un documento in ordine
    case 'add_intervento':
    case 'add_documento':
        $class = post('class');
        $id_documento = post('id_documento');

        // Individuazione del documento originale
        if (!is_subclass_of($class, \Common\Document::class)) {
            return;
        }
        $documento = $class::find($id_documento);

        // Individuazione sede
        $id_sede = ($documento->direzione == 'entrata') ? $documento->idsede_destinazione : $documento->idsede_partenza;
        $id_sede = $id_sede ?: $documento->idsede;
        $id_sede = $id_sede ?: 0;

        // Creazione dell' ordine al volo
        if (post('create_document') == 'on') {
            $stato = Stato::find(post('id_stato_intervento'));
            $tipo = TipoSessione::find(post('id_tipo_intervento'));

            $anagrafica = post('idanagrafica') ? Anagrafica::find(post('idanagrafica')) : $documento->anagrafica;

            $intervento = Intervento::build($anagrafica, $tipo, $stato, post('data'), post('id_segment'));
            $intervento->idsede_destinazione = $id_sede;

            $intervento->id_documento_fe = $documento->id_documento_fe;
            $intervento->codice_cup = $documento->codice_cup;
            $intervento->codice_cig = $documento->codice_cig;
            $intervento->num_item = $documento->num_item;
            $intervento->idreferente = $documento->idreferente;
            $intervento->idagente = $documento->idagente;

            $intervento->save();

            $id_record = $intervento->id;
        }

        // Evado le righe solo se il documento originale non è un Ordine fornitore
        $evadi_qta_parent = true;
        if (post('op') == 'add_intervento') {
            $evadi_qta_parent = false;
        }

        $righe = $documento->getRighe();
        foreach ($righe as $riga) {
            if (post('evadere')[$riga->id] == 'on' and !empty(post('qta_da_evadere')[$riga->id])) {
                $qta = post('qta_da_evadere')[$riga->id];

                $copia = $riga->copiaIn($intervento, $qta, $evadi_qta_parent);

                if ($copia->isArticolo()) {
                    // Aggiornamento seriali
                    $serials = is_array(post('serial')[$riga->id]) ? post('serial')[$riga->id] : [];
                    $copia->serials = $serials;

                    // Aggiornamento prezzi se il documento originale è un Ordine fornitore
                    if (post('op') == 'add_intervento') {
                        $articolo = $copia->articolo;

                        $cliente = DettaglioPrezzo::dettagli($riga->idarticolo, $anagrafica->id, 'entrata', $qta)->first();
                        if (empty($cliente)) {
                            $cliente = DettaglioPrezzo::dettaglioPredefinito($riga->idarticolo, $anagrafica->id, 'entrata')->first();
                        }

                        $prezzo_unitario = $cliente->prezzo_unitario - ($cliente->prezzo_unitario * $cliente->percentuale / 100);

                        $copia->setPrezzoUnitario($cliente ? $prezzo_unitario : $cliente->prezzo_vendita, $copia->aliquota->id);
                        $copia->setSconto($cliente->sconto_percentuale ?: 0, 'PRC');
                        $copia->costo_unitario = $riga->prezzo_unitario ?: 0;
                    }
                }

                $copia->save();
            }
        }

        // Modifica finale dello stato
    /*
        if (post('create_document') == 'on') {
            $intervento->idstatointervento = post('id_stato_intervento');
            $intervento->save();
        }*/

        // Messaggio informativo
        $message = tr('_DOC_ aggiunto!', [
            '_DOC_' => $documento->getReference(),
        ]);
        flash()->info($message);

        break;

    case 'firma':
        if (directory(base_dir().'/files/interventi')) {
            if (post('firma_base64') != '') {
                // Salvataggio firma
                $firma_file = 'firma_'.time().'.jpg';
                $firma_nome = post('firma_nome');

                $data = explode(',', post('firma_base64'));

                $img = Intervention\Image\ImageManagerStatic::make(base64_decode($data[1]));
                $img->resize(680, 202, function ($constraint) {
                    $constraint->aspectRatio();
                });

                if (!$img->save(base_dir().'/files/interventi/'.$firma_file)) {
                    flash()->error(tr('Impossibile creare il file!'));
                } elseif ($dbo->query('UPDATE in_interventi SET firma_file='.prepare($firma_file).', firma_data=NOW(), firma_nome = '.prepare($firma_nome).' WHERE id='.prepare($id_record))) {
                    flash()->info(tr('Firma salvata correttamente!'));

                    $id_stato = setting("Stato dell'attività dopo la firma");
                    $stato = $dbo->selectOne('in_statiintervento', '*', ['idstatointervento' => $id_stato]);
                    $intervento = Intervento::find($id_record);
                    if (!empty($stato)) {
                        $intervento = Intervento::find($id_record);
                        $intervento->idstatointervento = $stato['idstatointervento'];
                        $intervento->save();
                    }

                    // Notifica chiusura intervento
                    if (!empty($stato['notifica'])) {
                        $template = Template::find($stato['id_email']);

                        if (!empty($stato['destinatari'])) {
                            $mail = Mail::build(auth()->getUser(), $template, $id_record);
                            $mail->addReceiver($stato['destinatari']);
                            $mail->save();
                        }

                        if (!empty($stato['notifica_cliente'])) {
                            if (!empty($intervento->anagrafica->email)) {
                                $mail = Mail::build(auth()->getUser(), $template, $id_record);
                                $mail->addReceiver($intervento->anagrafica->email);
                                $mail->save();
                            }
                        }

                        if (!empty($stato['notifica_tecnici'])) {
                            $tecnici_intervento = $dbo->select('in_interventi_tecnici', 'idtecnico', ['idintervento' => $id_record]);
                            $tecnici_assegnati = $dbo->select('in_interventi_tecnici_assegnati', 'id_tecnico AS idtecnico', ['id_intervento' => $id_record]);
                            $tecnici = array_unique(array_merge($tecnici_intervento, $tecnici_assegnati), SORT_REGULAR);

                            foreach ($tecnici as $tecnico) {
                                $mail_tecnico = $dbo->selectOne('an_anagrafiche', '*', ['idanagrafica' => $tecnico]);
                                if (!empty($mail_tecnico['email'])) {
                                    $mail = Mail::build(auth()->getUser(), $template, $id_record);
                                    $mail->addReceiver($mail_tecnico['email']);
                                    $mail->save();
                                }
                            }
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

        $inizio = post('orario_inizio') ?: date('Y-m-d H:\0\0');
        $fine = post('orario_fine') ?: null;

        add_tecnico($id_record, $id_tecnico, $inizio, $fine, $idcontratto);
        break;

    // OPERAZIONI PER AGGIUNTA SESSIONi DI LAVORO MULTIPLE
    case 'add_sessioni':
        $idcontratto = $intervento['id_contratto'];
        $orario_inizio = post('orario_inizio');
        $orario_fine = post('orario_fine');
        $data_inizio = post('data_inizio');
        $data_fine = post('data_fine');
        $giorni = (array)post('giorni');
        $id_tecnici = (array)post('id_tecnici');

        $period = CarbonPeriod::create($data_inizio, $data_fine);

        // Iterate over the period
        foreach ($period as $date) {
            $data = $date->format('Y-m-d');
            $giorno = $date->locale('IT')->dayName;
            if (in_array($giorno, $giorni)) {
                $inizio = $data.' '.$orario_inizio;
                $fine = $data.' '.$orario_fine;
                
                foreach ($id_tecnici as $id_tecnico) {
                    add_tecnico($id_record, $id_tecnico, $inizio, $fine, $idcontratto);
                }
            }
        }

        break;

    // RIMOZIONE SESSIONE DI LAVORO
    case 'delete_sessione':
        $id_sessione = post('id_sessione');

        $tecnico = $dbo->fetchOne('SELECT an_anagrafiche.email FROM an_anagrafiche INNER JOIN in_interventi_tecnici ON in_interventi_tecnici.idtecnico = an_anagrafiche.idanagrafica WHERE in_interventi_tecnici.id = '.prepare($id_sessione));

        $dbo->query('DELETE FROM in_interventi_tecnici WHERE id='.prepare($id_sessione));

        // Notifica rimozione dell' intervento al tecnico
        if (setting('Notifica al tecnico la rimozione della sessione dall\'attività')) {
            if (!empty($tecnico['email'])) {
                $template = Template::pool('Notifica rimozione intervento');

                if (!empty($template)) {
                    $mail = Mail::build(auth()->getUser(), $template, $id_record);
                    $mail->addReceiver($tecnico['email']);
                    $mail->save();
                }
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

    // Duplica intervento
    case 'copy':
        $id_stato = post('id_stato');
        $data_richiesta = post('data_richiesta');
        $copia_sessioni = post('copia_sessioni');
        $copia_righe = post('copia_righe');
        $copia_impianti = post('copia_impianti');
        $copia_allegati = post('copia_allegati');

        $new = $intervento->replicate();
        $new->idstatointervento = $id_stato;

        // Calcolo del nuovo codice sulla base della data di richiesta
        $new->codice = Intervento::getNextCodice($data_richiesta, $new->id_segment);
        $new->data_richiesta = $data_richiesta;
        $new->data_scadenza = post('data_scadenza');
        $new->firma_file = '';
        $new->firma_data = null;
        $new->firma_nome = '';

        $new->save();

        $id_record = $new->id;

        // Copio le righe
        if (!empty($copia_righe)) {
            $righe = $intervento->getRighe();
            foreach ($righe as $riga) {
                $new_riga = $riga->replicate();
                $new_riga->setDocument($new);

                $new_riga->qta_evasa = 0;
                $new_riga->save();

                if ($new_riga->isArticolo()) {
                    $new_riga->movimenta($new_riga->qta);
                }
            }
        }

        // Copia delle sessioni
        $numero_sessione = 0;
        if (!empty($copia_sessioni)) {
            $sessioni = $intervento->sessioni;
            foreach ($sessioni as $sessione) {
                // Se è la prima sessione che copio importo la data con quella della richiesta
                if ($numero_sessione == 0) {
                    $orario_inizio = date('Y-m-d', strtotime($data_richiesta)).' '.date('H:i:s', strtotime($sessione->orario_inizio));
                } else {
                    $diff = strtotime($sessione->orario_inizio) - strtotime($inizio_old);
                    $orario_inizio = date('Y-m-d H:i:s', (strtotime($new_sessione->orario_inizio) + $diff));
                }

                $diff_fine = strtotime($sessione->orario_fine) - strtotime($sessione->orario_inizio);
                $orario_fine = date('Y-m-d H:i:s', (strtotime($orario_inizio) + $diff_fine));

                $new_sessione = $sessione->replicate();
                $new_sessione->idintervento = $new->id;

                $new_sessione->orario_inizio = $orario_inizio;
                $new_sessione->orario_fine = $orario_fine;
                $new_sessione->save();

                ++$numero_sessione;
                $inizio_old = $sessione->orario_inizio;
            }
        }

        // Copia degli impianti
        if (!empty($copia_impianti)) {
            $impianti = $dbo->select('my_impianti_interventi', '*', ['idintervento' => $intervento->id]);
            foreach ($impianti as $impianto) {
                $dbo->insert('my_impianti_interventi', [
                    'idintervento' => $id_record,
                    'idimpianto' => $impianto['idimpianto']
                ]);
            }

            $componenti = $dbo->select('my_componenti_interventi', '*', ['id_intervento' => $intervento->id]);
            foreach ($componenti as $componente) {
                $dbo->insert('my_componenti_interventi', [
                    'id_intervento' => $id_record,
                    'id_componente' => $componente['id_componente']
                ]);
            }
        }

        //copia allegati
        if (!empty($copia_allegati)) {
            $allegati = $intervento->uploads();
            foreach ($allegati as $allegato) {
                $allegato->copia([
                    'id_module' => $new->getModule()->id,
                    'id_record' => $new->id,
                ]);
            }
        }

        flash()->info(tr('Attività duplicata correttamente!'));

        break;

    case 'update_position':
        $order = explode(',', post('order', true));

        foreach ($order as $i => $id_riga) {
            $dbo->query('UPDATE `in_righe_interventi` SET `order` = '.prepare($i + 1).' WHERE id='.prepare($id_riga));
        }

        break;

    case 'add_articolo':
        $id_articolo = post('id_articolo');
        $barcode = post('barcode');
        $dir = 'entrata';

        if (!empty($barcode)) {
            $id_articolo = $dbo->selectOne('mg_articoli', 'id',  ['deleted_at' => null, 'barcode' => $barcode])['id'];
        }

        if (!empty($id_articolo)) {
            $permetti_movimenti_sotto_zero = setting('Permetti selezione articoli con quantità minore o uguale a zero in Documenti di Vendita');
            $qta_articolo = $dbo->selectOne('mg_articoli', 'qta', ['id' => $id_articolo])['qta'];

            $originale = ArticoloOriginale::find($id_articolo);

            if ($qta_articolo <= 0 && !$permetti_movimenti_sotto_zero && !$originale->servizio && $dir == 'entrata') {
                $response['error'] = tr('Quantità a magazzino non sufficiente');
                echo json_encode($response);
            } else {
                $articolo = Articolo::build($intervento, $originale);
                $qta = 1;

                $articolo->descrizione = $originale->descrizione;
                $articolo->um = $originale->um;
                $articolo->qta = 1;
                $articolo->costo_unitario = $originale->prezzo_acquisto;

                $id_iva = $originale->idiva_vendita ?: setting('Iva predefinita');
                $id_anagrafica = $intervento->idanagrafica;
                $prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');
        
                // CALCOLO PREZZO UNITARIO
                $prezzo_unitario = 0;
                $sconto = 0;
                // Prezzi netti clienti / listino fornitore
                $prezzi = $dbo->fetchArray('SELECT minimo, massimo, sconto_percentuale, '.($prezzi_ivati ? 'prezzo_unitario_ivato' : 'prezzo_unitario').' AS prezzo_unitario
                FROM mg_prezzi_articoli
                WHERE id_articolo = '.prepare($id_articolo).' AND dir = '.prepare($dir).' AND id_anagrafica = '.prepare($id_anagrafica));

                if ($prezzi) {
                    foreach ($prezzi as $prezzo) {
                        if ($qta >= $prezzo['minimo'] && $qta <= $prezzo['massimo']) {
                            $prezzo_unitario = $prezzo['prezzo_unitario'];
                            $sconto = $prezzo['sconto_percentuale'];
                            continue;
                        }

                        if ($prezzo['minimo'] == null && $prezzo['massimo'] == null && $prezzo['prezzo_unitario'] != null) {
                            $prezzo_unitario = $prezzo['prezzo_unitario'];
                            $sconto = $prezzo['sconto_percentuale'];
                            continue;
                        }
                    }
                } 
                if (empty($prezzo_unitario)) {
                    // Prezzi listini clienti
                    $listino = $dbo->fetchOne('SELECT sconto_percentuale AS sconto_percentuale_listino, '.($prezzi_ivati ? 'prezzo_unitario_ivato' : 'prezzo_unitario').' AS prezzo_unitario_listino
                    FROM mg_listini
                    LEFT JOIN mg_listini_articoli ON mg_listini.id=mg_listini_articoli.id_listino
                    LEFT JOIN an_anagrafiche ON mg_listini.id=an_anagrafiche.id_listino
                    WHERE mg_listini.data_attivazione<=NOW() AND mg_listini_articoli.data_scadenza>=NOW() AND mg_listini.attivo=1 AND id_articolo = '.prepare($id_articolo).' AND dir = '.prepare($dir).' AND idanagrafica = '.prepare($id_anagrafica));

                    if ($listino) {
                        $prezzo_unitario = $listino['prezzo_unitario_listino'];
                        $sconto = $listino['sconto_percentuale_listino'];
                    }
                }
                $prezzo_unitario = $prezzo_unitario ?: ($prezzi_ivati ? $originale->prezzo_vendita_ivato : $originale->prezzo_vendita);
                $provvigione = $dbo->selectOne('an_anagrafiche', 'provvigione_default', ['idanagrafica' => $intervento->idagente])['provvigione_default'];

                $articolo->setPrezzoUnitario($prezzo_unitario, $id_iva);
                $articolo->setSconto($sconto, 'PRC');
                $articolo->setProvvigione($provvigione ?: 0, 'PRC');
                $articolo->save();

                
                flash()->info(tr('Nuovo articolo aggiunto!'));
            }
        } else {
            $response['error'] = tr('Nessun articolo corrispondente a magazzino');
            echo json_encode($response);
        }

        break;

    case 'update_inline':
        $id_riga = post('riga_id');
        $riga = $riga ?: Riga::find($id_riga);
        $riga = $riga ?: Articolo::find($id_riga);

        if (!empty($riga)) {
            $riga->qta = post('qta');
            $riga->setSconto(post('sconto'), post('tipo_sconto'));
            $riga->save();

            flash()->info(tr('Quantità aggiornata!'));
        }

        break;
}
