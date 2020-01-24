<?php

include_once __DIR__.'/../../core.php';

use Modules\Anagrafiche\Anagrafica;
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Fatture\Components\Articolo;
use Modules\Fatture\Components\Descrizione;
use Modules\Fatture\Components\Riga;
use Modules\Fatture\Components\Sconto;
use Modules\Fatture\Fattura;
use Modules\Fatture\Stato;
use Modules\Fatture\Tipo;
use Plugins\ExportFE\FatturaElettronica;

$module = Modules::get($id_module);

if ($module['name'] == 'Fatture di vendita') {
    $dir = 'entrata';
} else {
    $dir = 'uscita';
}

switch (post('op')) {
    case 'add':
        $idanagrafica = post('idanagrafica');
        $data = post('data');
        $idtipodocumento = post('idtipodocumento');
        $id_segment = post('id_segment');

        $anagrafica = Anagrafica::find($idanagrafica);
        $tipo = Tipo::find($idtipodocumento);

        $fattura = Fattura::build($anagrafica, $tipo, $data, $id_segment);
        $id_record = $fattura->id;

        flash()->info(tr('Aggiunta fattura numero _NUM_!', [
            '_NUM_' => $fattura->numero,
        ]));

        break;

    case 'update':
        if (post('id_record') !== null) {
            $stato_precedente = $fattura->stato;

            $stato = Stato::find(post('idstatodocumento'));
            $fattura->stato()->associate($stato);

            $tipo = Tipo::find(post('idtipodocumento'));
            $fattura->tipo()->associate($tipo);

            $fattura->data = post('data');

            if ($dir == 'entrata') {
                $fattura->data_registrazione = post('data');
            } else {
                $fattura->data_registrazione = post('data_registrazione');
            }

            $fattura->data_competenza = post('data_competenza');

            $fattura->numero_esterno = post('numero_esterno');
            $fattura->note = post('note');
            $fattura->note_aggiuntive = post('note_aggiuntive');

            $fattura->idanagrafica = post('idanagrafica');
            $fattura->idagente = post('idagente');
            $fattura->idpagamento = post('idpagamento');
            $fattura->idbanca = post('idbanca');
            $fattura->idcausalet = post('idcausalet');
            $fattura->idspedizione = post('idspedizione');
            $fattura->idporto = post('idporto');
            $fattura->idaspettobeni = post('idaspettobeni');
            $fattura->idvettore = post('idvettore');
            $fattura->idsede_partenza = post('idsede_partenza');
            $fattura->idsede_destinazione = post('idsede_destinazione');
            $fattura->idconto = post('idconto');
            $fattura->split_payment = post('split_payment') ?: 0;
            $fattura->is_fattura_conto_terzi = post('is_fattura_conto_terzi') ?: 0;
            $fattura->n_colli = post('n_colli');
            $fattura->tipo_resa = post('tipo_resa');

            $fattura->rivalsainps = 0;
            $fattura->ritenutaacconto = 0;
            $fattura->iva_rivalsainps = 0;
            $fattura->id_ritenuta_contributi = post('id_ritenuta_contributi') ?: null;

            $fattura->codice_stato_fe = post('codice_stato_fe') ?: null;

            // Informazioni per le fatture di acquisto
            if ($dir == 'uscita') {
                $fattura->numero = post('numero');
                $fattura->numero_esterno = post('numero_esterno');
                $fattura->idrivalsainps = post('id_rivalsa_inps');
                $fattura->idritenutaacconto = post('id_ritenuta_acconto');
            }

            // Operazioni sul bollo
            $fattura->addebita_bollo = post('addebita_bollo');
            $bollo_automatico = post('bollo_automatico');
            if (empty($bollo_automatico)) {
                $fattura->bollo = post('bollo');
            } else {
                $fattura->bollo = null;
            }

            // Operazioni sulla dichiarazione d'intento
            $dichiarazione_precedente = $fattura->dichiarazione;
            $fattura->id_dichiarazione_intento = post('id_dichiarazione_intento') ?: null;

            $fattura->save();

            if ($fattura->direzione == 'entrata' && $stato_precedente->descrizione == 'Bozza' && $stato['descrizione'] == 'Emessa') {
                // Generazione automatica della Fattura Elettronica
                $stato_fe = empty($fattura->codice_stato_fe) || in_array($fattura->codice_stato_fe, ['GEN', 'NS', 'EC02']);
                $checks = FatturaElettronica::controllaFattura($fattura);
                if ($stato_fe && empty($checks)) {
                    try {
                        $fattura_pa = new FatturaElettronica($id_record);
                        $file = $fattura_pa->save(DOCROOT.'/'.FatturaElettronica::getDirectory());

                        flash()->info(tr('Fattura elettronica generata correttamente!'));

                        if (!$fattura_pa->isValid()) {
                            $errors = $fattura_pa->getErrors();

                            flash()->warning(tr('La fattura elettronica potrebbe avere delle irregolarità!').' '.tr('Controllare i seguenti campi: _LIST_', [
                                    '_LIST_' => implode(', ', $errors),
                                ]).'.');
                        }
                    } catch (UnexpectedValueException $e) {
                    }
                } elseif (!empty($checks)) {
                    $message = tr('La fattura elettronica non è stata generata a causa di alcune informazioni mancanti').':';

                    foreach ($checks as $check) {
                        $message .= '
<p><b>'.$check['name'].' '.$check['link'].'</b></p>
<ul>';

                        foreach ($check['errors'] as $error) {
                            if (!empty($error)) {
                                $message .= '
    <li>'.$error.'</li>';
                            }
                        }

                        $message .= '
</ul>';
                    }

                    flash()->warning($message);
                }
            }

            aggiorna_sedi_movimenti('documenti', $id_record);

            flash()->info(tr('Fattura modificata correttamente!'));
        }

        break;

    // Ricalcolo scadenze
    case 'ricalcola_scadenze':
        $fattura->registraScadenze(false, true);

        break;

    // Ricalcolo scadenze
    case 'controlla_totali':
        try {
            $xml = \Util\XML::read($fattura->getXML());

            $dati_generali = $xml['FatturaElettronicaBody']['DatiGenerali']['DatiGeneraliDocumento'];
            $totale_documento = abs(floatval($dati_generali['ImportoTotaleDocumento'])) ?: null;
        } catch (Exception $e) {
            $totale_documento = null;
        }

        echo json_encode([
            'stored' => $totale_documento,
            'calculated' => $fattura->totale,
        ]);

        break;

    // eliminazione documento
    case 'delete':
        try {
            $fattura->delete();

            $dbo->query('DELETE FROM co_scadenziario WHERE iddocumento='.prepare($id_record));
            $dbo->query('DELETE FROM co_movimenti WHERE iddocumento='.prepare($id_record));

            // Azzeramento collegamento della rata contrattuale alla pianificazione
            $dbo->query('UPDATE co_ordiniservizio_pianificazionefatture SET iddocumento=0 WHERE iddocumento='.prepare($id_record));

            flash()->info(tr('Fattura eliminata!'));
        } catch (InvalidArgumentException $e) {
            flash()->error(tr('Sono stati utilizzati alcuni serial number nel documento: impossibile procedere!'));
        }

        break;

    // Duplicazione fattura
    case 'copy':
        if ($id_record) {
            // Duplicazione righe
            $righe = $dbo->fetchArray('SELECT * FROM co_righe_documenti WHERE iddocumento='.prepare($id_record));

            // Lettura dati fattura attuale
            $rs = $dbo->fetchArray('SELECT * FROM co_documenti WHERE id='.prepare($id_record));

            $id_segment = $rs[0]['id_segment'];

            // Calcolo prossimo numero fattura
            $numero = get_new_numerofattura(date('Y-m-d'));

            if ($dir == 'entrata') {
                $numero_esterno = get_new_numerosecondariofattura(date('Y-m-d'));
            } else {
                $numero_esterno = '';
            }

            // Duplicazione intestazione
            $dbo->query('INSERT INTO co_documenti(numero, numero_esterno, data, idanagrafica, idcausalet, idspedizione, idporto, idaspettobeni, idvettore, n_colli, idsede_partenza, idsede_destinazione, idtipodocumento, idstatodocumento, idpagamento, idconto, idrivalsainps, idritenutaacconto, rivalsainps, iva_rivalsainps, ritenutaacconto, bollo, note, note_aggiuntive, buono_ordine, id_segment) VALUES('.prepare($numero).', '.prepare($numero_esterno).', NOW(), '.prepare($rs[0]['idanagrafica']).', '.prepare($rs[0]['idcausalet']).', '.prepare($rs[0]['idspedizione']).', '.prepare($rs[0]['idporto']).', '.prepare($rs[0]['idaspettobeni']).', '.prepare($rs[0]['idvettore']).', '.prepare($rs[0]['n_colli']).', '.prepare($rs[0]['idsede_partenza']).', '.prepare($rs[0]['idsede_destinazione']).', '.prepare($rs[0]['idtipodocumento']).', (SELECT id FROM co_statidocumento WHERE descrizione=\'Bozza\'), '.prepare($rs[0]['idpagamento']).', '.prepare($rs[0]['idconto']).', '.prepare($rs[0]['idrivalsainps']).', '.prepare($rs[0]['idritenutaacconto']).', '.prepare($rs[0]['rivalsainps']).', '.prepare($rs[0]['iva_rivalsainps']).', '.prepare($rs[0]['ritenutaacconto']).', '.prepare($rs[0]['bollo']).', '.prepare($rs[0]['note']).', '.prepare($rs[0]['note_aggiuntive']).', '.prepare($rs[0]['buono_ordine']).', '.prepare($rs[0]['id_segment']).')');
            $id_record = $dbo->lastInsertedID();

            // TODO: sistemare la duplicazione delle righe generiche e degli articoli, ignorando interventi, ddt, ordini, preventivi
            foreach ($righe as $riga) {
                // Scarico/carico nuovamente l'articolo da magazzino
                if (!empty($riga['idarticolo'])) {
                    add_articolo_infattura($id_record, $riga['idarticolo'], $riga['descrizione'], $riga['idiva'], $riga['qta'], $riga['subtotale'], $riga['sconto'], $riga['sconto_unitario'], $riga['tipo_sconto'], $riga['idintervento'], $riga['idconto'], $riga['um']);
                } else {
                    $dbo->query('INSERT INTO co_righe_documenti(iddocumento, idordine, idddt, idintervento, idarticolo, idpreventivo, idcontratto, is_descrizione, idtecnico, idagente, idconto, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, sconto, sconto_unitario, tipo_sconto, idritenutaacconto, ritenutaacconto, idrivalsainps, rivalsainps, um, qta, `order`) VALUES('.prepare($id_record).', 0, 0, 0, '.prepare($riga['idarticolo']).', '.prepare($riga['idpreventivo']).', '.prepare($riga['idcontratto']).', '.prepare($riga['is_descrizione']).', '.prepare($riga['idtecnico']).', '.prepare($riga['idagente']).', '.prepare($riga['idconto']).', '.prepare($riga['idiva']).', '.prepare($riga['desc_iva']).', '.prepare($riga['iva']).', '.prepare($riga['iva_indetraibile']).', '.prepare($riga['descrizione']).', '.prepare($riga['subtotale']).', '.prepare($riga['sconto']).', '.prepare($riga['sconto_unitario']).', '.prepare($riga['tipo_sconto']).', '.prepare($riga['idritenutaacconto']).', '.prepare($riga['ritenutaacconto']).', '.prepare($riga['idrivalsainps']).', '.prepare($riga['rivalsainps']).', '.prepare($riga['um']).', '.prepare($riga['qta']).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento='.prepare($id_record).'))');
                }
            }

            // Ricalcolo inps, ritenuta e bollo (se la fattura non è stata pagata)
            ricalcola_costiagg_fattura($id_record);
            aggiorna_sedi_movimenti('documenti', $id_record);

            flash()->info(tr('Fattura duplicata correttamente!'));
        }

        break;

    case 'reopen':
        if (!empty($id_record)) {
            $dbo->query("UPDATE co_documenti SET idstatodocumento=(SELECT id FROM co_statidocumento WHERE descrizione='Bozza') WHERE id=".prepare($id_record));
            elimina_movimenti($id_record, 1);
            elimina_scadenze($id_record);
            ricalcola_costiagg_fattura($id_record);
            flash()->info(tr('Fattura riaperta!'));
        }

        break;

    case 'add_intervento':
        $id_intervento = post('idintervento');

        if (!empty($id_record) && $id_intervento !== null) {
            $copia_descrizione = post('copia_descrizione');
            $intervento = $dbo->fetchOne('SELECT descrizione FROM in_interventi WHERE id = '.prepare($id_intervento));
            if (!empty($copia_descrizione) && !empty($intervento['descrizione'])) {
                $riga = Descrizione::build($fattura);
                $riga->descrizione = $intervento['descrizione'];
                $riga->idintervento = $id_intervento;
                $riga->save();
            }

            aggiungi_intervento_in_fattura($id_intervento, $id_record, post('descrizione'), post('idiva'), post('idconto'), post('id_rivalsa_inps'), post('id_ritenuta_acconto'), post('calcolo_ritenuta_acconto'));

            flash()->info(tr('Intervento _NUM_ aggiunto!', [
                '_NUM_' => $idintervento,
            ]));
        }
        break;

    case 'manage_documento_fe':
        $data = Filter::getPOST();

        $ignore = [
            'id_plugin',
            'id_module',
            'id_record',
            'backto',
            'hash',
            'op',
            'idriga',
            'dir',
        ];
        foreach ($ignore as $name) {
            unset($data[$name]);
        }

        $fattura->dati_aggiuntivi_fe = $data;
        $fattura->save();

        flash()->info(tr('Dati FE aggiornati correttamente!'));

        break;

    case 'manage_riga_fe':
        $id_riga = post('id_riga');
        if ($id_riga != null) {
            $riga = Articolo::find($id_riga) ?: Riga::find($id_riga);
            $riga = $riga ?: Descrizione::find($id_riga);
            $riga = $riga ?: Sconto::find($id_riga);

            $data = Filter::getPOST();

            $ignore = [
                'id_plugin',
                'id_module',
                'id_record',
                'backto',
                'hash',
                'op',
                'idriga',
                'dir',
            ];
            foreach ($ignore as $name) {
                unset($data[$name]);
            }

            $riga->dati_aggiuntivi_fe = $data;
            $riga->save();

            flash()->info(tr('Dati FE aggiornati correttamente!'));
        }

        break;

    case 'manage_articolo':
        if (post('idriga') != null) {
            $articolo = Articolo::find(post('idriga'));
        } else {
            $originale = ArticoloOriginale::find(post('idarticolo'));
            $articolo = Articolo::build($fattura, $originale);
        }

        $qta = post('qta');
        if (!empty($record['is_reversed'])) {
            $qta = -$qta;
        }

        $articolo->descrizione = post('descrizione');
        $articolo->um = post('um') ?: null;

        $articolo->id_iva = post('idiva');
        $articolo->idconto = post('idconto');

        $articolo->calcolo_ritenuta_acconto = post('calcolo_ritenuta_acconto') ?: null;
        $articolo->id_ritenuta_acconto = post('id_ritenuta_acconto') ?: null;
        $articolo->ritenuta_contributi = boolval(post('ritenuta_contributi'));
        $articolo->id_rivalsa_inps = post('id_rivalsa_inps') ?: null;

        $articolo->prezzo_unitario_acquisto = post('prezzo_acquisto') ?: 0;
        $articolo->prezzo_unitario_vendita = post('prezzo');
        $articolo->sconto_unitario = post('sconto');
        $articolo->tipo_sconto = post('tipo_sconto');

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

        // Ricalcolo inps, ritenuta e bollo
        ricalcola_costiagg_fattura($id_record);

        break;

    case 'manage_sconto':
        if (post('idriga') != null) {
            $sconto = Sconto::find(post('idriga'));
        } else {
            $sconto = Sconto::build($fattura);
        }

        $sconto->descrizione = post('descrizione');

        $sconto->id_iva = post('idiva');
        $sconto->idconto = post('idconto');

        $sconto->calcolo_ritenuta_acconto = post('calcolo_ritenuta_acconto') ?: null;
        $sconto->id_ritenuta_acconto = post('id_ritenuta_acconto') ?: null;
        $sconto->ritenuta_contributi = boolval(post('ritenuta_contributi'));
        $sconto->id_rivalsa_inps = post('id_rivalsa_inps') ?: null;

        $sconto->sconto_unitario = post('sconto_unitario');
        $sconto->tipo_sconto = 'UNT';

        $sconto->save();

        if (post('idriga') != null) {
            flash()->info(tr('Sconto/maggiorazione modificato!'));
        } else {
            flash()->info(tr('Sconto/maggiorazione aggiunto!'));
        }

        // Ricalcolo inps, ritenuta e bollo
        ricalcola_costiagg_fattura($id_record);

        break;

    case 'manage_riga':
        if (post('idriga') != null) {
            $riga = Riga::find(post('idriga'));
        } else {
            $riga = Riga::build($fattura);
        }

        $qta = post('qta');
        if (!empty($record['is_reversed'])) {
            $qta = -$qta;
        }

        $riga->descrizione = post('descrizione');
        $riga->um = post('um') ?: null;

        $riga->id_iva = post('idiva');
        $riga->idconto = post('idconto');

        $riga->calcolo_ritenuta_acconto = post('calcolo_ritenuta_acconto') ?: null;
        $riga->id_ritenuta_acconto = post('id_ritenuta_acconto') ?: null;
        $riga->ritenuta_contributi = boolval(post('ritenuta_contributi'));
        $riga->id_rivalsa_inps = post('id_rivalsa_inps') ?: null;

        $riga->prezzo_unitario_acquisto = post('prezzo_acquisto') ?: 0;
        $riga->prezzo_unitario_vendita = post('prezzo');
        $riga->sconto_unitario = post('sconto');
        $riga->tipo_sconto = post('tipo_sconto');

        $riga->qta = $qta;

        $riga->save();

        if (post('idriga') != null) {
            flash()->info(tr('Riga modificata!'));
        } else {
            flash()->info(tr('Riga aggiunta!'));
        }

        // Ricalcolo inps, ritenuta e bollo
        ricalcola_costiagg_fattura($id_record);

        break;

    case 'manage_descrizione':
        if (post('idriga') != null) {
            $riga = Descrizione::find(post('idriga'));
        } else {
            $riga = Descrizione::build($fattura);
        }

        $riga->descrizione = post('descrizione');

        $riga->save();

        if (post('idriga') != null) {
            flash()->info(tr('Riga descrittiva modificata!'));
        } else {
            flash()->info(tr('Riga descrittiva aggiunta!'));
        }

        break;

    // Scollegamento intervento da documento
    case 'unlink_intervento':
        if (!empty($id_record) && post('idriga') !== null) {
            $id_riga = post('idriga');

            $righe = $fattura->getRighe();
            $riga = $righe->find($id_riga);

            $righe_intervento = $righe->where('idintervento', $riga->idintervento);
            foreach ($righe_intervento as $r) {
                $r->delete();
            }

            flash()->info(tr('Intervento _NUM_ rimosso!', [
                '_NUM_' => $idintervento,
            ]));
        }
        break;

    // Scollegamento riga generica da documento
    case 'delete_riga':
        $id_riga = post('idriga');
        $type = post('type');
        $riga = $fattura->getRiga($type, $id_riga);

        if (!empty($riga)) {
            try {
                $riga->delete();

                // Ricalcolo inps, ritenuta e bollo
                ricalcola_costiagg_fattura($id_record);

                flash()->info(tr('Riga rimossa!'));
            } catch (InvalidArgumentException $e) {
                flash()->error(tr('Alcuni serial number sono già stati utilizzati!'));
            }
        }
        break;

    case 'add_serial':
        $articolo = Articolo::find(post('idriga'));

        $serials = (array) post('serial');

        $articolo->serials = $serials;
        $articolo->save();

        break;

    case 'update_position':
        $orders = explode(',', $_POST['order']);
        $order = 0;

        foreach ($orders as $idriga) {
            $dbo->query('UPDATE `co_righe_documenti` SET `order`='.prepare($order).' WHERE id='.prepare($idriga));
            ++$order;
        }

        break;

    // Aggiunta di un documento in fattura
    case 'add_documento':
        $id_documento = post('id_documento');
        $type = post('type');

        $movimenta = true;
        $idsede = 0;

        if ($type == 'ordine') {
            $documento = \Modules\Ordini\Ordine::find($id_documento);
            $idsede = $documento->idsede;
        } elseif ($type == 'ddt') {
            $documento = \Modules\DDT\DDT::find($id_documento);
            $idsede = ($documento->direzione == 'entrata') ? $documento->idsede_destinazione : $documento->idsede_partenza;
            $movimenta = false;
        } elseif ($type == 'preventivo') {
            $documento = \Modules\Preventivi\Preventivo::find($id_documento);
            $idsede = $documento->idsede;
        } elseif ($type == 'contratto') {
            $documento = \Modules\Contratti\Contratto::find($id_documento);
            $idsede = $documento->idsede;
        }

        // Creazione della fattura al volo
        if (post('create_document') == 'on') {
            $descrizione = ($dir == 'entrata') ? 'Fattura immediata di vendita' : 'Fattura immediata di acquisto';
            $tipo = Tipo::where('descrizione', $descrizione)->first();

            $fattura = Fattura::build($documento->anagrafica, $tipo, post('data'), post('id_segment'));
            $fattura->idpagamento = $documento->idpagamento;
            $fattura->idsede_destinazione = $idsede;
            $fattura->id_ritenuta_contributi = post('id_ritenuta_contributi') ?: null;
            $fattura->save();

            $id_record = $fattura->id;
        }

        $calcolo_ritenuta_acconto = post('calcolo_ritenuta_acconto') ?: null;
        $id_ritenuta_acconto = post('id_ritenuta_acconto') ?: null;
        $ritenuta_contributi = boolval(post('ritenuta_contributi'));
        $id_rivalsa_inps = post('id_rivalsa_inps') ?: null;
        $id_conto = post('id_conto');

        $righe = $documento->getRighe();
        foreach ($righe as $riga) {
            if (post('evadere')[$riga->id] == 'on') {
                $qta = post('qta_da_evadere')[$riga->id];

                $copia = $riga->copiaIn($fattura, $qta);
                $copia->id_conto = $id_conto;

                $copia->calcolo_ritenuta_acconto = $calcolo_ritenuta_acconto;
                $copia->id_ritenuta_acconto = $id_ritenuta_acconto;
                $copia->id_rivalsa_inps = $id_rivalsa_inps;
                $copia->ritenuta_contributi = $ritenuta_contributi;

                // Aggiornamento seriali dalla riga dell'ordine
                if ($copia->isArticolo()) {
                    if ($movimenta) {
                        $copia->movimenta($copia->qta);
                    }

                    $serials = is_array(post('serial')[$riga->id]) ? post('serial')[$riga->id] : [];

                    $copia->serials = $serials;
                }

                $copia->save();
            }
        }

        ricalcola_costiagg_fattura($id_record);

        $message = '';
        if ($type == 'ordine') {
            $message = tr('Ordine _NUM_ aggiunto!', [
                '_NUM_' => $ordine->numero,
            ]);
        } elseif ($type == 'ddt') {
            $message = tr('DDT _NUM_ aggiunto!', [
                '_NUM_' => $ordine->numero,
            ]);
        } elseif ($type == 'preventivo') {
            $message = tr('Preventivo _NUM_ aggiunto!', [
                '_NUM_' => $ordine->numero,
            ]);
        } elseif ($type == 'contratto') {
            $message = tr('Contratto _NUM_ aggiunto!', [
                '_NUM_' => $ordine->numero,
            ]);
        }

        flash()->info($message);

        break;

    // Nota di credito
    case 'nota_credito':
        $id_documento = post('id_documento');
        $fattura = Fattura::find($id_documento);

        $id_segment = post('id_segment');
        $data = post('data');

        $anagrafica = $fattura->anagrafica;
        $tipo = Tipo::where('descrizione', 'Nota di credito')->where('dir', 'entrata')->first();

        $nota = Fattura::build($anagrafica, $tipo, $data, $id_segment);
        $nota->ref_documento = $fattura->id;
        $nota->idconto = $fattura->idconto;
        $nota->idpagamento = $fattura->idpagamento;
        $nota->idbanca = $fattura->idbanca;
        $nota->idsede_partenza = $fattura->idsede_partenza;
        $nota->idsede_destinazione = $fattura->idsede_destinazione;
        $nota->save();

        $righe = $fattura->getRighe();
        foreach ($righe as $riga) {
            if (post('evadere')[$riga->id] == 'on') {
                $qta = post('qta_da_evadere')[$riga->id];

                $copia = $riga->copiaIn($nota, -$qta);
                $copia->ref_riga_documento = $riga->id;

                // Aggiornamento seriali dalla riga dell'ordine
                if ($copia->isArticolo()) {
                    $copia->movimenta($copia->qta);

                    $serials = is_array(post('serial')[$riga->id]) ? post('serial')[$riga->id] : [];

                    $copia->serials = $serials;
                    $riga->removeSerials($serials);
                }

                $copia->save();
            }
        }

        $id_record = $nota->id;
        aggiorna_sedi_movimenti('documenti', $id_record);

        break;

    case 'transform':
        $fattura->id_segment = post('id_segment');
        $fattura->data = post('data');
        $fattura->save();

        break;
}

// Nota di debito
if (get('op') == 'nota_addebito') {
    $rs_segment = $dbo->fetchArray("SELECT * FROM zz_segments WHERE predefined_addebito='1'");
    if (!empty($rs_segment)) {
        $id_segment = $rs_segment[0]['id'];
    } else {
        $id_segment = $record['id_segment'];
    }

    $anagrafica = $fattura->anagrafica;
    $tipo = Tipo::where('descrizione', 'Nota di debito')->where('dir', 'entrata')->first();
    $data = $fattura->data;

    $nota = Fattura::build($anagrafica, $tipo, $data, $id_segment);
    $nota->ref_documento = $fattura->id;
    $nota->idconto = $fattura->idconto;
    $nota->idpagamento = $fattura->idpagamento;
    $nota->idbanca = $fattura->idbanca;
    $nota->idsede_partenza = $fattura->idsede_partenza;
    $nota->idsede_destinazione = $fattura->idsede_destinazione;
    $nota->save();

    $id_record = $nota->id;
    aggiorna_sedi_movimenti('documenti', $id_record);
}
