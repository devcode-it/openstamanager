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
use Models\Module;
use Modules\Anagrafiche\Anagrafica;
use Modules\Anagrafiche\Tipo as TipoAnagrafica;
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Fatture\Components\Articolo;
use Modules\Fatture\Components\Descrizione;
use Modules\Fatture\Components\Riga;
use Modules\Fatture\Components\Sconto;
use Modules\Fatture\Fattura;
use Modules\Fatture\Stato;
use Modules\Fatture\Tipo;
use Modules\Iva\Aliquota;
use Plugins\ExportFE\Interaction;
use Util\XML;

$module = Module::find($id_module);
$op = post('op');
if ($module->getTranslation('title', Models\Locale::getPredefined()->id) == 'Fatture di vendita') {
    $dir = 'entrata';
} else {
    $dir = 'uscita';
}

// Controllo se la fattura è già stata inviata allo SDI
if ($fattura) {
    $stato_fe = $dbo->fetchOne('SELECT codice_stato_fe FROM co_documenti WHERE id = '.$fattura->id);
}

$ops = ['update', 'add_intervento', 'manage_documento_fe', 'manage_riga_fe', 'manage_articolo', 'manage_sconto', 'manage_riga', 'manage_descrizione', 'unlink_intervento', 'delete_riga', 'copy_riga', 'add_serial', 'add_articolo', 'edit-price'];

if ($dir === 'entrata' && in_array($stato_fe['codice_stato_fe'], ['WAIT', 'RC', 'MC', 'QUEUE', 'DT', 'EC01', 'NE']) && Interaction::isEnabled() && in_array($op, $ops)) {
    // Permetto sempre la modifica delle note aggiuntive e/o della data di competenza della fattura di vendita
    if ($op == 'update' && ($fattura->note_aggiuntive != post('note_aggiuntive') || $fattura->data_competenza != post('data_competenza'))) {
        if ($fattura->note_aggiuntive != post('note_aggiuntive')) {
            $fattura->note_aggiuntive = post('note_aggiuntive');
            $fattura->save();
            flash()->info(tr('Note interne modificate correttamente.'));
        }

        if ($fattura->data_competenza != post('data_competenza')) {
            $fattura->data_competenza = post('data_competenza');
            $fattura->save();
            flash()->info(tr('Data competenza modificata correttamente.'));
        }
    } else {
        flash()->warning(tr('La fattura numero _NUM_ è già stata inviata allo SDI, non è possibile effettuare modifiche.', [
            '_NUM_' => $fattura->numero_esterno,
        ]));
    }

    $op = null;
}

