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
use Models\Plugin;
use Modules\Anagrafiche\Anagrafica;
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Contratti\Components\Articolo;
use Modules\Contratti\Components\Descrizione;
use Modules\Contratti\Components\Riga;
use Modules\Contratti\Components\Sconto;
use Modules\Contratti\Contratto;
use Modules\Contratti\Stato;
use Modules\Iva\Aliquota;
use Plugins\PianificazioneInterventi\Promemoria;

switch (post('op')) {
    case 'add':
        $idanagrafica = post('idanagrafica');
        $anagrafica = Anagrafica::find($idanagrafica);
        $id_segment = post('id_segment');

        // Generazione Contratto
        $contratto = Contratto::build($anagrafica, post('nome'), $id_segment);

        // Salvataggio informazioni sul rinnovo
        $contratto->idstato = post('idstato');
        $contratto->validita = post('validita');
        $contratto->tipo_validita = post('tipo_validita');
        $contratto->data_accettazione = post('data_accettazione') ?: null;
        $contratto->data_conclusione = post('data_conclusione') ?: null;
        $contratto->rinnovabile = post('rinnovabile_add');
        $contratto->rinnovo_automatico = post('rinnovo_automatico_add');
        $contratto->giorni_preavviso_rinnovo = post('giorni_preavviso_rinnovo');
        $contratto->ore_preavviso_rinnovo = post('ore_preavviso_rinnovo');
        $contratto->id_categoria = post('id_categoria_add');
        $contratto->id_sottocategoria = post('id_sottocategoria_add');
        $contratto->save();

        $id_record = $contratto->id;

        if (isAjaxRequest()) {
            echo json_encode(['id' => $id_record, 'text' => 'Contratto '.$contratto->numero.' del '.dateFormat($contratto->data_bozza).' - '.$contratto->nome]);
        }

        flash()->info(tr('Aggiunto contratto numero _NUM_!', [
            '_NUM_' => $contratto['numero'],
        ]));

        break;

    case 'update':
        if (post('id_record') !== null) {
            // Se non specifico un budget me lo vado a ricalcolare
            if ($budget != '') {
                $budget = post('budget');
            } else {
                $q = "SELECT (SELECT SUM(subtotale) FROM co_righe_contratti GROUP BY idcontratto HAVING idcontratto=co_contratti.id) AS 'budget' FROM co_contratti WHERE id=".prepare($id_record);
                $rs = $dbo->fetchArray($q);
                $budget = $rs[0]['budget'];
            }

            $contratto->idanagrafica = post('idanagrafica');
            $contratto->idsede_partenza = post('idsede_partenza');
            $contratto->idsede_destinazione = post('idsede_destinazione');
            $contratto->idstato = post('idstato');
            $contratto->nome = post('nome');
            $contratto->idagente = post('idagente');
            $contratto->idpagamento = post('idpagamento');
            $contratto->numero = post('numero');
            $contratto->budget = $budget;
            $contratto->idreferente = post('idreferente');
            $contratto->condizioni_fornitura = post('condizioni_fornitura');
            $contratto->informazioniaggiuntive = post('informazioniaggiuntive');
            $contratto->id_categoria = post('id_categoria') ?: null;
            $contratto->id_sottocategoria = post('id_sottocategoria') ?: null;

            // Informazioni sulle date del documento
            $contratto->data_bozza = post('data_bozza') ?: null;
            $contratto->data_rifiuto = post('data_rifiuto') ?: null;

            // Dati relativi alla validità del documento
            $contratto->validita = post('validita');
            $contratto->tipo_validita = post('tipo_validita');
            $contratto->data_accettazione = post('data_accettazione') ?: null;
            $contratto->data_conclusione = post('data_conclusione') ?: null;

            $contratto->esclusioni = post('esclusioni');
            $contratto->descrizione = post('descrizione');
            $contratto->id_documento_fe = post('id_documento_fe');
            $contratto->num_item = post('num_item');
            $contratto->codice_cig = post('codice_cig');
            $contratto->codice_cup = post('codice_cup');

            // Salvataggio informazioni sul rinnovo
            $contratto->rinnovabile = post('rinnovabile');
            $contratto->rinnovo_automatico = post('rinnovo_automatico');
            $contratto->giorni_preavviso_rinnovo = post('giorni_preavviso_rinnovo');
            $contratto->ore_preavviso_rinnovo = post('ore_preavviso_rinnovo');

            $contratto->setScontoFinale(post('sconto_finale'), post('tipo_sconto_finale'));
            $contratto->idtipointervento = post('idtipointervento');

            $contratto->save();

            // Verifico impianti presenti
            $matricole_presenti_array = $dbo->select('my_impianti_contratti', 'idimpianto', [], ['idcontratto' => $id_record]);
            $matricole_presenti = [];
            foreach ($matricole_presenti_array as $matricola) {
                $matricole_presenti[] = $matricola['idimpianto'];
            }

            // Verifico nuovi impianti
            $matricole_assegnate_array = post('matricolaimpianto') ?: [];
            $matricole = [];

            foreach ($matricole_assegnate_array as $matricola_assegnata) {
                $matricole[] = $matricola_assegnata;
            }

            // Aggiornamento impianti
            $dbo->sync('my_impianti_contratti', [
                'idcontratto' => $id_record,
            ], [
                'idimpianto' => $matricole,
            ]);

            // Salvataggio costi attività unitari del contratto
            foreach (post('costo_ore') as $id_tipo => $valore) {
                $dbo->update('co_contratti_tipiintervento', [
                    'costo_ore' => post('costo_ore')[$id_tipo],
                    'costo_km' => post('costo_km')[$id_tipo],
                    'costo_dirittochiamata' => post('costo_dirittochiamata')[$id_tipo],
                ], [
                    'idcontratto' => $id_record,
                    'idtipointervento' => $id_tipo,
                ]);
            }

            flash()->info(tr('Contratto modificato correttamente!'));
        }

        break;

        // Duplica contratto
    case 'copy':
        $new = $contratto->replicate(['idcontratto_prev']);
        $new->numero = Contratto::getNextNumero(Carbon::parse($data)->format('Y-m-d'), $contratto->id_segment);

        $stato = Stato::where('name', 'Bozza')->first()->id;
        $new->stato()->associate($stato);
        $new->save();

        $id_record = $new->id;

        $righe = $contratto->getRighe();
        foreach ($righe as $riga) {
            $new_riga = $riga->replicate();
            $new_riga->setDocument($new);

            $new_riga->qta_evasa = 0;
            $new_riga->save();
        }

        flash()->info(tr('Contratto duplicato correttamente!'));

        break;

    case 'manage_articolo':
        if (post('idriga') != null) {
            $articolo = Articolo::find(post('idriga'));
        } else {
            $originale = ArticoloOriginale::find(post('idarticolo'));
            $articolo = Articolo::build($contratto, $originale);
            $articolo->id_dettaglio_fornitore = post('id_dettaglio_fornitore') ?: null;
        }

        $qta = post('qta');

        $articolo->descrizione = post('descrizione');
        $articolo->note = post('note');
        $articolo->um = post('um') ?: null;
        $articolo->data_inizio_competenza = post('data_inizio_competenza') ?: null;
        $articolo->data_fine_competenza = post('data_fine_competenza') ?: null;

        $articolo->costo_unitario = post('costo_unitario') ?: 0;
        $articolo->setPrezzoUnitario(post('prezzo_unitario'), post('idiva'));
        $articolo->setSconto(post('sconto'), post('tipo_sconto'), post('sconto_percentuale_combinato'));
        $articolo->setProvvigione(post('provvigione'), post('tipo_provvigione'));
        $articolo->idconto = post('idconto') ?: null;

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

        break;

    case 'manage_sconto':
        if (post('idriga') != null) {
            $sconto = Sconto::find(post('idriga'));
        } else {
            $sconto = Sconto::build($contratto);
        }

        $sconto->descrizione = post('descrizione');
        $sconto->note = post('note');
        $sconto->setScontoUnitario(post('sconto_unitario'), post('idiva'));

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
            $riga = Riga::build($contratto);
        }

        $qta = post('qta');

        $riga->descrizione = post('descrizione');
        $riga->note = post('note');
        $riga->um = post('um') ?: null;
        $riga->data_inizio_competenza = post('data_inizio_competenza') ?: null;
        $riga->data_fine_competenza = post('data_fine_competenza') ?: null;

        $riga->id_iva = post('idiva');
        $riga->id_tipointervento = post('id_tipointervento') ?: null;

        $riga->costo_unitario = post('costo_unitario') ?: 0;
        $riga->setPrezzoUnitario(post('prezzo_unitario'), post('idiva'));
        $riga->setSconto(post('sconto'), post('tipo_sconto'), post('sconto_percentuale_combinato'));
        $riga->setProvvigione(post('provvigione'), post('tipo_provvigione'));

        $riga->qta = $qta;

        $riga->save();

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
            $riga = Descrizione::build($contratto);
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
            $new_riga->setDocument($contratto);
            $new_riga->qta_evasa = 0;
            $new_riga->save();

            $riga = null;
        }

        flash()->info(tr('Righe duplicate!'));

        break;

        // Recupero dati righe per copia negli appunti
    case 'get_righe_data':
        $id_righe = (array) post('righe');
        $righe_data = [];

        foreach ($id_righe as $id_riga) {
            $riga = Articolo::find($id_riga) ?: Riga::find($id_riga);
            $riga = $riga ?: Descrizione::find($id_riga);
            $riga = $riga ?: Sconto::find($id_riga);

            if ($riga) {
                $riga_array = [
                    'type' => $riga::class,
                    'descrizione' => $riga->descrizione,
                    'qta' => $riga->qta,
                    'um' => $riga->um,
                    'prezzo_unitario' => $riga->prezzo_unitario,
                    'sconto_unitario' => $riga->sconto_unitario,
                    'sconto_percentuale' => $riga->sconto_percentuale,
                    'tipo_sconto' => $riga->tipo_sconto,
                    'idiva' => $riga->idiva,
                    'id_conto' => $riga->id_conto,
                    'note' => $riga->note,
                ];

                if ($riga->isArticolo()) {
                    $riga_array['idarticolo'] = $riga->idarticolo;
                    $riga_array['codice'] = $riga->codice;
                    $riga_array['costo_unitario'] = $riga->costo_unitario;
                }

                $righe_data[] = $riga_array;
            }
        }

        echo json_encode([
            'data' => $righe_data,
        ]);

        break;

        // Incolla righe dagli appunti
    case 'paste_righe':
        $righe_data = json_decode(post('righe_data'), true);

        if (is_array($righe_data)) {
            foreach ($righe_data as $riga_data) {
                $type = $riga_data['type'];
                $class_name = substr((string) $type, strrpos((string) $type, '\\') + 1);

                if ($class_name == 'Articolo' && !empty($riga_data['idarticolo'])) {
                    $articolo_originale = ArticoloOriginale::find($riga_data['idarticolo']);
                    if ($articolo_originale) {
                        $riga = Articolo::build($contratto, $articolo_originale);
                        $riga->costo_unitario = $riga_data['costo_unitario'];
                    } else {
                        $riga = Riga::build($contratto);
                    }
                } elseif ($class_name == 'Descrizione') {
                    $riga = Descrizione::build($contratto);
                } elseif ($class_name == 'Sconto') {
                    $riga = Sconto::build($contratto);
                } else {
                    $riga = Riga::build($contratto);
                }

                $riga->descrizione = $riga_data['descrizione'];
                $riga->qta = $riga_data['qta'];
                $riga->um = $riga_data['um'];

                if (!$riga->isDescrizione()) {
                    $riga->prezzo_unitario = $riga_data['prezzo_unitario'];
                    $riga->sconto_unitario = $riga_data['sconto_unitario'];
                    $riga->sconto_percentuale = $riga_data['sconto_percentuale'];
                    $riga->tipo_sconto = $riga_data['tipo_sconto'];
                    $riga->idiva = $riga_data['idiva'];
                    $riga->id_conto = $riga_data['id_conto'];
                }

                $riga->note = $riga_data['note'];
                $riga->save();
            }

            flash()->info(tr('Righe incollate correttamente!'));

            echo json_encode([
                'status' => 'success',
            ]);
        } else {
            flash()->error(tr('Errore durante l\'incollaggio delle righe'));

            echo json_encode([
                'status' => 'error',
            ]);
        }

        break;

        // Scollegamento intervento da contratto
    case 'unlink':
        if (!empty(get('idcontratto')) && !empty(get('idintervento'))) {
            $idcontratto = get('idcontratto');
            $idintervento = get('idintervento');

            $dbo->delete('co_promemoria', ['idcontratto' => $idcontratto, 'idintervento' => $idintervento]);

            flash()->info(tr('Intervento _NUM_ rimosso!', [
                '_NUM_' => $idintervento,
            ]));
        }
        break;

    case 'update_position':
        $order = explode(',', post('order', true));

        foreach ($order as $i => $id_riga) {
            $dbo->query('UPDATE `co_righe_contratti` SET `order` = '.prepare($i + 1).' WHERE id='.prepare($id_riga));
        }

        break;

        // eliminazione contratto
    case 'delete':
        // Fatture o interventi collegati a questo contratto
        $elementi = $dbo->fetchArray('SELECT
                0 AS `codice`,
                `co_documenti`.`id` AS `id`,
                `co_documenti`.`numero` AS `numero`,
                `co_documenti`.`numero_esterno` AS `numero_esterno`,
                `co_documenti`.`data`,
                `co_tipidocumento_lang`.`title` AS `tipo_documento`,
                `co_tipidocumento`.`dir` AS `dir`
            FROM
                `co_documenti`
                INNER JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento`
                LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento_lang`.`id_record` = `co_tipidocumento`.`id` AND `co_tipidocumento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
                INNER JOIN co_righe_documenti ON `co_righe_documenti`.`iddocumento` = `co_documenti`.`id`
            WHERE
                `co_righe_documenti`.`idcontratto` = '.prepare($id_record).'
        UNION
            SELECT
                `in_interventi`.`codice` AS `codice`,
                `in_interventi`.`id` AS `id`,
                0 AS `numero`,
                0 AS `numero_esterno`,
                `in_interventi`.`data_richiesta` AS `data`,
                0 AS `tipo_documento`,
                0 AS `dir`
            FROM
                `in_interventi`
            WHERE
                `in_interventi`.`id_contratto` = '.prepare($id_record).'
        ORDER BY
                `data` ');

        if (empty($elementi)) {
            try {
                $contratto->delete();

                $dbo->delete('co_promemoria', ['idcontratto' => $id_record]);
                $dbo->delete('co_contratti_tipiintervento', ['idcontratto' => $id_record]);
                $dbo->delete('my_impianti_contratti', ['idcontratto' => $id_record]);

                flash()->info(tr('Contratto eliminato!'));
            } catch (InvalidArgumentException) {
                flash()->error(tr('Sono stati utilizzati alcuni serial number nel documento: impossibile procedere!'));
            }
        }

        break;

    case 'toggle_tipo_attivita':
        // Recupera lo stato attuale
        $current = $dbo->fetchOne('SELECT `is_abilitato` FROM `co_contratti_tipiintervento` WHERE `idcontratto` = '.prepare($id_record).' AND `idtipointervento` = '.prepare(post('idtipointervento')));

        if ($current) {
            // Inverti lo stato
            $nuovo_stato = $current['is_abilitato'] == 1 ? 0 : 1;
            $dbo->update('co_contratti_tipiintervento', [
                'is_abilitato' => $nuovo_stato,
            ], [
                'idcontratto' => $id_record,
                'idtipointervento' => post('idtipointervento'),
            ]);

            echo json_encode([
                'status' => 'success',
                'is_abilitato' => $nuovo_stato == 1,
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => tr('Tipo di attività non trovato'),
            ]);
        }

        break;

    case 'import':
        $rs = $dbo->fetchArray('SELECT * FROM co_contratti_tipiintervento WHERE idcontratto = '.prepare(post('idcontratto')).' AND idtipointervento='.prepare(post('idtipointervento')));

        // Se la riga in_tipiintervento esiste, la aggiorno...
        if (!empty($rs)) {
            $result = $dbo->query('UPDATE `co_contratti_tipiintervento` SET '
                .' `costo_ore`=(SELECT `costo_orario` FROM `in_tipiintervento` WHERE `id`='.prepare(post('idtipointervento')).'), '
                .' `costo_km`=(SELECT `costo_km` FROM `in_tipiintervento` WHERE `id`='.prepare(post('idtipointervento')).'), '
                .' `costo_dirittochiamata`=(SELECT `costo_diritto_chiamata` FROM `in_tipiintervento` WHERE `id`='.prepare(post('idtipointervento')).'), '
                .' `costo_ore_tecnico`=(SELECT `costo_orario_tecnico` FROM `in_tipiintervento` WHERE `id`='.prepare(post('idtipointervento')).'), '
                .' `costo_km_tecnico`=(SELECT `costo_km_tecnico` FROM `in_tipiintervento` WHERE `id`='.prepare(post('idtipointervento')).'), '
                .' `costo_dirittochiamata_tecnico`=(SELECT `costo_diritto_chiamata_tecnico` FROM `in_tipiintervento` WHERE `id`='.prepare(post('idtipointervento')).') '
                .' WHERE `idcontratto`='.prepare(post('idcontratto')).' AND `idtipointervento`='.prepare(post('idtipointervento')));

            if ($result) {
                flash()->info(tr('Informazioni tariffe salvate correttamente!'));
            } else {
                flash()->error(tr("Errore durante l'importazione tariffe!"));
            }
        }

        // ...altrimenti la creo
        else {
            if ($dbo->query('INSERT INTO `co_contratti_tipiintervento`(idcontratto, idtipointervento, costo_ore, costo_km, costo_dirittochiamata, costo_ore_tecnico, costo_km_tecnico, costo_dirittochiamata_tecnico ) VALUES( '.prepare(post('idcontratto')).', '.prepare(post('idtipointervento')).', (SELECT `costo_orario` FROM `in_tipiintervento` WHERE `id`='.prepare(post('idtipointervento')).'), (SELECT `costo_km` FROM `in_tipiintervento` WHERE `id`='.prepare(post('idtipointervento')).'), (SELECT `costo_diritto_chiamata` FROM `in_tipiintervento` WHERE `id`='.prepare(post('idtipointervento')).'),  (SELECT `costo_orario_tecnico` FROM `in_tipiintervento` WHERE `id`='.prepare(post('idtipointervento')).'), (SELECT `costo_km_tecnico` FROM `in_tipiintervento` WHERE `id`='.prepare(post('idtipointervento')).'), (SELECT `costo_diritto_chiamata_tecnico` FROM `in_tipiintervento` WHERE `id`='.prepare(post('idtipointervento')).') )')) {
                flash()->info(tr('Informazioni tariffe salvate correttamente!'));
            } else {
                flash()->error(tr("Errore durante l'importazione tariffe!"));
            }
        }

        break;

        // Aggiunta di un documento in contratto
    case 'add_preventivo':
    case 'add_documento':
        $class = post('class');
        $id_documento = post('id_documento');

        // Individuazione del documento originale
        if (!is_subclass_of($class, Common\Document::class)) {
            return;
        }
        $documento = $class::find($id_documento);

        // Verifica se è un rinnovo di contratto
        $is_renewal = $documento instanceof Contratto && post('is_evasione') == '1';

        // Individuazione sede
        $idsede_partenza = ($documento->direzione == 'entrata') ? $documento->idsede_partenza : $documento->idsede_destinazione;
        $idsede_partenza = $idsede_partenza ?: 0;
        $idsede_destinazione = ($documento->direzione == 'entrata') ? $documento->idsede_destinazione : $documento->idsede_partenza;
        $idsede_destinazione = $idsede_destinazione ?: 0;

        // Creazione del contratto al volo (solo se non è un rinnovo)
        if (post('create_document') == 'on' && !$is_renewal) {
            $contratto = Contratto::build($documento->anagrafica, $documento->nome, post('id_segment'));

            $contratto->idpagamento = $documento->idpagamento ?: setting('Tipo di pagamento predefinito');
            $contratto->idsede_partenza = $idsede_partenza;
            $contratto->idsede_destinazione = $idsede_destinazione;
            $contratto->rinnovabile = setting('Crea contratto rinnovabile di default');
            $contratto->giorni_preavviso_rinnovo = setting('Giorni di preavviso di default');
            $contratto->id_documento_fe = $documento->id_documento_fe;
            $contratto->codice_cup = $documento->codice_cup;
            $contratto->codice_cig = $documento->codice_cig;
            $contratto->num_item = $documento->num_item;

            $contratto->descrizione = $documento->descrizione;
            $contratto->esclusioni = $documento->esclusioni;
            $contratto->idreferente = $documento->idreferente;
            $contratto->idagente = $documento->idagente;

            $contratto->save();

            $id_record = $contratto->id;
        } elseif ($is_renewal) {
            // Creazione del nuovo contratto per il rinnovo
            $contratto = Contratto::build($documento->anagrafica, $documento->nome, post('id_segment'));

            $contratto->idpagamento = $documento->idpagamento ?: setting('Tipo di pagamento predefinito');
            $contratto->idsede_partenza = $idsede_partenza;
            $contratto->idsede_destinazione = $idsede_destinazione;
            $contratto->rinnovabile = setting('Crea contratto rinnovabile di default');
            $contratto->giorni_preavviso_rinnovo = setting('Giorni di preavviso di default');
            $contratto->id_documento_fe = $documento->id_documento_fe;
            $contratto->codice_cup = $documento->codice_cup;
            $contratto->codice_cig = $documento->codice_cig;
            $contratto->num_item = $documento->num_item;
            $contratto->idcontratto_prev = $documento->id;

            $contratto->descrizione = $documento->descrizione;
            $contratto->esclusioni = $documento->esclusioni;
            $contratto->idreferente = $documento->idreferente;
            $contratto->idagente = $documento->idagente;
            $contratto->id_categoria = $documento->id_categoria;
            $contratto->id_sottocategoria = $documento->id_sottocategoria;

            // Calcola le date del nuovo contratto
            $diff = $documento->data_conclusione->diffAsCarbonInterval($documento->data_accettazione);
            $contratto->data_accettazione = $documento->data_conclusione->copy()->addDays(1);
            $contratto->data_conclusione = $contratto->data_accettazione->copy()->add($diff);
            $contratto->data_bozza = Carbon::now();
            
            // Disabilita il calcolo automatico della data di conclusione
            $contratto->validita = null;
            $contratto->tipo_validita = null;

            $stato = Stato::where('name', 'Bozza')->first();
            $contratto->stato()->associate($stato);

            $contratto->save();
            $id_record = $contratto->id;

            // Copia i tipi di intervento dal contratto precedente
            $dbo->delete('co_contratti_tipiintervento', ['idcontratto' => $id_record]);
            $dbo->query('INSERT INTO co_contratti_tipiintervento(idcontratto, idtipointervento, costo_ore, costo_km, costo_dirittochiamata, costo_ore_tecnico, costo_km_tecnico, costo_dirittochiamata_tecnico, is_abilitato) SELECT '.prepare($id_record).', idtipointervento, costo_ore, costo_km, costo_dirittochiamata, costo_ore_tecnico, costo_km_tecnico, costo_dirittochiamata_tecnico, is_abilitato FROM co_contratti_tipiintervento AS z WHERE idcontratto='.prepare($documento->id));

            // Copia gli impianti dal contratto precedente
            $impianti = $dbo->fetchArray('SELECT idimpianto FROM my_impianti_contratti WHERE idcontratto='.prepare($documento->id));
            $dbo->sync('my_impianti_contratti', ['idcontratto' => $id_record], ['idimpianto' => array_column($impianti, 'idimpianto')]);

            // Replicazione dei promemoria
            $promemoria = $dbo->fetchArray('SELECT * FROM co_promemoria WHERE idcontratto='.prepare($documento->id));
            $giorni = $documento->data_conclusione->diffInDays($documento->data_accettazione);
            foreach ($promemoria as $p) {
                $dbo->insert('co_promemoria', [
                    'idcontratto' => $id_record,
                    'data_richiesta' => date('Y-m-d', strtotime($p['data_richiesta'].' +'.$giorni.' day')),
                    'idtipointervento' => $p['idtipointervento'],
                    'richiesta' => $p['richiesta'],
                    'idimpianti' => $p['idimpianti'],
                ]);
                $id_promemoria = $dbo->lastInsertedID();

                $promemoria_obj = Promemoria::find($p['id']);
                $righe = $promemoria_obj->getRighe();
                foreach ($righe as $riga) {
                    $new_riga = $riga->replicate();
                    $new_riga->id_promemoria = $id_promemoria;
                    $new_riga->save();
                }

                // Copia degli allegati
                $allegati = $promemoria_obj->uploads();
                foreach ($allegati as $allegato) {
                    $allegato->copia([
                        'id_module' => $id_module,
                        'id_plugin' => Plugin::where('name', 'Pianificazione interventi')->first()->id,
                        'id_record' => $id_promemoria,
                    ]);
                }
            }

            // Aggiorna lo stato del contratto precedente a concluso
            $dbo->query('UPDATE `co_contratti` SET `rinnovabile`= 0, `idstato`= (SELECT `co_staticontratti`.`id` FROM `co_staticontratti` WHERE `name` = \'Concluso\')  WHERE `co_contratti`.`id` = '.prepare($documento->id));
        }

        if (!empty($documento->sconto_finale)) {
            $contratto->sconto_finale = $documento->sconto_finale;
        } elseif (!empty($documento->sconto_finale_percentuale)) {
            $contratto->sconto_finale_percentuale = $documento->sconto_finale_percentuale;
        }

        $contratto->save();

        // Se è un rinnovo, copia solo le righe selezionate
        if ($is_renewal) {
            $righe_selezionate = $documento->getRighe()->filter(function($riga) {
                return post('evadere')[$riga->id] == 'on' && !empty(post('qta_da_evadere')[$riga->id]);
            });

            foreach ($righe_selezionate as $riga) {
                $qta = post('qta_da_evadere')[$riga->id];
 
                $copia = $riga->replicate();
                $copia->setDocument($contratto);
                $copia->qta_evasa = 0;
                $copia->qta = $qta;
                $copia->original_id = null;
                $copia->original_type = null;
                $copia->original_document_id = null;
                $copia->original_document_type = null;

                // Aggiornamento seriali dalla riga dell'ordine
                if ($copia->isArticolo()) {
                    $serials = is_array(post('serial')[$riga->id]) ? post('serial')[$riga->id] : [];

                    $copia->serials = $serials;
                }

                $copia->save();
            }
        }

        // Gestione delle ore residue selezionate
        $tipi_attivita_selezionati = post('evadere_ore') ?: [];
        $tipi_attivita = post('tipi_attivita') ?: [];
            
        if (!empty($tipi_attivita_selezionati) && !empty($tipi_attivita)) {
            // Prepara la lista dei tipi di attività per la query
            $tipi_attivita_list = [];
            foreach ($tipi_attivita as $id_tipo) {
                $tipi_attivita_list[] = prepare($id_tipo);
            }
            
            // Recupera i dettagli dei tipi di attività selezionati
            $tipi_attivita_dettagli = $dbo->fetchArray('SELECT
                `co_contratti_tipiintervento`.`idtipointervento`,
                `in_tipiintervento_lang`.`title` AS descrizione,
                COALESCE(SUM(`co_righe_contratti`.`qta`), 0) AS ore_totali,
                COALESCE(SUM(`in_interventi_tecnici`.`ore`), 0) AS ore_utilizzate
            FROM `co_contratti_tipiintervento`
            INNER JOIN `in_tipiintervento` ON `co_contratti_tipiintervento`.`idtipointervento` = `in_tipiintervento`.`id`
            LEFT JOIN `in_tipiintervento_lang` ON (`in_tipiintervento`.`id` = `in_tipiintervento_lang`.`id_record` AND `in_tipiintervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
            LEFT JOIN `co_righe_contratti` ON `co_righe_contratti`.`idcontratto` = `co_contratti_tipiintervento`.`idcontratto`
                AND `co_righe_contratti`.`id_tipointervento` = `co_contratti_tipiintervento`.`idtipointervento`
            LEFT JOIN `in_interventi` ON `in_interventi`.`id_contratto` = `co_contratti_tipiintervento`.`idcontratto`
            LEFT JOIN `in_interventi_tecnici` ON `in_interventi_tecnici`.`idintervento` = `in_interventi`.`id`
                AND `in_interventi_tecnici`.`idtipointervento` = `co_contratti_tipiintervento`.`idtipointervento`
            WHERE `co_contratti_tipiintervento`.`idcontratto` = '.prepare($documento->id).'
                AND `co_contratti_tipiintervento`.`idtipointervento` IN ('.implode(',', $tipi_attivita_list).')
            GROUP BY `co_contratti_tipiintervento`.`idtipointervento`, `in_tipiintervento_lang`.`title`');

            foreach ($tipi_attivita_dettagli as $tipo) {
                $idtipointervento = $tipo['idtipointervento'];
                
                // Verifica se questo tipo di attività è stato selezionato
                if (!empty($tipi_attivita_selezionati[$idtipointervento]) && $tipi_attivita_selezionati[$idtipointervento] == 'on') {
                    $qta_ore = post('qta_da_evadere_ore')[$idtipointervento] ?? 0;
                    
                    if ($qta_ore > 0) {
                        // Recupera la riga originale del contratto precedente per questo tipo di attività
                        $riga_originale = $documento->getRighe()
                            ->where('id_tipointervento', $idtipointervento)
                            ->first();
                        
                        if ($riga_originale) {
                            // Crea una copia della riga originale con la quantità selezionata
                            $copia = $riga_originale->replicate();
                            $copia->setDocument($contratto);
                            $copia->qta_evasa = 0;
                            $copia->qta = $qta_ore;
                            $copia->original_id = null;
                            $copia->original_type = null;
                            $copia->original_document_id = null;
                            $copia->original_document_type = null;
                            
                            // Aggiunge alla descrizione il riferimento al contratto precedente
                            $data_contratto = $documento->data_conclusione ? dateFormat($documento->data_conclusione) : dateFormat($documento->data_bozza);
                            $copia->descrizione = $riga_originale->descrizione.' (Residue da attività numero '.$documento->numero.' del '.$data_contratto.')';
                            
                            // Applica uno sconto del 100%
                            $copia->setSconto(100, 'PRC');
                            
                            $copia->save();
                        }
                    }
                }
            }
            
        }

        // Modifica finale dello stato
        if (post('create_document') == 'on') {
            $contratto->idstato = post('id_stato');
            $contratto->save();
        }

        ricalcola_costiagg_ordine($id_record);

        // Messaggio informativo
        if ($is_renewal) {
            $message = tr('Contratto rinnovato correttamente!');
        } else {
            $message = tr('_DOC_ aggiunto!', [
                '_DOC_' => $documento->getReference(),
            ]);
        }
        flash()->info($message);

        break;

    case 'add_articolo':
        $id_articolo = post('id_articolo');
        $barcode = post('barcode');
        $save_inline_barcode = true;
        $dir = 'entrata';

        if (!empty($barcode)) {
            $id_articolo = $dbo->selectOne('mg_articoli_barcode', 'idarticolo', ['barcode' => $barcode])['idarticolo'];
            if (empty($id_articolo)) {
                $id_articolo = $dbo->selectOne('mg_articoli', 'id', ['deleted_at' => null, 'attivo' => 1, 'barcode' => '', 'codice' => $barcode])['id'];
                $save_inline_barcode = false;
            }
        }

        if (!empty($id_articolo)) {
            $permetti_movimenti_sotto_zero = setting('Permetti selezione articoli con quantità minore o uguale a zero in Documenti di Vendita');
            $qta_articolo = $dbo->selectOne('mg_articoli', 'qta', ['id' => $id_articolo])['qta'];

            $originale = ArticoloOriginale::find($id_articolo);

            $articolo = Articolo::build($contratto, $originale);
            $qta = 1;

            $articolo->um = $originale->um;

            if ($save_inline_barcode) {
                $articolo->barcode = $barcode;
            }

            $articolo->qta = 1;
            $articolo->costo_unitario = $originale->prezzo_acquisto;

            // L'aliquota dell'articolo ha precedenza solo se ha aliquota a 0, altrimenti anagrafica -> articolo -> impostazione
            if ($originale->idiva_vendita) {
                $aliquota_articolo = floatval(Aliquota::find($originale->idiva_vendita)->percentuale);
            }
            $id_iva = ($contratto->anagrafica->idiva_vendite && (!$originale->idiva_vendita || $aliquota_articolo != 0) ? $contratto->anagrafica->idiva_vendite : $originale->idiva_vendita) ?: setting('Iva predefinita');
            $id_anagrafica = $contratto->idanagrafica;
            $prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');

            // CALCOLO PREZZO UNITARIO
            $prezzo_consigliato = getPrezzoConsigliato($id_anagrafica, $dir, $id_articolo);
            if (!$prezzo_consigliato['prezzo_unitario']) {
                $prezzo_consigliato = getPrezzoConsigliato(setting('Azienda predefinita'), $dir, $id_articolo);
            }
            $prezzo_unitario = $prezzo_consigliato['prezzo_unitario'];
            $sconto = $prezzo_consigliato['sconto'];

            $prezzo_unitario = $prezzo_unitario ?: ($prezzi_ivati ? $originale->prezzo_vendita_ivato : $originale->prezzo_vendita);
            $provvigione = $dbo->selectOne('an_anagrafiche', 'provvigione_default', ['idanagrafica' => $contratto->idagente])['provvigione_default'];

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
        $riga = Riga::find($id_riga);
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
        $id_anagrafica = $contratto->idanagrafica;
        $prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');
        $numero_totale = 0;
        $id_righe = (array) post('righe');
        $update_prezzo_acquisto = post('update_prezzo_acquisto');
        $update_prezzo_vendita = post('update_prezzo_vendita');
        $update_descrizione = post('update_descrizione');

        foreach ($id_righe as $id_riga) {
            $riga = Articolo::find($id_riga) ?: Riga::find($id_riga);

            // CALCOLO PREZZO UNITARIO
            $prezzo_unitario = 0;
            $sconto = 0;
            if ($riga->isArticolo()) {
                $id_articolo = $riga->idarticolo;

                if ($update_prezzo_vendita) {
                    $prezzo_consigliato = getPrezzoConsigliato($id_anagrafica, $dir, $id_articolo);
                    if (!$prezzo_consigliato['prezzo_unitario']) {
                        $prezzo_consigliato = getPrezzoConsigliato(setting('Azienda predefinita'), $dir, $id_articolo);
                    }
                    $prezzo_unitario = $prezzo_consigliato['prezzo_unitario'];
                    $sconto = $prezzo_consigliato['sconto'];

                    $prezzo_unitario = $prezzo_unitario ?: ($prezzi_ivati ? $riga->articolo->prezzo_vendita_ivato : $riga->articolo->prezzo_vendita);
                    $riga->setPrezzoUnitario($prezzo_unitario, $riga->idiva);
                }

                if ($dir == 'entrata' && $update_prezzo_acquisto) {
                    $riga->costo_unitario = $riga->articolo->prezzo_acquisto;
                }

                if ($update_descrizione) {
                    $riga->descrizione = $riga->articolo->getTranslation('title');
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

    case 'add_serial':
        $articolo = Articolo::find(post('idriga'));

        $serials = (array) post('serial');
        $articolo->serials = $serials;

        break;

    case 'update_iva':
        $id_riga = post('riga_id');
        $id_iva = post('iva_id');

        $riga = Riga::find($id_riga);
        $riga = $riga ?: Articolo::find($id_riga);
        $riga = $riga ?: Sconto::find($id_riga);

        if (!empty($riga)) {
            if ($riga->isSconto()) {
                // Per gli sconti, aggiorna l'IVA mantenendo lo stesso valore di sconto
                $sconto_unitario = $riga->sconto_unitario;
                $riga->setScontoUnitario($sconto_unitario, $id_iva);
            } else {
                // Per articoli e righe, aggiorna l'IVA mantenendo lo stesso prezzo unitario
                $prezzo_unitario = $riga->prezzo_unitario;
                $riga->setPrezzoUnitario($prezzo_unitario, $id_iva);
            }
            $riga->save();

            flash()->info(tr('IVA aggiornata!'));
        }

        break;

    case 'update_iva_multiple':
        $id_righe = (array) post('righe');
        $id_iva = post('iva_id');
        $numero_totale = 0;

        foreach ($id_righe as $id_riga) {
            $riga = Articolo::find($id_riga) ?: Riga::find($id_riga);
            $riga = $riga ?: Sconto::find($id_riga);

            if (!empty($riga)) {
                if ($riga->isSconto()) {
                    // Per gli sconti, aggiorna l'IVA mantenendo lo stesso valore di sconto
                    $sconto_unitario = $riga->sconto_unitario;
                    $riga->setScontoUnitario($sconto_unitario, $id_iva);
                } else {
                    // Per articoli e righe, aggiorna l'IVA mantenendo lo stesso prezzo unitario
                    $prezzo_unitario = $riga->prezzo_unitario;
                    $riga->setPrezzoUnitario($prezzo_unitario, $id_iva);
                }
                $riga->save();
                ++$numero_totale;
            }
        }

        if ($numero_totale > 1) {
            flash()->info(tr('_NUM_ aliquote IVA modificate!', [
                '_NUM_' => $numero_totale,
            ]));
        } elseif ($numero_totale == 1) {
            flash()->info(tr('_NUM_ aliquota IVA modificata!', [
                '_NUM_' => $numero_totale,
            ]));
        } else {
            flash()->warning(tr('Nessuna aliquota IVA modificata!'));
        }

        break;
}
