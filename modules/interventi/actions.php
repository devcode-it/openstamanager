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
use Models\Module;
use Models\OperationLog;
use Models\Plugin;
use Modules\Anagrafiche\Anagrafica;
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Checklists\Check;
use Modules\Emails\Mail;
use Modules\Emails\Template;
use Modules\Impianti\Impianto;
use Modules\Interventi\Components\Articolo;
use Modules\Interventi\Components\Riga;
use Modules\Interventi\Components\Sconto;
use Modules\Interventi\Components\Sessione;
use Modules\Interventi\Intervento;
use Modules\Interventi\Stato;
use Modules\Iva\Aliquota;
use Modules\TipiIntervento\Tipo as TipoSessione;
use Plugins\ComponentiImpianti\Componente;
use Plugins\ListinoClienti\DettaglioPrezzo;
use Plugins\PianificazioneInterventi\Promemoria;

$id_modulo_impianti = Module::where('name', 'Impianti')->first()->id;
$plugin_impianti = Plugin::where('name', 'Impianti')->first()->id;

switch (post('op')) {
    case 'update':
        $idcontratto = post('idcontratto') ?: null;
        $id_promemoria = post('idcontratto_riga');
        $tecnici_assegnati_array = post('tecnici_assegnati') ?: [];

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
        $intervento->id_preventivo = post('idpreventivo') ?: null;
        $intervento->id_contratto = $idcontratto;
        $intervento->id_ordine = post('idordine') ?: null;
        $intervento->idpagamento = post('idpagamento');

        $intervento->id_documento_fe = post('id_documento_fe');
        $intervento->num_item = post('num_item');
        $intervento->codice_cup = post('codice_cup');
        $intervento->codice_cig = post('codice_cig');
        $intervento->save();

        $tags = $dbo->select('in_interventi_tags', 'id_tag', [], ['id_intervento' => $intervento->id]);
        $tags_presenti = [];

        foreach ($tags as $tag) {
            $tags_presenti[] = $tag['id_tag'];
        }

        $tags = post('tags') ?: [];
        $tags_presenti = [];

        foreach ($tags as $tag) {
            $tags_presenti[] = $tag;
        }

        // Assegnazione dei tecnici all'intervento
        $dbo->sync('in_interventi_tags', [
            'id_intervento' => $id_record,
        ], [
            'id_tag' => $tags_presenti,
        ]);

        $tecnici_presenti_array = $dbo->select('in_interventi_tecnici_assegnati', 'id_tecnico', [], ['id_intervento' => $intervento->id]);
        $tecnici_presenti = [];

        foreach ($tecnici_presenti_array as $tecnico_presente) {
            $tecnici_presenti[] = $tecnico_presente['id_tecnico'];

            // Notifica rimozione tecnico assegnato
            if (setting('Notifica al tecnico la rimozione dell\'assegnazione dall\'attività')) {
                if (!in_array($tecnico_presente['id_tecnico'], $tecnici_assegnati_array)) {
                    $tecnico = Anagrafica::find($tecnico_presente['id_tecnico']);
                    if (!empty($tecnico['email'])) {
                        $template = Template::where('name', 'Notifica rimozione intervento')->first();

                        if (!empty($template)) {
                            $mail = Mail::build(auth()->getUser(), $template, $intervento->id);
                            $mail->addReceiver($tecnico['email']);
                            $mail->save();
                            flash()->info(tr('Notifica al tecnico aggiunta correttamente.'));
                        }
                    }
                }
            }
        }

        $tecnici_assegnati = [];

        foreach ($tecnici_assegnati_array as $tecnico_assegnato) {
            $tecnici_assegnati[] = $tecnico_assegnato;
            // Notifica aggiunta tecnico assegnato
            if (setting('Notifica al tecnico l\'assegnazione all\'attività')) {
                if (!in_array($tecnico_assegnato, $tecnici_presenti)) {
                    $tecnico = Anagrafica::find($tecnico_assegnato);

                    if (!empty($tecnico['email'])) {
                        $template = Template::where('name', 'Notifica intervento')->first();

                        if (!empty($template)) {
                            $mail = Mail::build(auth()->getUser(), $template, $intervento->id);
                            $mail->addReceiver($tecnico['email']);
                            $mail->save();
                            flash()->info(tr('Notifica al tecnico aggiunta correttamente.'));
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
        $stato = $dbo->selectOne('in_statiintervento', '*', ['id' => post('idstatointervento')]);
        if (!empty($stato['notifica']) && $stato['id'] != $record['idstatointervento']) {
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
                $tecnici_intervento = $dbo->select('in_interventi_tecnici', 'idtecnico', [], ['idintervento' => $id_record]);
            }

            $tecnici_assegnati = [];
            if (!empty($stato['notifica_tecnico_assegnato'])) {
                $tecnici_assegnati = $dbo->select('in_interventi_tecnici_assegnati', 'id_tecnico AS idtecnico', [], ['id_intervento' => $id_record]);
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

        aggiorna_sedi_movimenti('interventi', $id_record);

        flash()->info(tr('Attività modificata correttamente!'));

        break;

    case 'add':
        if (post('id_intervento') == null) {
            $idanagrafica = post('idanagrafica');
            $idtipointervento = post('idtipointervento');
            $idstatointervento = post('id');
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

            $intervento->id_preventivo = $idpreventivo ?: null;
            $intervento->id_contratto = $idcontratto ?: null;
            $intervento->id_ordine = post('idordine') ?: null;
            $intervento->idreferente = post('idreferente') ?: null;
            $intervento->richiesta = post('richiesta');
            $intervento->descrizione = post('descrizione');
            $intervento->idsede_destinazione = $idsede_destinazione;
            $intervento->data_scadenza = $data_scadenza;

            $intervento->save();

            // Sincronizzazione con il promemoria indicato
            if (!empty($id_promemoria)) {
                $promemoria = Promemoria::find($id_promemoria);
                $promemoria->pianifica($intervento, false);
            }

            // Collegamenti intervento/impianti
            $impianti = post('idimpianti');
            foreach ($impianti as $impianto) {
                if (!empty($impianto)) {
                    $dbo->insert('my_impianti_interventi', [
                        'idintervento' => $id_record,
                        'idimpianto' => $impianto,
                    ]);

                    $checks_impianti = $dbo->fetchArray('SELECT * FROM zz_checks WHERE id_module = '.prepare($id_modulo_impianti).' AND id_record = '.prepare($impianto));
                    foreach ($checks_impianti as $check_impianto) {
                        $id_parent_new = null;
                        if ($check_impianto['id_parent']) {
                            $parent = $dbo->selectOne('zz_checks', '*', ['id' => $check_impianto['id_parent']]);
                            $id_parent_new = $dbo->selectOne('zz_checks', '*', ['content' => $parent['content'], 'id_module' => $id_module, 'id_record' => $id_record])['id'];
                        }
                        $check = Check::build($user, $structure, $id_record, $check_impianto['content'], $id_parent_new, $check_impianto['is_titolo'], $check_impianto['order'], $id_modulo_impianti, $impianto);
                        $check->id_module = $id_module;
                        $check->id_plugin = $plugin_impianti;
                        $check->note = $check_impianto['note'];
                        $check->save();

                        // Riporto anche i permessi della check
                        $users = [];
                        $utenti = $dbo->table('zz_check_user')->where('id_check', $check_impianto['id'])->get();
                        foreach ($utenti as $utente) {
                            $users[] = $utente->id_utente;
                        }
                        $check->setAccess($users, null);
                    }
                }
            }

            // Collegamenti intervento/componenti
            $componenti = (array) post('componenti');
            foreach ($componenti as $componente) {
                if ($componente) {
                    $dbo->insert('my_componenti_interventi', [
                        'id_intervento' => $id_record,
                        'id_componente' => $componente,
                    ]);
                }
            }
        } else {
            $id_record = post('id_intervento');
            $idstatointervento = post('id');

            $intervento = Intervento::find($id_record);
            $intervento->richiesta = post('richiesta');
            $intervento->descrizione = post('descrizione');
            $intervento->idstatointervento = $idstatointervento;
            $intervento->save();

            $idcontratto = $dbo->fetchOne('SELECT idcontratto FROM co_promemoria WHERE idintervento = :id', [
                ':id' => $id_record,
            ])['idcontratto'];
        }

        // Collegamenti tecnici/interventi
        if (!empty(post('orario_inizio')) && !empty(post('orario_fine'))) {
            $idtecnici = post('idtecnico') ?: null;
            foreach ($idtecnici as $idtecnico) {
                add_tecnico($id_record, $idtecnico, post('orario_inizio'), post('orario_fine'), $idcontratto);
            }
        }

        // Assegnazione dei tecnici all'intervento
        $tecnici_assegnati = post('tecnici_assegnati');
        if (!empty($tecnici_assegnati)) {
            $tecnici_assegnati = array_unique($tecnici_assegnati);
            $dbo->sync('in_interventi_tecnici_assegnati', [
                'id_intervento' => $id_record,
            ], [
                'id_tecnico' => $tecnici_assegnati,
            ]);
        }

        foreach ($tecnici_assegnati as $tecnico_assegnato) {
            $tecnico = Anagrafica::find($tecnico_assegnato);

            // Notifica al tecnico
            if (setting('Notifica al tecnico l\'assegnazione all\'attività')) {
                if (!empty($tecnico->email)) {
                    $template = Template::where('name', 'Notifica intervento')->first();

                    if (!empty($template)) {
                        $mail = Mail::build(auth()->getUser(), $template, $intervento->id);
                        $mail->addReceiver($tecnico->email);
                        $mail->save();
                        flash()->info(tr('Notifica al tecnico aggiunta correttamente.'));
                    }
                }
            }
        }

        if (!empty(post('ricorsiva_add'))) {
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
                    // Escludo sabato e domenica
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
                    // Escludo sabato e domenica
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
                $new->idstatointervento = $stato->id;
                $new->save();
                $idintervento = $new->id;

                // Inserimento sessioni
                if (!empty(post('riporta_sessioni_add'))) {
                    $numero_sessione = 0;
                    $sessioni = $intervento->sessioni;
                    foreach ($sessioni as $sessione) {
                        // Se è la prima sessione che copio importo la data con quella della richiesta
                        if ($numero_sessione == 0) {
                            $orario_inizio = date('Y-m-d', strtotime((string) $data_ricorrenza)).' '.date('H:i:s', strtotime($sessione->orario_inizio));
                        } else {
                            $diff = strtotime($sessione->orario_inizio) - strtotime((string) $inizio_old);
                            $orario_inizio = date('Y-m-d H:i:s', strtotime($new_sessione->orario_inizio) + $diff);
                        }

                        $diff_fine = strtotime($sessione->orario_fine) - strtotime($sessione->orario_inizio);
                        $orario_fine = date('Y-m-d H:i:s', strtotime($orario_inizio) + $diff_fine);

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

            flash()->info(tr('Intervento eliminato!'));
        } catch (InvalidArgumentException) {
            flash()->error(tr('Sono stati utilizzati alcuni serial number nel documento: impossibile procedere!'));
        }

        break;

    case 'delete_riga':
        $id_righe = (array) post('righe');

        foreach ($id_righe as $id_riga) {
            $riga = Articolo::find($id_riga) ?: Riga::find($id_riga);
            $riga = $riga ?: Sconto::find($id_riga);
            try {
                $riga->delete();
            } catch (InvalidArgumentException) {
                flash()->error(tr('Alcuni serial number sono già stati utilizzati!'));
            }

            $riga = null;
        }

        if (count($id_righe) == 1) {
            flash()->info(tr('Riga eliminata!'));
        } else {
            flash()->info(tr('Righe eliminate!'));
        }

        break;

        // Duplicazione riga
    case 'copy_riga':
        $id_righe = (array) post('righe');

        foreach ($id_righe as $id_riga) {
            $riga = Articolo::find($id_riga) ?: Riga::find($id_riga);
            $riga = $riga ?: Sconto::find($id_riga);

            $new_riga = $riga->replicate();
            $new_riga->setDocument($intervento);
            $new_riga->qta_evasa = 0;

            if ($new_riga->isArticolo()) {
                $new_riga->movimenta($new_riga->qta);
            }

            $new_riga->save();

            $riga = null;
        }

        flash()->info(tr('Righe duplicate!'));

        break;

    case 'manage_articolo':
        if (!empty(post('idriga'))) {
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
        } catch (UnexpectedValueException) {
            flash()->error(tr('Alcuni serial number sono già stati utilizzati!'));
        }

        $articolo->save();

        if (!empty(post('idriga'))) {
            flash()->info(tr('Articolo modificato!'));
        } else {
            flash()->info(tr('Articolo aggiunto!'));
        }

        // Collegamento all'Impianto tramite generazione Componente
        $id_impianto = post('id_impianto');
        $impianto = Impianto::find($id_impianto);
        if (!empty($impianto)) {
            // Data di inizio dell'intervento (data_richiesta in caso di assenza di sessioni)
            $data_registrazione = $intervento->inizio;

            // Creazione in base alla quantità
            for ($q = 0; $q < $articolo->qta; ++$q) {
                $componente = Componente::build($impianto, $articolo->articolo, $data_registrazione);
            }
        }

        break;

    case 'manage_sconto':
        if (!empty(post('idriga'))) {
            $sconto = Sconto::find(post('idriga'));
        } else {
            $sconto = Sconto::build($intervento);
        }

        $sconto->descrizione = post('descrizione');
        $sconto->setScontoUnitario(post('sconto_unitario'), post('idiva'));
        $sconto->note = post('note');
        $sconto->save();

        if (!empty(post('idriga'))) {
            flash()->info(tr('Sconto/maggiorazione modificato!'));
        } else {
            flash()->info(tr('Sconto/maggiorazione aggiunto!'));
        }

        break;

    case 'manage_riga':
        if (!empty(post('idriga'))) {
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

        if (!empty(post('idriga'))) {
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
        if (!is_subclass_of($class, Common\Document::class)) {
            return;
        }
        $documento = $class::find($id_documento);

        // Individuazione sede
        $idsede_partenza = $documento->idsede_partenza ?: 0;
        $idsede_destinazione = ($documento->direzione == 'entrata') ? $documento->idsede_destinazione : $documento->idsede_partenza;
        $idsede_destinazione = $idsede_destinazione ?: 0;

        // Creazione dell' ordine al volo
        if (post('create_document') == 'on') {
            $stato = Stato::find(post('id_stato_intervento'));
            $tipo = TipoSessione::find(post('id_tipo_intervento'));

            $anagrafica = post('idanagrafica') ? Anagrafica::find(post('idanagrafica')) : $documento->anagrafica;

            $intervento = Intervento::build($anagrafica, $tipo, $stato, post('data'), post('id_segment'));
            $intervento->idsede_partenza = $idsede_partenza;
            $intervento->idsede_destinazione = $idsede_destinazione;

            if (!empty($documento->idpagamento)) {
                $intervento->idpagamento = $documento->idpagamento;
            } else {
                $intervento->idpagamento = setting('Tipo di pagamento predefinito');
            }

            $intervento->id_documento_fe = $documento->id_documento_fe;
            $intervento->codice_cup = $documento->codice_cup;
            $intervento->codice_cig = $documento->codice_cig;
            $intervento->num_item = $documento->num_item;
            $intervento->idreferente = $documento->idreferente;
            $intervento->idagente = $documento->idagente;

            if ($class == Modules\Preventivi\Preventivo::class) {
                $intervento->id_preventivo = $documento->id;
                $intervento->richiesta = 'Attività creata da preventivo num. '.$documento->numero.'<br>'.$documento->nome;
            }
            if ($class == Modules\Ordini\Ordine::class) {
                $intervento->id_ordine = $documento->id;
                $intervento->richiesta = 'Attività creata da ordine num. '.$documento->numero_esterno;
            }

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
                $intervento->id = post('id_stato_intervento');
                $intervento->save();
            }*/

        // Messaggio informativo
        $message = tr('_DOC_ aggiunto!', [
            '_DOC_' => $documento->getReference(),
        ]);
        flash()->info($message);

        break;

    case 'firma':
        if (is_writable(Uploads::getDirectory($id_module))) {
            if (post('firma_base64') != '') {
                // Salvataggio firma
                $firma_file = 'firma_'.time().'.jpg';
                $firma_nome = post('firma_nome');

                $data = explode(',', post('firma_base64'));

                $img = Intervention\Image\ImageManagerStatic::make(base64_decode($data[1]));
                $img->resize(680, 202, function ($constraint) {
                    $constraint->aspectRatio();
                });

                if (setting('Sistema di firma') == 'Tavoletta Wacom') {
                    $img->brightness(setting('Luminosità firma Wacom'));
                    $img->contrast(setting('Contrasto firma Wacom'));
                }

                if (!$img->save(base_dir().'/files/interventi/'.$firma_file)) {
                    flash()->error(tr('Impossibile creare il file.'));
                } elseif ($dbo->query('UPDATE in_interventi SET firma_file='.prepare($firma_file).', firma_data=NOW(), firma_nome = '.prepare($firma_nome).' WHERE id='.prepare($id_record))) {
                    flash()->info(tr('Firma salvata correttamente.'));

                    $id_stato = setting("Stato dell'attività dopo la firma");
                    $stato = $dbo->selectOne('in_statiintervento', '*', ['id' => $id_stato]);
                    $intervento = Intervento::find($id_record);
                    if (!empty($stato)) {
                        $intervento = Intervento::find($id_record);
                        $intervento->idstatointervento = $stato['id'];
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
                            $tecnici_intervento = $dbo->select('in_interventi_tecnici', 'idtecnico', [], ['idintervento' => $id_record]);
                            $tecnici_assegnati = $dbo->select('in_interventi_tecnici_assegnati', 'id_tecnico AS idtecnico', [], ['id_intervento' => $id_record]);
                            $tecnici = array_unique(array_merge($tecnici_intervento, $tecnici_assegnati), SORT_REGULAR);

                            foreach ($tecnici as $tecnico) {
                                $mail_tecnico = $dbo->selectOne('an_anagrafiche', '*', ['idanagrafica' => $tecnico]);
                                if (!empty($mail_tecnico['email'])) {
                                    if (!empty($template)) {
                                        $mail = Mail::build(auth()->getUser(), $template, $id_record);
                                        $mail->addReceiver($mail_tecnico['email']);
                                        $mail->save();
                                        flash()->info(tr('Notifica al tecnico aggiunta correttamente.'));
                                    }
                                }
                            }
                        }
                    }
                } else {
                    flash()->error(tr('Errore durante il salvataggio della firma nel database.'));
                }
            } else {
                flash()->error(tr('Errore durante il salvataggio della firma.').'<br>'.tr('La firma risulta vuota.'));
            }
        } else {
            flash()->error(tr("Non è stato possibile creare la cartella _DIRECTORY_ per salvare l'immagine della firma.", [
                '_DIRECTORY_' => '<b>'.Uploads::getDirectory($id_module).'</b>',
            ]));
        }

        break;

    case 'firma_bulk':
        if (is_writable(Uploads::getDirectory($id_module))) {
            $firmati = 0;
            $non_firmati = 0;
            $id_records = filter('records') ? explode(';', filter('records')) : null;

            if (post('firma_base64') != '') {
                foreach ($id_records as $id_record) {
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
                        ++$firmati;

                        $id_stato = setting("Stato dell'attività dopo la firma");
                        $stato = $dbo->selectOne('in_statiintervento', '*', ['id' => $id_stato]);
                        $intervento = Intervento::find($id_record);
                        if (!empty($stato)) {
                            $intervento = Intervento::find($id_record);
                            $intervento->idstatointervento = $stato['id'];
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
                                $tecnici_intervento = $dbo->select('in_interventi_tecnici', 'idtecnico', [], ['idintervento' => $id_record]);
                                $tecnici_assegnati = $dbo->select('in_interventi_tecnici_assegnati', 'id_tecnico AS idtecnico', [], ['id_intervento' => $id_record]);
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
                        ++$non_firmati;
                    }
                }
            } else {
                flash()->error(tr('Errore durante il salvataggio della firma.').'<br>'.tr('La firma risulta vuota'));
            }
        } else {
            flash()->error(tr("Non è stato possibile creare la cartella _DIRECTORY_ per salvare l'immagine della firma.", [
                '_DIRECTORY_' => '<b>'.Uploads::getDirectory($id_module).'</b>',
            ]));
        }

        if (!empty($firmati)) {
            flash()->info(tr('_NUM_ interventi firmati correttamente!', [
                '_NUM_' => $firmati,
            ]));
        }

        if (!empty($non_firmati)) {
            flash()->info(tr('_NUM_ interventi non sono stati firmati correttamente!', [
                '_NUM_' => $non_firmati,
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
        $giorni = (array) post('giorni');
        $id_tecnici = (array) post('id_tecnici');

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

        // Log specifico per la rimozione sessione
        OperationLog::setInfo('id_module', $id_module);
        OperationLog::setInfo('id_plugin', $id_plugin);
        OperationLog::setInfo('id_record', $id_record);
        OperationLog::setInfo('options', $id_sessione);
        OperationLog::build(post('op'));

        // Notifica rimozione dell' intervento al tecnico
        if (setting('Notifica al tecnico la rimozione della sessione dall\'attività')) {
            if (!empty($tecnico['email'])) {
                $template = Template::where('name', 'Notifica rimozione intervento')->first();

                if (!empty($template)) {
                    $mail = Mail::build(auth()->getUser(), $template, $id_record);
                    $mail->addReceiver($tecnico['email']);
                    $mail->save();
                    flash()->info(tr('Notifica al tecnico aggiunta correttamente.'));
                }
            }
        }

        // Trigger aggiornamento intervento
        $intervento = Intervento::find($id_record);
        $intervento->updated_at = date('Y-m-d H:i:s');
        $intervento->save();

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

        // Note
        $sessione->note = post('note');

        $sessione->save();

        // Trigger aggiornamento intervento
        $intervento = Intervento::find($id_record);
        $intervento->updated_at = date('Y-m-d H:i:s');
        $intervento->save();

        break;

    case 'update_inline_sessione':
        $id_sessione = post('id_sessione');
        $sessione = Sessione::find($id_sessione);

        $sessione->orario_inizio = post('data_inizio');
        $sessione->orario_fine = post('data_fine');

        $sessione->km = post('km');

        $sessione->sconto_unitario = post('sconto_unitario');
        $sessione->tipo_sconto = post('tipo_sconto');
        $sessione->scontokm_unitario = post('scontokm_unitario');
        $sessione->tipo_scontokm = post('tipo_sconto_km');
        $sessione->save();

        // Trigger aggiornamento intervento
        $intervento = Intervento::find($id_record);
        $intervento->updated_at = date('Y-m-d H:i:s');
        $intervento->save();

        break;

        // Duplica intervento
    case 'copy':
        $id_stato = post('id_stato');
        $ora_richiesta = post('ora_richiesta');
        $copia_sessioni = post('copia_sessioni');
        $copia_righe = post('copia_righe');
        $copia_impianti = post('copia_impianti');
        $copia_allegati = post('copia_allegati');
        $data_inizio = post('data_inizio');
        $data_fine = post('data_fine');
        $giorni = (array) post('giorni');

        $period = CarbonPeriod::create($data_inizio, $data_fine);

        if (count($period) > 0) {
            $i = 0;
            // Iterate over the period
            foreach ($period as $date) {
                $data_richiesta = $date->format('Y-m-d').' '.$ora_richiesta;
                $giorno = $date->locale('IT')->dayName;

                if (in_array($giorno, $giorni)) {
                    ++$i;
                    $new = $intervento->replicate();
                    $new->idstatointervento = $id_stato;

                    // Calcolo del nuovo codice sulla base della data di richiesta
                    $new->codice = Intervento::getNextCodice($data_richiesta, $new->id_segment);
                    $new->data_richiesta = $data_richiesta;
                    $new->data_scadenza = post('ora_scadenza') ? $date->format('Y-m-d').' '.post('ora_scadenza') : null;
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

                            if ($new_riga->isArticolo()) {
                                $new_riga->movimenta($new_riga->qta);
                            }

                            $new_riga->save();
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
                                $diff = strtotime($sessione->orario_inizio) - strtotime((string) $inizio_old);
                                $orario_inizio = date('Y-m-d H:i:s', strtotime($new_sessione->orario_inizio) + $diff);
                            }

                            $diff_fine = strtotime($sessione->orario_fine) - strtotime($sessione->orario_inizio);
                            $orario_fine = date('Y-m-d H:i:s', strtotime($orario_inizio) + $diff_fine);

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
                        $impianti = $dbo->select('my_impianti_interventi', '*', [], ['idintervento' => $intervento->id]);
                        foreach ($impianti as $impianto) {
                            $dbo->insert('my_impianti_interventi', [
                                'idintervento' => $id_record,
                                'idimpianto' => $impianto['idimpianto'],
                            ]);
                        }

                        $componenti = $dbo->select('my_componenti_interventi', '*', [], ['id_intervento' => $intervento->id]);
                        foreach ($componenti as $componente) {
                            $dbo->insert('my_componenti_interventi', [
                                'id_intervento' => $id_record,
                                'id_componente' => $componente['id_componente'],
                            ]);
                        }
                    }

                    // copia allegati
                    if (!empty($copia_allegati)) {
                        $allegati = $intervento->uploads();
                        foreach ($allegati as $allegato) {
                            $allegato->copia([
                                'id_module' => $new->getModule()->id,
                                'id_record' => $new->id,
                            ]);
                        }
                    }
                }
            }

            if ($i > 0) {
                flash()->info(tr('Sono state create _NUM_ attività!', ['_NUM_' => $i]));
            } else {
                flash()->warning(tr('Nessuna attività creata per il periodo indicato.'));
            }
        } else {
            flash()->warning(tr('Nessuna attività creata.'));
        }

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
            $id_articolo = $dbo->selectOne('mg_articoli', 'id', ['deleted_at' => null, 'attivo' => 1, 'barcode' => $barcode])['id'];
            if (empty($id_articolo)) {
                $id_articolo = $dbo->selectOne('mg_articoli', 'id', ['deleted_at' => null, 'attivo' => 1, 'barcode' => '', 'codice' => $barcode])['id'];
            }
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

                $articolo->um = $originale->um;
                $articolo->qta = 1;
                $articolo->costo_unitario = $originale->prezzo_acquisto;

                // L'aliquota dell'articolo ha precedenza solo se ha aliquota a 0, altrimenti anagrafica -> articolo -> impostazione
                if ($originale->idiva_vendita) {
                    $aliquota_articolo = floatval(Aliquota::find($originale->idiva_vendita)->percentuale);
                }
                $id_iva = ($intervento->anagrafica->idiva_vendite && (!$originale->idiva_vendita || $aliquota_articolo != 0) ? $intervento->anagrafica->idiva_vendite : $originale->idiva_vendita) ?: setting('Iva predefinita');
                $id_anagrafica = $intervento->idanagrafica;
                $prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');

                // CALCOLO PREZZO UNITARIO
                $prezzo_consigliato = getPrezzoConsigliato($id_anagrafica, $dir, $id_articolo);
                if (!$prezzo_consigliato['prezzo_unitario']) {
                    $prezzo_consigliato = getPrezzoConsigliato(setting('Azienda predefinita'), $dir, $id_articolo);
                }
                $prezzo_unitario = $prezzo_consigliato['prezzo_unitario'];
                $sconto = $prezzo_consigliato['sconto'];

                $prezzo_unitario = $prezzo_unitario ?: ($prezzi_ivati ? $originale->prezzo_vendita_ivato : $originale->prezzo_vendita);
                $provvigione = $dbo->selectOne('an_anagrafiche', 'provvigione_default', ['idanagrafica' => $intervento->idagente])['provvigione_default'];

                // Aggiunta sconto combinato se è presente un piano di sconto nell'anagrafica
                $piano_sconto = $dbo->fetchOne('SELECT prc_guadagno FROM an_anagrafiche INNER JOIN mg_piani_sconto ON an_anagrafiche.id_piano_sconto_vendite=mg_piani_sconto.id WHERE idanagrafica='.prepare($id_anagrafica));
                if (!empty($piano_sconto)) {
                    $sconto = parseScontoCombinato($piano_sconto['prc_guadagno'].'+'.$sconto);
                }

                $articolo->setPrezzoUnitario($prezzo_unitario, $id_iva);
                $articolo->setSconto($sconto, 'PRC');
                $articolo->setProvvigione($provvigione ?: 0, 'PRC');
                $articolo->idsede_partenza = $intervento->idsede_partenza;
                $articolo->save();

                flash()->info(tr('Nuovo articolo aggiunto!'));
            }
        } else {
            $response['error'] = tr('Nessun articolo corrispondente a magazzino');
            echo json_encode($response);
        }

        break;

    case 'update_inline':
        $qta = post('qta');
        $id_riga = post('riga_id');
        $riga = $riga ?: Riga::find($id_riga);
        $riga = $riga ?: Articolo::find($id_riga);
        $riga = $riga ?: Sconto::find($id_riga);

        if (!empty($riga)) {
            if ($riga->isSconto()) {
                $riga->setScontoUnitario(post('sconto'), $riga->idiva);
            } else {
                $riga->qta = $qta;
                $riga->setPrezzoUnitario(post('prezzo'), $riga->idiva);
                $riga->setSconto(post('sconto'), post('tipo_sconto'));
                $riga->costo_unitario = post('costo') ?: 0;
            }
            $riga->save();

            flash()->info(tr('Riga aggiornata!'));
        }

        break;

    case 'edit-price':
        $righe = (array) post('righe');
        $numero_totale = 0;

        foreach ($righe as $riga) {
            if (!empty($riga['id'])) {
                $articolo = Articolo::find($riga['id']);
            }

            if ($articolo->prezzo_unitario != $riga['price']) {
                $articolo->setPrezzoUnitario($riga['price'], $articolo->idiva);
                $articolo->save();
                ++$numero_totale;
            }
        }

        if ($numero_totale > 1) {
            flash()->info(tr('_NUM_ prezzi modificati!', [
                '_NUM_' => $numero_totale,
            ]));
        } elseif ($numero_totale == 1) {
            flash()->info(tr('_NUM_ prezzo modificato!', [
                '_NUM_' => $numero_totale,
            ]));
        } else {
            flash()->warning(tr('Nessun prezzo modificato!'));
        }

        break;

    case 'update-price':
        $dir = 'entrata';
        $id_anagrafica = $intervento->idanagrafica;
        $prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');
        $numero_totale = 0;
        $id_righe = (array) post('righe');

        foreach ($id_righe as $id_riga) {
            $riga = Articolo::find($id_riga) ?: Riga::find($id_riga);

            // CALCOLO PREZZO UNITARIO
            $prezzo_unitario = 0;
            $sconto = 0;
            if ($riga->isArticolo()) {
                $id_articolo = $riga->idarticolo;
                $prezzo_consigliato = getPrezzoConsigliato($id_anagrafica, $dir, $id_articolo);
                if (!$prezzo_consigliato['prezzo_unitario']) {
                    $prezzo_consigliato = getPrezzoConsigliato(setting('Azienda predefinita'), $dir, $id_articolo);
                }
                $prezzo_unitario = $prezzo_consigliato['prezzo_unitario'];
                $sconto = $prezzo_consigliato['sconto'];

                $prezzo_unitario = $prezzo_unitario ?: ($prezzi_ivati ? $riga->articolo->prezzo_vendita_ivato : $riga->articolo->prezzo_vendita);
                $riga->setPrezzoUnitario($prezzo_unitario, $riga->idiva);

                if ($dir == 'entrata') {
                    $riga->costo_unitario = $riga->articolo->prezzo_acquisto;
                }
            }

            // Aggiunta sconto combinato se è presente un piano di sconto nell'anagrafica
            $piano_sconto = $dbo->fetchOne('SELECT prc_guadagno FROM an_anagrafiche INNER JOIN mg_piani_sconto ON an_anagrafiche.id_piano_sconto_vendite=mg_piani_sconto.id WHERE idanagrafica='.prepare($id_anagrafica));
            if (!empty($piano_sconto)) {
                $sconto = parseScontoCombinato($piano_sconto['prc_guadagno'].'+'.$sconto);
            }

            $riga->setSconto($sconto, 'PRC');
            $riga->save();
            ++$numero_totale;
        }

        if ($numero_totale > 1) {
            flash()->info(tr('_NUM_ prezzi modificati!', [
                '_NUM_' => $numero_totale,
            ]));
        } elseif ($numero_totale == 1) {
            flash()->info(tr('_NUM_ prezzo modificato!', [
                '_NUM_' => $numero_totale,
            ]));
        } else {
            flash()->warning(tr('Nessun prezzo modificato!'));
        }

        break;
}