switch ($op) {
    case 'add':
        $idanagrafica = post('idanagrafica');
        $data = post('data');
        $idtipodocumento = post('idtipodocumento_add');
        $id_segment = post('id_segment_add');

        if ($dir == 'uscita') {
            $numero_esterno = post('numero_esterno');
        }

        $anagrafica = Anagrafica::find($idanagrafica);
        $tipo = Tipo::find($idtipodocumento);

        $fattura = Fattura::build($anagrafica, $tipo, $data, $id_segment, $numero_esterno);

        $id_record = $fattura->id;

        flash()->info(tr('Fattura aggiunta correttamente!'));

        break;

    case 'update':
        $stato = Stato::find(post('idstatodocumento'));
        $fattura->stato()->associate($stato);
        $data = post('data');

        $tipo = Tipo::find(post('idtipodocumento'));
        $fattura->tipo()->associate($tipo);

        $data_fattura_precedente = $dbo->fetchOne('
            SELECT
                MAX(`data`) AS datamax
            FROM
                `co_documenti`
                INNER JOIN `co_statidocumento` ON `co_statidocumento`.`id` = `co_documenti`.`idstatodocumento`
                LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento`.`id` = `co_statidocumento_lang`.`id_record` AND `co_statidocumento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
                INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id`
                INNER JOIN `zz_segments` ON `zz_segments`.`id` = `co_documenti`.`id_segment`
            WHERE
                `co_statidocumento_lang`.`title` = "Emessa" AND `co_tipidocumento`.`dir` = "entrata" AND `co_documenti`.`id_segment`='.$fattura->id_segment);

        if ((setting('Data emissione fattura automatica') == 1) && ($dir == 'entrata') && ($stato->id == Stato::where('name', 'Emessa')->first()->id) && Carbon::parse($data)->lessThan(Carbon::parse($data_fattura_precedente['datamax'])) && (!empty($data_fattura_precedente['datamax']))) {
            $fattura->data = $data_fattura_precedente['datamax'];
            $fattura->data_competenza = $data_fattura_precedente['datamax'];
            flash()->info(tr('Data di emissione aggiornata, come da impostazione!'));
        } else {
            $fattura->data = post('data');
            $fattura->data_competenza = post('data_competenza');
        }

        if ($dir == 'entrata') {
            $fattura->data_registrazione = post('data');
        } else {
            $fattura->data_registrazione = post('data_registrazione');
        }

        $fattura->numero_esterno = post('numero_esterno');
        $fattura->note = post('note');
        $fattura->note_aggiuntive = post('note_aggiuntive');

        $fattura->idanagrafica = post('idanagrafica');
        $fattura->idagente = post('idagente') ?: '';
        $fattura->idreferente = post('idreferente');
        $fattura->idpagamento = post('idpagamento');
        $fattura->id_banca_azienda = post('id_banca_azienda');
        $fattura->id_banca_controparte = post('id_banca_controparte');
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

        $fattura->peso = post('peso');
        $fattura->volume = post('volume');
        $fattura->peso_manuale = post('peso_manuale');
        $fattura->volume_manuale = post('volume_manuale');

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
        if ($dir == 'entrata') {
            $bollo_automatico = post('bollo_automatico');
            $fattura->addebita_bollo = $bollo_automatico == 1 ? $bollo_automatico : post('addebita_bollo');
            if (empty($bollo_automatico)) {
                $fattura->bollo = post('bollo');
            } else {
                $fattura->bollo = null;
            }
        }

        // Operazioni sulla dichiarazione d'intento
        $dichiarazione_precedente = $fattura->dichiarazione;
        $fattura->id_dichiarazione_intento = post('id_dichiarazione_intento') ?: null;

        // Flag pagamento ritenuta
        $fattura->is_ritenuta_pagata = post('is_ritenuta_pagata') ?: 0;

        $fattura->setScontoFinale(post('sconto_finale'), post('tipo_sconto_finale'));

        $anagrafica = Anagrafica::find($fattura->idanagrafica);
        if ($anagrafica->tipo === 'Privato' && $fattura->is_fattura_conto_terzi) {
            flash()->warning(tr('L\'anagrafica selezionata è del tipo "Privato", correggere la tipologia dalla scheda anagrafica!'));
        } else {
            $results = $fattura->save();
            $message = '';
            flash()->info(tr('Fattura modificata correttamente!'));
        }

        foreach ($results as $numero => $result) {
            foreach ($result as $title => $links) {
                foreach ($links as $link => $errors) {
                    if (empty($title)) {
                        flash()->warning(tr('La fattura elettronica num. _NUM_ potrebbe avere delle irregolarità!', [
                            '_NUM_' => $numero,
                        ]).' '.tr('Controllare i seguenti campi: _LIST_', [
                            '_LIST_' => implode(', ', $errors),
                        ]).'.');
                    } else {
                        $message .= '
                            <p><b>'.$title.' '.$link.'</b></p>
                            <ul>';

                        foreach ($errors as $error) {
                            if (!empty($error)) {
                                $message .= '
                                    <li>'.$error.'</li>';
                            }
                        }

                        $message .= '
                            </ul>';
                    }
                }
            }
        }

        if ($message) {
            // Messaggi informativi sulle problematiche
            $message = tr('La fattura elettronica numero _NUM_ non è stata generata a causa di alcune informazioni mancanti', [
                '_NUM_' => $numero,
            ]).':'.$message;

            flash()->warning($message);
        }

        aggiorna_sedi_movimenti('documenti', $id_record);

        // Controllo sulla presenza di fattura di acquisto con lo stesso numero secondario nello stesso periodo
        $direzione = $fattura->direzione;
        if ($direzione == 'uscita') {
            $count = Fattura::where('numero_esterno', $fattura->numero_esterno)
                ->where('id', '!=', $id_record)
                ->where('idanagrafica', '=', $fattura->anagrafica->id)
                ->where('data', '>=', $_SESSION['period_start'])
                ->where('data', '<=', $_SESSION['period_end'])
                ->whereHas('tipo', function ($query) use ($direzione) {
                    $query->where('dir', '=', $direzione);
                })->count();
            if (!empty($count)) {
                flash()->warning(tr('Esiste già una fattura con lo stesso numero secondario e la stessa anagrafica collegata!'));
            }
        }

        // Controllo sulla presenza di fattura di vendita con lo stesso numero nello stesso periodo
        if ($direzione == 'entrata') {
            $count = Fattura::where('numero_esterno', $fattura->numero_esterno)
                ->where('id', '!=', $id_record)
                ->where('data', '>=', $_SESSION['period_start'])
                ->where('data', '<=', $_SESSION['period_end'])
                ->where('numero_esterno', '!=', '')
                ->whereHas('tipo', function ($query) use ($direzione) {
                    $query->where('dir', '=', $direzione);
                })->count();
            if (!empty($count)) {
                flash()->warning(tr('Esiste già una fattura con lo stesso numero!'));
            }
        }

        break;

        // Ricalcolo scadenze
    case 'ricalcola_scadenze':
        $fattura->registraScadenze(false, true);

        break;

        // Ricalcolo scadenze
    case 'controlla_totali':
        $totale_documento = null;

        try {
            $xml = XML::read($fattura->getXML());

            // Totale basato sul campo ImportoTotaleDocumento
            $dati_generali = $xml['FatturaElettronicaBody']['DatiGenerali']['DatiGeneraliDocumento'];
            $totale_documento = 0;

            $riepiloghi = $xml['FatturaElettronicaBody']['DatiBeniServizi']['DatiRiepilogo'];
            if (!empty($riepiloghi) && !isset($riepiloghi[0])) {
                $riepiloghi = [$riepiloghi];
            }

            foreach ($riepiloghi as $riepilogo) {
                $totale_documento = sum([$totale_documento, $riepilogo['ImponibileImporto'], $riepilogo['Imposta']]);
            }

            $totale_documento = abs($totale_documento);
            $totale_documento = abs($dati_generali['ImportoTotaleDocumento']) ?: $totale_documento;
        } catch (Exception) {
        }

        echo json_encode([
            'stored' => round($totale_documento, 2),
            'calculated' => round($fattura->totale, 2),
        ]);

        break;

        // Elenco fatture in stato Bozza per il cliente
    case 'fatture_bozza':
        $id_anagrafica = post('id_anagrafica');
        $stato = Stato::where('name', 'Bozza')->first();

        $fatture = Fattura::vendita()
            ->where('idanagrafica', $id_anagrafica)
            ->where('idstatodocumento', $stato->id)
            ->get();

        $results = [];
        foreach ($fatture as $result) {
            $results[] = Modules::link('Fatture di vendita', $result->id, reference($result));
        }

        echo json_encode($results);

        break;

        // Elenco fatture Scadute per il cliente
    case 'fatture_scadute':
        $id_anagrafica = post('id_anagrafica');
        $stato1 = Stato::where('name', 'Emessa')->first();
        $stato2 = Stato::where('name', 'Parzialmente pagato')->first();

        $fatture = Fattura::vendita()
            ->select('*', 'co_documenti.id AS id', 'co_documenti.data AS data')
            ->where('co_documenti.idanagrafica', '=', $id_anagrafica)
            ->whereIn('idstatodocumento', [$stato1->id, $stato2->id])
            ->join('co_scadenziario', 'co_documenti.id', '=', 'co_scadenziario.iddocumento')
            ->join('co_tipidocumento', 'co_tipidocumento.id', '=', 'co_documenti.idtipodocumento')
            ->whereRaw('co_scadenziario.da_pagare > co_scadenziario.pagato')
            ->whereRaw('co_scadenziario.scadenza < NOW()')
            ->groupBy('co_scadenziario.iddocumento')
            ->get();

        $results = [];
        foreach ($fatture as $result) {
            $results[] = Modules::link('Fatture di vendita', $result->id, reference($result));
        }

        echo json_encode($results);

        break;

        // eliminazione documento
    case 'delete':
        try {
            $fattura->delete();

            $dbo->query('DELETE FROM co_scadenziario WHERE iddocumento='.prepare($id_record));
            $dbo->query('DELETE FROM co_movimenti WHERE iddocumento='.prepare($id_record));

            // Azzeramento collegamento della rata contrattuale alla pianificazione
            $dbo->query('UPDATE co_fatturazione_contratti SET iddocumento=0 WHERE iddocumento='.prepare($id_record));

            flash()->info(tr('Fattura eliminata!'));
        } catch (InvalidArgumentException) {
            flash()->error(tr('Sono stati utilizzati alcuni serial number nel documento: impossibile procedere!'));
        }

        break;

        // Duplicazione fattura
    case 'copy':
        $new = $fattura->replicate();
        $new->id_autofattura = null;
        $new->save();

        $id_record = $new->id;

        $righe = $fattura->getRighe()->where('id', '!=', $fattura->id_riga_bollo);
        foreach ($righe as $riga) {
            $new_riga = $riga->replicate();
            $new_riga->setDocument($new);

            // Rimozione riferimenti (deprecati)
            $new_riga->idpreventivo = 0;
            $new_riga->idcontratto = 0;
            $new_riga->idintervento = 0;
            $new_riga->idddt = 0;
            $new_riga->idordine = 0;

            if ($new_riga->isArticolo()) {
                $new_riga->movimenta($new_riga->qta);
            }

            $new_riga->save();
        }

        // Forzo il salvataggio della fattura per far scattare gli automatismi legati alle righe
        $new->save();

        flash()->info(tr('Fattura duplicata correttamente!'));

        break;

    case 'reopen':
        if (!empty($id_record)) {
            $stato = Stato::where('name', 'Bozza')->first()->id;
            $fattura->stato()->associate($stato);
            $fattura->save();
            $stato = Stato::where('name', 'Emessa')->first()->id;
            $fattura->stato()->associate($stato);
            $fattura->save();
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
            $articolo->id_dettaglio_fornitore = post('id_dettaglio_fornitore') ?: null;
        }

        $qta = post('qta');

        $articolo->descrizione = post('descrizione');
        $articolo->note = post('note');
        $articolo->um = post('um') ?: null;

        $articolo->id_iva = post('idiva');
        $articolo->idconto = post('idconto');

        $articolo->calcolo_ritenuta_acconto = post('calcolo_ritenuta_acconto') ?: null;
        $articolo->id_ritenuta_acconto = post('id_ritenuta_acconto') ?: null;
        $articolo->ritenuta_contributi = boolval(post('ritenuta_contributi'));
        $articolo->id_rivalsa_inps = post('id_rivalsa_inps') ?: null;

        $articolo->costo_unitario = post('costo_unitario') ?: 0;
        $articolo->setPrezzoUnitario(post('prezzo_unitario'), post('idiva'));
        $articolo->setSconto(post('sconto'), post('tipo_sconto'));
        if ($dir == 'entrata') {
            $articolo->setProvvigione(post('provvigione'), post('tipo_provvigione'));
        }

        try {
            $articolo->qta = $qta;
        } catch (UnexpectedValueException) {
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

        $sconto->idconto = post('idconto');

        $sconto->calcolo_ritenuta_acconto = post('calcolo_ritenuta_acconto') ?: null;
        $sconto->id_ritenuta_acconto = post('id_ritenuta_acconto') ?: null;
        $sconto->ritenuta_contributi = boolval(post('ritenuta_contributi'));
        $sconto->id_rivalsa_inps = post('id_rivalsa_inps') ?: null;

        $sconto->descrizione = post('descrizione');
        $sconto->note = post('note');
        $sconto->setScontoUnitario(post('sconto_unitario'), post('idiva'));
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

        $riga->descrizione = post('descrizione');
        $riga->note = post('note');
        $riga->um = post('um') ?: null;

        $riga->id_iva = post('idiva');
        $riga->idconto = post('idconto');

        $riga->calcolo_ritenuta_acconto = post('calcolo_ritenuta_acconto') ?: null;
        $riga->id_ritenuta_acconto = post('id_ritenuta_acconto') ?: null;
        $riga->ritenuta_contributi = boolval(post('ritenuta_contributi'));
        $riga->id_rivalsa_inps = post('id_rivalsa_inps') ?: null;

        $riga->costo_unitario = post('costo_unitario') ?: 0;
        $riga->setPrezzoUnitario(post('prezzo_unitario'), post('idiva'));
        $riga->setSconto(post('sconto'), post('tipo_sconto'));
        if ($dir == 'entrata') {
            $riga->setProvvigione(post('provvigione'), post('tipo_provvigione'));
        }

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
        $riga->note = post('note');

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
            $type = post('type');
            $riga = $fattura->getRiga($type, $id_riga);

            if (!empty($riga)) {
                try {
                    $riga->delete();

                    flash()->info(tr('Intervento _NUM_ rimosso!', [
                        '_NUM_' => $idintervento,
                    ]));
                } catch (InvalidArgumentException) {
                    flash()->error(tr('Errore durante l\'eliminazione della riga!'));
                }
            }
        }

        break;

        // Scollegamento riga generica da documento
    case 'delete_riga':
        $id_righe = (array) post('righe');

        foreach ($id_righe as $id_riga) {
            $riga = Articolo::find($id_riga) ?: Riga::find($id_riga);
            $riga = $riga ?: Descrizione::find($id_riga);
            $riga = $riga ?: Sconto::find($id_riga);

            try {
                $riga->delete();

                // Ricalcolo inps, ritenuta e bollo
                ricalcola_costiagg_fattura($id_record);
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
            $riga = $riga ?: Descrizione::find($id_riga);
            $riga = $riga ?: Sconto::find($id_riga);

            $new_riga = $riga->replicate();
            $new_riga->setDocument($fattura);
            $new_riga->qta_evasa = 0;

            if ($new_riga->isArticolo()) {
                $new_riga->movimenta($new_riga->qta);
            }

            $new_riga->save();

            $riga = null;
        }

        // Ricalcolo inps, ritenuta e bollo
        ricalcola_costiagg_fattura($id_record);

        flash()->info(tr('Righe duplicate!'));

        break;

    case 'add_serial':
        $articolo = Articolo::find(post('idriga'));

        $serials = (array) post('serial');
        $articolo->serials = $serials;

        break;

    case 'update_position':
        $order = explode(',', post('order', true));

        foreach ($order as $i => $id_riga) {
            $dbo->query('UPDATE `co_righe_documenti` SET `order` = '.prepare($i + 1).' WHERE id='.prepare($id_riga));
        }

        break;

        // Aggiunta di un documento esterno
    case 'add_documento':
        $class = post('class');
        $id_documento = post('id_documento');

        // Individuazione del documento originale
        if (!is_subclass_of($class, Common\Document::class)) {
            return;
        }
        $documento = $class::find($id_documento);

        // Individuazione sede
        $idsede_partenza = ($documento->direzione == 'entrata') ? $documento->idsede_partenza : $documento->idsede_destinazione;
        $idsede_partenza = $idsede_partenza ?: 0;
        $idsede_destinazione = ($documento->direzione == 'entrata') ? $documento->idsede_destinazione : $documento->idsede_partenza;
        $idsede_destinazione = $idsede_destinazione ?: 0;

        // Creazione della fattura al volo
        if (post('create_document') == 'on') {
            $tipo = Tipo::find(post('idtipodocumento'));

            $fattura = Fattura::build($documento->anagrafica, $tipo, post('data'), post('id_segment'));

            if (!empty($documento->idpagamento)) {
                $fattura->idpagamento = $documento->idpagamento;
            } else {
                $fattura->idpagamento = setting('Tipo di pagamento predefinito');
            }

            $fattura->idsede_partenza = $idsede_partenza;
            $fattura->idsede_destinazione = $idsede_destinazione;
            $fattura->id_ritenuta_contributi = post('id_ritenuta_contributi') ?: null;
            $fattura->idreferente = $documento->idreferente;
            $fattura->idagente = $documento->idagente ?: '';

            $fattura->save();

            $id_record = $fattura->id;
        }

        if (!empty($documento->sconto_finale)) {
            $fattura->sconto_finale = $documento->sconto_finale;
        } elseif (!empty($documento->sconto_finale_percentuale)) {
            $fattura->sconto_finale_percentuale = $documento->sconto_finale_percentuale;
        }

        $fattura->save();

        $calcolo_ritenuta_acconto = post('calcolo_ritenuta_acconto') ?: null;
        $id_ritenuta_acconto = post('id_ritenuta_acconto') ?: null;
        $ritenuta_contributi = boolval(post('ritenuta_contributi'));
        $id_rivalsa_inps = post('id_rivalsa_inps') ?: null;
        $id_conto = post('id_conto');

        if ($class == Modules\Interventi\Intervento::class) {
            $riga = Descrizione::build($fattura);
            $riga->descrizione = post('descrizione_intervento');
            $riga->idintervento = $documento->id;
            $riga->save();

            $copia_descrizione = post('copia_descrizione');
            if (!empty($copia_descrizione) && !empty($documento->descrizione)) {
                $riga = Descrizione::build($fattura);
                $riga->descrizione = $documento->descrizione;
                $riga->idintervento = $documento->id;
                $riga->save();
            }

            if (post('importa_sessioni')) {
                $id_iva = $anagrafica->idiva_vendite ?: setting('Iva predefinita');
                aggiungi_sessioni_in_fattura($documento->id, $fattura->id, $id_iva, $id_conto, $id_rivalsa_inps, $id_ritenuta_acconto, $calcolo_ritenuta_acconto);
            }
        }

        $righe = $documento->getRighe();
        foreach ($righe as $riga) {
            if (post('evadere')[$riga->id] == 'on') {
                $qta = post('qta_da_evadere')[$riga->id];
                $articolo = ArticoloOriginale::find($riga->idarticolo);

                $copia = $riga->copiaIn($fattura, $qta);

                $copia->id_conto = ($documento->direzione == 'entrata' ? ($articolo->idconto_vendita ?: $id_conto) : ($articolo->idconto_acquisto ?: $id_conto));
                $copia->calcolo_ritenuta_acconto = $calcolo_ritenuta_acconto;
                $copia->id_ritenuta_acconto = $id_ritenuta_acconto;
                $copia->id_rivalsa_inps = $id_rivalsa_inps;
                $copia->ritenuta_contributi = $ritenuta_contributi;

                // Aggiornamento seriali dalla riga dell'ordine
                if ($copia->isArticolo()) {
                    $serials = is_array(post('serial')[$riga->id]) ? post('serial')[$riga->id] : [];

                    $copia->serials = $serials;
                }

                $copia->save();
            }
        }

        // Modifica finale dello stato
        if (post('create_document') == 'on') {
            $fattura->idstatodocumento = post('id_stato');
            $fattura->save();
        }

        ricalcola_costiagg_fattura($id_record);

        // Messaggio informativo
        $message = tr('_DOC_ aggiunto!', [
            '_DOC_' => $fattura->getReference(),
        ]);
        flash()->info($message);

        break;

        // Nota di credito
    case 'nota_credito':
        $id_documento = post('id_documento');
        $fattura = Fattura::find($id_documento);

        $id_segment = post('id_segment');
        $data = post('data');

        $anagrafica = $fattura->anagrafica;
        $id_tipo = database()->fetchOne('SELECT `co_tipidocumento`.`id` FROM `co_tipidocumento` LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento_lang`.`id_record` = `co_tipidocumento`.`id` AND `co_tipidocumento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `title` = "Nota di credito" AND `dir` = "entrata"')['id'];
        $tipo = Tipo::find($id_tipo);
        $nota = Fattura::build($anagrafica, $tipo, $data, $id_segment);
        $nota->ref_documento = $fattura->id;
        $nota->idconto = $fattura->idconto;
        $nota->idpagamento = $fattura->idpagamento;
        $nota->id_banca_azienda = $fattura->id_banca_azienda;
        $nota->id_banca_controparte = $fattura->id_banca_controparte;
        $nota->idsede_partenza = $fattura->idsede_partenza;
        $nota->idsede_destinazione = $fattura->idsede_destinazione;
        $nota->split_payment = $fattura->split_payment;
        $nota->save();

        $righe = $fattura->getRighe();
        foreach ($righe as $riga) {
            if (post('evadere')[$riga->id] == 'on' and !empty(post('qta_da_evadere')[$riga->id])) {
                $qta = post('qta_da_evadere')[$riga->id];

                $copia = $riga->copiaIn($nota, $qta);
                $copia->ref_riga_documento = $riga->id;

                // Aggiornamento seriali dalla riga della fattura
                if ($copia->isArticolo()) {
                    $serials = is_array(post('serial')[$riga->id]) ? post('serial')[$riga->id] : [];
                    $copia->serials = $serials;
                }

                $copia->save();
            }
        }

        $stato = Stato::find(post('id_stato'));
        $nota->stato()->associate($stato);
        $nota->save();

        $id_record = $nota->id;
        aggiorna_sedi_movimenti('documenti', $id_record);

        break;

        // Autofattura
    case 'autofattura':
        $fattura = Fattura::find($id_record);

        $imponibile = 0;
        $sconto = 0;

        $data = date('Y-m-d');
        $anagrafica = $fattura->anagrafica;

        $id_segment = post('id_segment_autofattura');
        $tipo = Tipo::find(post('idtipodocumento_autofattura'));
        $iva = Aliquota::find(setting('Iva predefinita'));

        $imponibile = $database->table('co_righe_documenti')
            ->join('co_iva', 'co_iva.id', '=', 'co_righe_documenti.idiva')
            ->where('co_righe_documenti.iddocumento', $fattura->id)
            ->whereIn('co_iva.codice_natura_fe', ['N2', 'N2.1', 'N2.2', 'N3', 'N3.1', 'N3.2', 'N3.3', 'N3.4', 'N3.5', 'N3.6', 'N6', 'N6.1', 'N6.2', 'N6.3', 'N6.4', 'N6.5', 'N6.6', 'N6.7', 'N6.8', 'N6.9'])
            ->sum('subtotale');

        $sconto = $database->table('co_righe_documenti')
            ->join('co_iva', 'co_iva.id', '=', 'co_righe_documenti.idiva')
            ->where('co_righe_documenti.iddocumento', $fattura->id)
            ->whereIn('co_iva.codice_natura_fe', ['N2', 'N2.1', 'N2.2', 'N3', 'N3.1', 'N3.2', 'N3.3', 'N3.4', 'N3.5', 'N3.6', 'N6', 'N6.1', 'N6.2', 'N6.3', 'N6.4', 'N6.5', 'N6.6', 'N6.7', 'N6.8', 'N6.9'])
            ->sum('sconto');

        $totale_imponibile = setting('Utilizza prezzi di vendita comprensivi di IVA') ? ($imponibile - $sconto) + (($imponibile - $sconto) * $iva->percentuale / 100) : ($imponibile - $sconto);
        $totale_imponibile = $fattura->tipo->reversed == 1 ? -$totale_imponibile : $totale_imponibile;

        $autofattura = Fattura::build($anagrafica, $tipo, $data, $id_segment);
        $autofattura->idconto = $fattura->idconto;
        $autofattura->idpagamento = $fattura->idpagamento;
        $autofattura->is_fattura_conto_terzi = 1;
        $autofattura->ref_documento = $fattura->id;
        $autofattura->save();

        $riga = Riga::build($autofattura);
        $riga->descrizione = $tipo->getTranslation('title');
        $riga->id_iva = $iva->id;
        $riga->idconto = setting('Conto per autofattura') ?: setting('Conto predefinito fatture di vendita');
        $riga->setPrezzoUnitario($totale_imponibile, $iva->id);
        $riga->qta = 1;
        $riga->save();

        // Aggiunta tipologia cliente se necessario
        if (!$anagrafica->isTipo('Cliente')) {
            $tipo_cliente = TipoAnagrafica::where('name', 'Cliente')->first()->id;
            $tipi = $anagrafica->tipi->pluck('id')->toArray();
            $tipi[] = $tipo_cliente;

            $anagrafica->tipologie = $tipi;
            $anagrafica->save();
        }

        $fattura->id_autofattura = $autofattura->id;
        $fattura->save();

        $id_module = Module::where('name', 'Fatture di vendita')->first()->id;
        $id_record = $autofattura->id;

        break;

    case 'transform':
        $fattura->id_segment = post('id_segment');
        $fattura->data = post('data');
        $fattura->data_registrazione = post('data');
        $fattura->data_competenza = post('data');
        $fattura->save();

        break;

    case 'controlla_serial':
        if (post('is_rientrabile')) {
            // Controllo che i serial entrati e usciti siano uguali in modo da poterli registrare nuovamente.
            $serial_uscita = $dbo->fetchOne('SELECT COUNT(id) AS `tot` FROM mg_prodotti WHERE serial='.prepare(post('serial')).' AND dir="uscita" AND id_articolo='.prepare(post('id_articolo')))['tot'];
            $serial_entrata = $dbo->fetchOne('SELECT COUNT(id) AS `tot` FROM mg_prodotti WHERE serial='.prepare(post('serial')).' AND dir="entrata" AND id_articolo='.prepare(post('id_articolo')))['tot'];
            $has_serial = $serial_entrata != $serial_uscita;
        } else {
            $has_serial = $dbo->fetchOne('SELECT id FROM mg_prodotti WHERE serial='.prepare(post('serial')).' AND dir="uscita" AND id_articolo='.prepare(post('id_articolo')).' AND (id_riga_documento IS NOT NULL OR id_riga_ordine IS NOT NULL OR id_riga_ddt IS NOT NULL)')['id'];
        }

        echo json_encode($has_serial);

        break;

    case 'add_articolo':
        $id_articolo = post('id_articolo');
        $barcode = post('barcode');

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
                $articolo = Articolo::build($fattura, $originale);
                $qta = 1;

                $articolo->um = $originale->um;
                $articolo->qta = 1;
                $articolo->costo_unitario = $originale->prezzo_acquisto;

                $id_conto = ($dir == 'entrata') ? setting('Conto predefinito fatture di vendita') : setting('Conto predefinito fatture di acquisto');
                if ($dir == 'entrata' && !empty($originale->idconto_vendita)) {
                    $id_conto = $originale->idconto_vendita;
                } elseif ($dir == 'uscita' && !empty($originale->idconto_acquisto)) {
                    $id_conto = $originale->idconto_acquisto;
                }
                $articolo->idconto = $id_conto;

                if ($dir == 'entrata') {
                    // L'aliquota dell'articolo ha precedenza solo se ha aliquota a 0, altrimenti anagrafica -> articolo -> impostazione
                    if ($originale->idiva_vendita) {
                        $aliquota_articolo = floatval(Aliquota::find($originale->idiva_vendita)->percentuale);
                    }
                    $id_iva = ($fattura->anagrafica->idiva_vendite && (!$originale->idiva_vendita || $aliquota_articolo != 0) ? $fattura->anagrafica->idiva_vendite : $originale->idiva_vendita) ?: setting('Iva predefinita');
                } else {
                    $id_iva = ($fattura->anagrafica->idiva_acquisti ?: ($originale->idiva_vendita ?: setting('Iva predefinita')));
                }
                $id_anagrafica = $fattura->idanagrafica;
                $prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');

                // CALCOLO PREZZO UNITARIO
                $prezzo_consigliato = getPrezzoConsigliato($id_anagrafica, $dir, $id_articolo);
                if (!$prezzo_consigliato['prezzo_unitario']) {
                    $prezzo_consigliato = getPrezzoConsigliato(setting('Azienda predefinita'), $dir, $id_articolo);
                }
                $prezzo_unitario = $prezzo_consigliato['prezzo_unitario'];
                $sconto = $prezzo_consigliato['sconto'];

                if ($dir == 'entrata') {
                    $prezzo_unitario = $prezzo_unitario ?: ($prezzi_ivati ? $originale->prezzo_vendita_ivato : $originale->prezzo_vendita);
                } else {
                    $prezzo_unitario = $prezzo_unitario ?: $originale->prezzo_acquisto;
                }

                // Aggiunta sconto combinato se è presente un piano di sconto nell'anagrafica
                $join = ($dir == 'entrata' ? 'id_piano_sconto_vendite' : 'id_piano_sconto_acquisti');
                $piano_sconto = $dbo->fetchOne('SELECT prc_guadagno FROM an_anagrafiche INNER JOIN mg_piani_sconto ON an_anagrafiche.'.$join.'=mg_piani_sconto.id WHERE idanagrafica='.prepare($id_anagrafica));
                if (!empty($piano_sconto)) {
                    $sconto = parseScontoCombinato($piano_sconto['prc_guadagno'].'+'.$sconto);
                }

                $provvigione = $dbo->selectOne('an_anagrafiche', 'provvigione_default', ['idanagrafica' => $fattura->idagente])['provvigione_default'];

                $articolo->id_rivalsa_inps = setting('Cassa previdenziale predefinita') ?: '';
                $articolo->id_ritenuta_acconto = setting('Ritenuta d\'acconto predefinita') ?: '';
                $articolo->setPrezzoUnitario($prezzo_unitario, $id_iva);
                $articolo->setSconto($sconto, 'PRC');
                $articolo->setProvvigione($provvigione ?: 0, 'PRC');
                $articolo->save();

                flash()->info(tr('Nuovo articolo aggiunto!'));
            }

            // Ricalcolo inps, ritenuta e bollo
            ricalcola_costiagg_fattura($id_record);
        } else {
            $response['error'] = tr('Nessun articolo corrispondente a magazzino');
            echo json_encode($response);
        }
        break;

        // Controllo se impostare anagrafica azienda in base a tipologia documento
    case 'check_tipodocumento':
        $idtipodocumento = post('idtipodocumento');
        $tipologie = Tipo::wherein('codice_tipo_documento_fe', ['TD21', 'TD27'])->where('dir', 'entrata')->get()->pluck('id')->toArray();
        $azienda = Anagrafica::find(setting('Azienda predefinita'));

        $result = false;
        if (in_array($idtipodocumento, $tipologie)) {
            // Aggiunta tipologia cliente se necessario
            if (!$azienda->isTipo('Cliente')) {
                $tipo_cliente = TipoAnagrafica::where('name', 'Cliente')->first()->id;
                $tipi = $azienda->tipi->pluck('id')->toArray();
                $tipi[] = $tipo_cliente;

                $azienda->tipologie = $tipi;
                $azienda->save();
            }
            $result = [
                'id' => $azienda->id,
                'ragione_sociale' => $azienda->ragione_sociale,
            ];
        }

        echo json_encode($result);

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

        // Ricalcolo inps, ritenuta e bollo
        ricalcola_costiagg_fattura($id_record);

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
        $id_anagrafica = $fattura->idanagrafica;
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

                if ($dir == 'entrata') {
                    $prezzo_unitario = $prezzo_unitario ?: ($prezzi_ivati ? $riga->articolo->prezzo_vendita_ivato : $riga->articolo->prezzo_vendita);
                    $riga->costo_unitario = $riga->articolo->prezzo_acquisto;
                } else {
                    $prezzo_unitario = $prezzo_unitario ?: $riga->articolo->prezzo_acquisto;
                }
                $riga->setPrezzoUnitario($prezzo_unitario, $riga->idiva);
            }

            // Aggiunta sconto combinato se è presente un piano di sconto nell'anagrafica
            $join = ($dir == 'entrata' ? 'id_piano_sconto_vendite' : 'id_piano_sconto_acquisti');
            $piano_sconto = $dbo->fetchOne('SELECT prc_guadagno FROM an_anagrafiche INNER JOIN mg_piani_sconto ON an_anagrafiche.'.$join.'=mg_piani_sconto.id WHERE idanagrafica='.prepare($id_anagrafica));
            if (!empty($piano_sconto)) {
                $sconto = parseScontoCombinato($piano_sconto['prc_guadagno'].'+'.$sconto);
            }

            $riga->setSconto($sconto, 'PRC');
            $riga->save();
            ++$numero_totale;
        }

        // Ricalcolo inps, ritenuta e bollo
        ricalcola_costiagg_fattura($id_record);

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

        // Ricalcolo inps, ritenuta e bollo
        ricalcola_costiagg_fattura($id_record);

        break;

    case 'cambia_stato':
        $stato = Stato::where('name', 'Non valida')->first();
        $fattura->stato()->associate($stato);
        $fattura->save();

        break;
}

// Nota di debito
if (get('op') == 'nota_addebito') {
    $rs_segment = $dbo->fetchArray('SELECT `zz_segments`.* FROM `zz_segments` LEFT JOIN `zz_segments_lang` ON (`zz_segments`.`id` = `zz_segments_lang`.`id_record` AND `zz_segments_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).") WHERE `predefined_addebito`='1'");
    if (!empty($rs_segment)) {
        $id_segment = $rs_segment[0]['id'];
    } else {
        $id_segment = $record['id_segment'];
    }

    $anagrafica = $fattura->anagrafica;
    $tipo = Tipo::where('name', 'Nota di debito')->where('dir', 'entrata')->first();
    $data = $fattura->data;

    $nota = Fattura::build($anagrafica, $tipo, $data, $id_segment);
    $nota->ref_documento = $fattura->id;
    $nota->idconto = $fattura->idconto;
    $nota->idpagamento = $fattura->idpagamento;
    $nota->id_banca_azienda = $fattura->id_banca_azienda;
    $nota->id_banca_controparte = $fattura->id_banca_controparte;
    $nota->idsede_partenza = $fattura->idsede_partenza;
    $nota->idsede_destinazione = $fattura->idsede_destinazione;
    $nota->save();

    $id_record = $nota->id;
    aggiorna_sedi_movimenti('documenti', $id_record);
}
