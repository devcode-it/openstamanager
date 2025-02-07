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

use Carbon\Carbon;
use Modules\Anagrafiche\Anagrafica;
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Iva\Aliquota;
use Modules\Preventivi\Components\Articolo;
use Modules\Preventivi\Components\Descrizione;
use Modules\Preventivi\Components\Riga;
use Modules\Preventivi\Components\Sconto;
use Modules\Preventivi\Preventivo;
use Modules\Preventivi\Stato;
use Modules\TipiIntervento\Tipo as TipoSessione;

switch (post('op')) {
    case 'add':
        $idanagrafica = post('idanagrafica');
        $nome = post('nome');
        $idtipointervento = post('idtipointervento');
        $data_bozza = post('data_bozza');
        $idsede_destinazione = post('idsede_destinazione');
        $id_segment = post('id_segment');

        $anagrafica = Anagrafica::find($idanagrafica);
        $tipo = TipoSessione::find($idtipointervento);

        $preventivo = Preventivo::build($anagrafica, $tipo, $nome, $data_bozza, $idsede_destinazione, $id_segment);

        $preventivo->esclusioni = setting('Esclusioni default preventivi');
        $preventivo->idstato = post('idstato');
        $preventivo->save();

        $id_record = $preventivo->id;

        if (isAjaxRequest()) {
            echo json_encode(['id' => $id_record, 'text' => tr('Preventivo').' '.$preventivo->numero.' '.tr('del').' '.dateFormat($preventivo->data_bozza).' - '.$preventivo->nome]);
        }

        flash()->info(tr('Aggiunto preventivo numero _NUM_!', [
            '_NUM_' => $preventivo['numero'],
        ]));

        break;

    case 'update':
        if (!empty($id_record)) {
            $preventivo->idstato = post('idstato');
            $preventivo->nome = post('nome');
            $preventivo->idanagrafica = post('idanagrafica');
            $preventivo->idsede_partenza = post('idsede_partenza');
            $preventivo->idsede_destinazione = post('idsede_destinazione');
            $preventivo->idagente = post('idagente');
            $preventivo->idreferente = post('idreferente');
            $preventivo->idpagamento = post('idpagamento');
            $preventivo->idporto = post('idporto');
            $preventivo->tempi_consegna = post('tempi_consegna');
            $preventivo->numero = post('numero');
            $preventivo->condizioni_fornitura = post('condizioni_fornitura');
            $preventivo->informazioniaggiuntive = post('informazioniaggiuntive');
            $preventivo->note = post('note');

            // Informazioni sulle date del documento
            $preventivo->data_bozza = post('data_bozza') ?: null;
            $preventivo->data_rifiuto = post('data_rifiuto') ?: null;

            // Dati relativi alla validità del documento
            $preventivo->validita = post('validita');
            $preventivo->tipo_validita = post('tipo_validita');
            $preventivo->data_accettazione = post('data_accettazione') ?: null;
            $preventivo->data_conclusione = post('data_conclusione') ?: null;

            $preventivo->esclusioni = post('esclusioni');
            $preventivo->garanzia = post('garanzia');
            $preventivo->descrizione = post('descrizione');
            $preventivo->id_documento_fe = post('id_documento_fe');
            $preventivo->num_item = post('num_item');
            $preventivo->codice_cig = post('codice_cig');
            $preventivo->codice_cup = post('codice_cup');
            $preventivo->idtipointervento = post('idtipointervento');
            $preventivo->idiva = post('idiva');
            $preventivo->setScontoFinale(post('sconto_finale'), post('tipo_sconto_finale'));

            $preventivo->save();

            flash()->info(tr('Preventivo modificato correttamente!'));
        }

        break;

        // Duplica preventivo
    case 'copy':
        // Copia del preventivo
        $new = $preventivo->replicate();
        $new->numero = Preventivo::getNextNumero(Carbon::now(), $new->id_segment);
        $new->data_bozza = Carbon::now();

        $stato_preventivo = Stato::where('name', 'Bozza')->first()->id;
        $new->stato()->associate($stato_preventivo);

        $new->save();

        $new->master_revision = $new->id;
        $new->descrizione_revision = '';
        $new->numero_revision = 0;
        $new->save();

        $id_record = $new->id;

        // Copia delle righe
        $righe = $preventivo->getRighe();
        foreach ($righe as $riga) {
            $new_riga = $riga->replicate();
            $new_riga->setDocument($new);

            $new_riga->qta_evasa = 0;
            $new_riga->save();
        }

        flash()->info(tr('Preventivo duplicato correttamente!'));
        break;

    case 'addintervento':
        if (post('idintervento') !== null) {
            // Selezione costi da intervento
            $idintervento = post('idintervento');
            $rs = $dbo->fetchArray('SELECT * FROM in_interventi WHERE id='.prepare($idintervento));
            $costo_km = $rs[0]['prezzo_km_unitario'];
            $costo_orario = $rs[0]['prezzo_ore_unitario'];

            $dbo->update('in_interventi', [
                'id_preventivo' => $id_record,
            ], ['id' => $idintervento]);

            // Imposto il preventivo nello stato "In lavorazione" se inizio ad aggiungere interventi
            $dbo->query('UPDATE `co_preventivi` SET `idstato`=(SELECT `id` FROM `co_statipreventivi` LEFT JOIN `co_statipreventivi_lang ON (`co_statipreventivi_lang`.`id_record` = `co_statipreventivi`.`id` AND `co_statipreventivi_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).") WHERE `title`='In lavorazione') WHERE `co_preventivi`.`id`=".prepare($id_record));

            flash()->info(tr('Intervento _NUM_ aggiunto!', [
                '_NUM_' => $rs[0]['codice'],
            ]));
        }
        break;

        // Scollegamento intervento da preventivo
    case 'unlink':
        if (isset($_GET['idpreventivo']) && isset($_GET['idintervento'])) {
            $idintervento = get('idintervento');

            $dbo->update('in_interventi', [
                'id_preventivo' => null,
            ], ['id' => $idintervento]);

            flash()->info(tr('Intervento _NUM_ rimosso!', [
                '_NUM_' => $idintervento,
            ]));
        }
        break;

        // Eliminazione preventivo
    case 'delete':
        try {
            $preventivo->delete();

            flash()->info(tr('Preventivo eliminato!'));
        } catch (InvalidArgumentException) {
            flash()->error(tr('Sono stati utilizzati alcuni serial number nel documento: impossibile procedere!'));
        }

        break;

    case 'manage_articolo':
        if (post('idriga') != null) {
            $articolo = Articolo::find(post('idriga'));
        } else {
            $originale = ArticoloOriginale::find(post('idarticolo'));
            $articolo = Articolo::build($preventivo, $originale);
            $articolo->id_dettaglio_fornitore = post('id_dettaglio_fornitore') ?: null;
        }

        $qta = post('qta');

        $articolo->descrizione = post('descrizione');
        $articolo->note = post('note');
        $articolo->um = post('um') ?: null;
        $articolo->data_evasione = post('data_evasione') ?: null;
        $articolo->ora_evasione = post('ora_evasione') ?: null;
        $articolo->confermato = post('confermato') ?: 0;

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

        // Impostare data evasione su tutte le righe
        if (post('data_evasione_all') == 1) {
            $righe = $preventivo->getRighe()->where('is_descrizione', '=', '0');

            foreach ($righe as $riga) {
                $riga->data_evasione = post('data_evasione') ?: null;
                $riga->ora_evasione = post('ora_evasione') ?: null;
                $riga->save();
            }
        }
        // Impostare confermato su tutte le righe
        if (post('confermato_all') == 1) {
            $righe = $preventivo->getRighe()->where('is_descrizione', '=', '0');

            foreach ($righe as $riga) {
                $riga->confermato = post('confermato') ?: 0;
                $riga->save();
            }
        }

        if (post('idriga') != null) {
            flash()->info(tr('Articolo modificato!'));
        } else {
            flash()->info(tr('Articolo aggiunto!'));
        }

        break;

    case 'manage_sconto':
        if (post('idriga') != null) {
            $sconto = Sconto::find(post('idriga'));
        } else {
            $sconto = Sconto::build($preventivo);
        }

        $sconto->descrizione = post('descrizione');
        $sconto->note = post('note');
        $sconto->setScontoUnitario(post('sconto_unitario'), post('idiva'));
        $sconto->confermato = setting('Conferma automaticamente le quantità nei preventivi');
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
            $riga = Riga::build($preventivo);
        }

        $qta = post('qta');

        $riga->descrizione = post('descrizione');
        $riga->note = post('note');
        $riga->um = post('um') ?: null;
        $riga->data_evasione = post('data_evasione') ?: null;
        $riga->ora_evasione = post('ora_evasione') ?: null;
        $riga->confermato = post('confermato') ?: 0;

        $riga->costo_unitario = post('costo_unitario') ?: 0;
        $riga->setPrezzoUnitario(post('prezzo_unitario'), post('idiva'));
        $riga->setSconto(post('sconto'), post('tipo_sconto'));
        $riga->setProvvigione(post('provvigione'), post('tipo_provvigione'));

        $riga->qta = $qta;

        $riga->save();

        // Impostare data evasione su tutte le righe
        if (post('data_evasione_all') == 1) {
            $righe = $preventivo->getRighe()->where('is_descrizione', '=', '0');

            foreach ($righe as $riga) {
                $riga->data_evasione = post('data_evasione') ?: null;
                $riga->ora_evasione = post('ora_evasione') ?: null;
                $riga->save();
            }
        }
        // Impostare confermato su tutte le righe
        if (post('confermato_all') == 1) {
            $righe = $preventivo->getRighe()->where('is_descrizione', '=', '0');

            foreach ($righe as $riga) {
                $riga->confermato = post('confermato') ?: 0;
                $riga->save();
            }
        }

        if (post('idriga') != null) {
            flash()->info(tr('Riga modificata!'));
        } else {
            flash()->info(tr('Riga aggiunta!'));
        }

        break;

    case 'manage_descrizione':
        if (post('idriga') != null) {
            $riga = Descrizione::find(post('idriga'));
        } else {
            $riga = Descrizione::build($preventivo);
        }

        $riga->descrizione = post('descrizione');
        $riga->note = post('note');
        $riga->is_titolo = post('is_titolo');
        $riga->save();

        if (post('idriga') != null) {
            flash()->info(tr('Riga descrittiva modificata!'));
        } else {
            flash()->info(tr('Riga descrittiva aggiunta!'));
        }

        break;

        // Eliminazione riga
    case 'delete_riga':
        $id_righe = (array) post('righe');

        foreach ($id_righe as $id_riga) {
            $riga = Articolo::find($id_riga) ?: Riga::find($id_riga);
            $riga = $riga ?: Descrizione::find($id_riga);
            $riga = $riga ?: Sconto::find($id_riga);
            $riga->delete();

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
            $riga = $riga ?: Descrizione::find($id_riga);
            $riga = $riga ?: Sconto::find($id_riga);

            $new_riga = $riga->replicate();
            $new_riga->setDocument($preventivo);
            $new_riga->qta_evasa = 0;
            $new_riga->save();

            $riga = null;
        }

        flash()->info(tr('Righe duplicate!'));

        break;

    case 'add_revision':
        // Rimozione flag default_revision dal record principale e dalle revisioni
        $dbo->query('UPDATE co_preventivi SET default_revision=0 WHERE master_revision = '.prepare($preventivo->master_revision));

        // Copia del preventivo
        $new = $preventivo->replicate();

        $stato_preventivo = Stato::where('name', 'Bozza')->first()->id;
        $new->stato()->associate($stato_preventivo);

        $new->save();

        $new->default_revision = 1;
        $new->numero_revision = $new->ultima_revisione + 1;
        $new->descrizione_revision = post('descrizione');
        $new->save();

        $id_record = $new->id;

        // Copia delle righe
        $righe = $preventivo->getRighe();
        foreach ($righe as $riga) {
            $new_riga = $riga->replicate();
            $new_riga->setDocument($new);

            $new_riga->qta_evasa = 0;
            $new_riga->save();
        }

        flash()->info(tr('Aggiunta nuova revisione!'));
        break;

    case 'update_position':
        $order = explode(',', post('order', true));

        foreach ($order as $i => $id_riga) {
            $dbo->query('UPDATE `co_righe_preventivi` SET `order` = '.prepare($i + 1).' WHERE id='.prepare($id_riga));
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

            $articolo = Articolo::build($preventivo, $originale);
            $qta = 1;

            $articolo->um = $originale->um;
            $articolo->qta = 1;
            $articolo->costo_unitario = $originale->prezzo_acquisto;
            $articolo->confermato = setting('Conferma automaticamente le quantità nei preventivi');

            // L'aliquota dell'articolo ha precedenza solo se ha aliquota a 0, altrimenti anagrafica -> articolo -> impostazione
            if ($originale->idiva_vendita) {
                $aliquota_articolo = floatval(Aliquota::find($originale->idiva_vendita)->percentuale);
            }
            $id_iva = ($preventivo->anagrafica->idiva_vendite && (!$originale->idiva_vendita || $aliquota_articolo != 0) ? $preventivo->anagrafica->idiva_vendite : $originale->idiva_vendita) ?: setting('Iva predefinita');
            $id_anagrafica = $preventivo->idanagrafica;
            $prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');

            // CALCOLO PREZZO UNITARIO
            $prezzo_consigliato = getPrezzoConsigliato($id_anagrafica, $dir, $id_articolo);
            if (!$prezzo_consigliato['prezzo_unitario']) {
                $prezzo_consigliato = getPrezzoConsigliato(setting('Azienda predefinita'), $dir, $id_articolo);
            }
            $prezzo_unitario = $prezzo_consigliato['prezzo_unitario'];
            $sconto = $prezzo_consigliato['sconto'];

            $prezzo_unitario = $prezzo_unitario ?: ($prezzi_ivati ? $originale->prezzo_vendita_ivato : $originale->prezzo_vendita);
            $provvigione = $dbo->selectOne('an_anagrafiche', 'provvigione_default', ['idanagrafica' => $preventivo->idagente])['provvigione_default'];

            // Aggiunta sconto combinato se è presente un piano di sconto nell'anagrafica
            $piano_sconto = $dbo->fetchOne('SELECT prc_guadagno FROM an_anagrafiche INNER JOIN mg_piani_sconto ON an_anagrafiche.id_piano_sconto_vendite=mg_piani_sconto.id WHERE idanagrafica='.prepare($id_anagrafica));
            if (!empty($piano_sconto)) {
                $sconto = parseScontoCombinato($piano_sconto['prc_guadagno'].'+'.$sconto);
            }

            $articolo->setPrezzoUnitario($prezzo_unitario, $id_iva);
            $articolo->setSconto($sconto, 'PRC');
            $articolo->setProvvigione($provvigione ?: 0, 'PRC');
            $articolo->save();

            flash()->info(tr('Nuovo articolo aggiunto!'));
        } else {
            $response['error'] = tr('Nessun articolo corrispondente a magazzino');
            echo json_encode($response);
        }

        break;

    case 'update_inline':
        $id_riga = post('riga_id');
        $riga = $riga ?: Riga::find($id_riga);
        $riga = $riga ?: Articolo::find($id_riga);
        $riga = $riga ?: Sconto::find($id_riga);

        if (!empty($riga)) {
            if ($riga->isSconto()) {
                $riga->setScontoUnitario(post('sconto'), $riga->idiva);
            } else {
                $riga->qta = post('qta');
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
            if ($riga['id'] != null) {
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
        $id_anagrafica = $preventivo->idanagrafica;
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
