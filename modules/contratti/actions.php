<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

use Modules\Anagrafiche\Anagrafica;
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Contratti\Components\Articolo;
use Modules\Contratti\Components\Descrizione;
use Modules\Contratti\Components\Riga;
use Modules\Contratti\Components\Sconto;
use Modules\Contratti\Contratto;
use Plugins\PianificazioneInterventi\Promemoria;

switch (post('op')) {
    case 'add':
        $idanagrafica = post('idanagrafica');
        $nome = post('nome');

        $anagrafica = Anagrafica::find($idanagrafica);

        $contratto = Contratto::build($anagrafica, $nome);
        $id_record = $contratto->id;

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
            $contratto->idsede = post('idsede');
            $contratto->idstato = post('idstato');
            $contratto->nome = post('nome');
            $contratto->idagente = post('idagente');
            $contratto->idpagamento = post('idpagamento');
            $contratto->numero = post('numero');
            $contratto->budget = $budget;
            $contratto->idreferente = post('idreferente');

            // Informazioni sulle date del documento
            $contratto->data_bozza = post('data_bozza');
            $contratto->data_rifiuto = post('data_rifiuto');

            // Dati relativi alla validità del documento
            $contratto->validita = post('validita');
            $contratto->tipo_validita = post('tipo_validita');
            $contratto->data_accettazione = post('data_accettazione');
            $contratto->data_conclusione = post('data_conclusione');

            $contratto->rinnovabile = post('rinnovabile');
            $contratto->giorni_preavviso_rinnovo = post('giorni_preavviso_rinnovo');
            $contratto->ore_preavviso_rinnovo = post('ore_preavviso_rinnovo');
            $contratto->esclusioni = post('esclusioni');
            $contratto->descrizione = post('descrizione');
            $contratto->id_documento_fe = post('id_documento_fe');
            $contratto->num_item = post('num_item');
            $contratto->codice_cig = post('codice_cig');
            $contratto->codice_cup = post('codice_cup');

            $contratto->save();

            $dbo->query('DELETE FROM my_impianti_contratti WHERE idcontratto='.prepare($id_record));
            foreach ((array) post('matricolaimpianto') as $matricolaimpianto) {
                $dbo->query('INSERT INTO my_impianti_contratti(idcontratto,idimpianto) VALUES('.prepare($id_record).', '.prepare($matricolaimpianto).')');
            }

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
        $new = $contratto->replicate();
        $new->numero = Contratto::getNextNumero();
        $new->stato = 'Bozza';
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
            $articolo = Articolo::build($contratto, $originale);
            $articolo->id_dettaglio_fornitore = $id_dettaglio_fornitore ?: null;

            $articolo->setPrezzoUnitario($prezzo_unitario, $id_iva);
            if ($dir == 'entrata') {
                $articolo->costo_unitario = $originale->prezzo_acquisto;
            }
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
            $articolo = Articolo::build($contratto, $originale);
            $articolo->id_dettaglio_fornitore = post('id_dettaglio_fornitore') ?: null;
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

        break;

    case 'manage_sconto':
        if (post('idriga') != null) {
            $sconto = Sconto::find(post('idriga'));
        } else {
            $sconto = Sconto::build($contratto);
        }

        $sconto->descrizione = post('descrizione');
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
        $riga->um = post('um') ?: null;

        $riga->id_iva = post('idiva');

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

    case 'manage_descrizione':
        if (post('idriga') != null) {
            $riga = Descrizione::find(post('idriga'));
        } else {
            $riga = Descrizione::build($contratto);
        }

        $riga->descrizione = post('descrizione');

        $riga->save();

        if (post('idriga') != null) {
            flash()->info(tr('Riga descrittiva modificata!'));
        } else {
            flash()->info(tr('Riga descrittiva aggiunta!'));
        }

        break;

    // Eliminazione riga
    case 'delete_riga':
        $id_riga = post('riga_id');
        $type = post('riga_type');
$riga = $contratto->getRiga($type, $id_riga);

        if (!empty($riga)) {
            $riga->delete();

            flash()->info(tr('Riga eliminata!'));
        }

        break;

    // Scollegamento intervento da contratto
    case 'unlink':
        if (get('idcontratto') !== null && get('idintervento') !== null) {
            $idcontratto = get('idcontratto');
            $idintervento = get('idintervento');

            $query = 'DELETE FROM `co_promemoria` WHERE idcontratto='.prepare($idcontratto).' AND idintervento='.prepare($idintervento);
            $dbo->query($query);

            flash()->info(tr('Intervento _NUM_ rimosso!', [
                '_NUM_' => $idintervento,
            ]));
        }
        break;

    case 'update_position':
        $order = explode(',', post('order', true));

        foreach ($order as $i => $id_riga) {
            $dbo->query('UPDATE `co_righe_contratti` SET `order` = '.prepare($i).' WHERE id='.prepare($id_riga));
        }

        break;

    // eliminazione contratto
    case 'delete':

        // Fatture o interventi collegati a questo contratto
        $elementi = $dbo->fetchArray('SELECT 0 AS `codice`, `co_documenti`.`id` AS `id`, `co_documenti`.`numero` AS `numero`, `co_documenti`.`numero_esterno` AS `numero_esterno`,  `co_documenti`.`data`, `co_tipidocumento`.`descrizione` AS `tipo_documento`, `co_tipidocumento`.`dir` AS `dir`  FROM `co_documenti` JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento` WHERE `co_documenti`.`id` IN (SELECT `iddocumento` FROM `co_righe_documenti` WHERE `idcontratto` = '.prepare($id_record).')'.'
        UNION
        SELECT  `in_interventi`.`codice` AS `codice`, `in_interventi`.`id` AS `id`, 0 AS `numero`, 0 AS `numero_esterno`, `in_interventi`.`data_richiesta` AS `data`, 0 AS `tipo_documento`, 0 AS `dir` FROM `in_interventi` WHERE `in_interventi`.`id_contratto` = '.prepare($id_record).' ORDER BY `data` ');

        if (empty($elementi)) {
            try {
                $contratto->delete();

                $dbo->query('DELETE FROM co_promemoria WHERE idcontratto='.prepare($id_record));
                $dbo->query('DELETE FROM co_contratti_tipiintervento WHERE idcontratto='.prepare($id_record));
                $dbo->query('DELETE FROM my_impianti_contratti WHERE idcontratto='.prepare($id_record));

                flash()->info(tr('Contratto eliminato!'));
            } catch (InvalidArgumentException $e) {
                flash()->error(tr('Sono stati utilizzati alcuni serial number nel documento: impossibile procedere!'));
            }
        }

        break;

    // Rinnovo contratto
    case 'renew':
        $diff = $contratto->data_conclusione->diffAsCarbonInterval($contratto->data_accettazione);

        $new_contratto = $contratto->replicate();
        $new_contratto->numero = Contratto::getNextNumero();

        $new_contratto->idcontratto_prev = $contratto->id;
        $new_contratto->data_accettazione = $contratto->data_conclusione->copy()->addDays(1);
        $new_contratto->data_conclusione = $new_contratto->data_accettazione->copy()->add($diff);
        $new_contratto->stato = 'Bozza';
        $new_contratto->save();
        $new_idcontratto = $new_contratto->id;

        // Correzioni dei prezzi per gli interventi
        $dbo->query('DELETE FROM co_contratti_tipiintervento WHERE idcontratto='.prepare($new_idcontratto));
        $dbo->query('INSERT INTO co_contratti_tipiintervento(idcontratto, idtipointervento, costo_ore, costo_km, costo_dirittochiamata, costo_ore_tecnico, costo_km_tecnico, costo_dirittochiamata_tecnico) SELECT '.prepare($new_idcontratto).', idtipointervento, costo_ore, costo_km, costo_dirittochiamata, costo_ore_tecnico, costo_km_tecnico, costo_dirittochiamata_tecnico FROM co_contratti_tipiintervento AS z WHERE idcontratto='.prepare($id_record));
        $new_contratto->save();

        // Replico le righe del contratto
        $righe = $contratto->getRighe();
        foreach ($righe as $riga) {
            $new_riga = $riga->replicate();
            $new_riga->qta_evasa = 0;
            $new_riga->idcontratto = $new_contratto->id;

            $new_riga->save();
        }

        // Replicazione degli impianti
        $impianti = $dbo->fetchArray('SELECT idimpianto FROM my_impianti_contratti WHERE idcontratto='.prepare($id_record));
        $dbo->sync('my_impianti_contratti', ['idcontratto' => $new_idcontratto], ['idimpianto' => array_column($impianti, 'idimpianto')]);

        // Replicazione dei promemoria
        $promemoria = $dbo->fetchArray('SELECT * FROM co_promemoria WHERE idcontratto='.prepare($id_record));
        $giorni = $contratto->data_conclusione->diffInDays($contratto->data_accettazione);
        foreach ($promemoria as $p) {
            $dbo->insert('co_promemoria', [
                'idcontratto' => $new_idcontratto,
                'data_richiesta' => date('Y-m-d', strtotime($p['data_richiesta'].' +'.$giorni.' day')),
                'idtipointervento' => $p['idtipointervento'],
                'richiesta' => $p['richiesta'],
                'idimpianti' => $p['idimpianti'],
            ]);
            $id_promemoria = $dbo->lastInsertedID();

            $promemoria = Promemoria::find($p['id']);
            $righe = $promemoria->getRighe();
            foreach ($righe as $riga) {
                $new_riga = $riga->replicate();
                $new_riga->id_promemoria = $id_promemoria;
                $new_riga->save();
            }

            // Copia degli allegati
            Uploads::copy([
                'id_module' => $id_module,
                'id_plugin' => Plugins::get('Pianificazione interventi')['id'],
                'id_record' => $p['id'],
            ], [
                'id_module' => $id_module,
                'id_plugin' => Plugins::get('Pianificazione interventi')['id'],
                'id_record' => $id_promemoria,
            ]);
        }

        // Cambio stato precedente contratto in concluso (non più pianificabile)
        $dbo->query('UPDATE `co_contratti` SET `rinnovabile`= 0, `idstato`= (SELECT id FROM co_staticontratti WHERE is_pianificabile = 0 AND is_fatturabile = 1 AND descrizione = \'Concluso\')  WHERE `id` = '.prepare($id_record));

        flash()->info(tr('Contratto rinnovato!'));

        $id_record = $new_idcontratto;

        break;

        case 'import':

        $rs = $dbo->fetchArray('SELECT * FROM co_contratti_tipiintervento WHERE idcontratto = '.prepare(post('idcontratto')).' AND idtipointervento='.prepare(post('idtipointervento')));

        // Se la riga in_tipiintervento esiste, la aggiorno...
        if (!empty($rs)) {
            $result = $dbo->query('UPDATE co_contratti_tipiintervento SET '
                .' costo_ore=(SELECT costo_orario FROM in_tipiintervento WHERE idtipointervento='.prepare(post('idtipointervento')).'), '
                .' costo_km=(SELECT costo_km FROM in_tipiintervento WHERE idtipointervento='.prepare(post('idtipointervento')).'), '
                .' costo_dirittochiamata=(SELECT costo_diritto_chiamata FROM in_tipiintervento WHERE idtipointervento='.prepare(post('idtipointervento')).'), '
                .' costo_ore_tecnico=(SELECT costo_orario_tecnico FROM in_tipiintervento WHERE idtipointervento='.prepare(post('idtipointervento')).'), '
                .' costo_km_tecnico=(SELECT costo_km_tecnico FROM in_tipiintervento WHERE idtipointervento='.prepare(post('idtipointervento')).'), '
                .' costo_dirittochiamata_tecnico=(SELECT costo_diritto_chiamata_tecnico FROM in_tipiintervento WHERE idtipointervento='.prepare(post('idtipointervento')).') '
                .' WHERE idcontratto='.prepare(post('idcontratto')).' AND idtipointervento='.prepare(post('idtipointervento')));

            if ($result) {
                flash()->info(tr('Informazioni tariffe salvate correttamente!'));
            } else {
                flash()->error(tr("Errore durante l'importazione tariffe!"));
            }
        }

        // ...altrimenti la creo
        else {
            if ($dbo->query('INSERT INTO co_contratti_tipiintervento( idcontratto, idtipointervento, costo_ore, costo_km, costo_dirittochiamata, costo_ore_tecnico, costo_km_tecnico, costo_dirittochiamata_tecnico ) VALUES( '.prepare(post('idcontratto')).', '.prepare(post('idtipointervento')).', (SELECT costo_orario FROM in_tipiintervento WHERE idtipointervento='.prepare(post('idtipointervento')).'), (SELECT costo_km FROM in_tipiintervento WHERE idtipointervento='.prepare(post('idtipointervento')).'), (SELECT costo_diritto_chiamata FROM in_tipiintervento WHERE idtipointervento='.prepare(post('idtipointervento')).'),  (SELECT costo_orario_tecnico FROM in_tipiintervento WHERE idtipointervento='.prepare(post('idtipointervento')).'), (SELECT costo_km_tecnico FROM in_tipiintervento WHERE idtipointervento='.prepare(post('idtipointervento')).'), (SELECT costo_diritto_chiamata_tecnico FROM in_tipiintervento WHERE idtipointervento='.prepare(post('idtipointervento')).') )')) {
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
        if (!is_subclass_of($class, \Common\Document::class)) {
            return;
        }
        $documento = $class::find($id_documento);

        // Individuazione sede
        $id_sede = ($documento->direzione == 'entrata') ? $documento->idsede_destinazione : $documento->idsede_partenza;
        $id_sede = $id_sede ?: $documento->idsede;
        $id_sede = $id_sede ?: 0;

        // Creazione del contratto al volo
        if (post('create_document') == 'on') {
            $contratto = Contratto::build($documento->anagrafica, $documento->nome);

            $contratto->idpagamento = $documento->idpagamento;
            $contratto->idsede = $id_sede;

            $contratto->id_documento_fe = $documento->id_documento_fe;
            $contratto->codice_cup = $documento->codice_cup;
            $contratto->codice_cig = $documento->codice_cig;
            $contratto->num_item = $documento->num_item;

            $contratto->descrizione = $documento->descrizione;
            $contratto->esclusioni = $documento->esclusioni;

            $contratto->save();

            $id_record = $contratto->id;
        }

        $righe = $documento->getRighe();
        foreach ($righe as $riga) {
            if (post('evadere')[$riga->id] == 'on' and !empty(post('qta_da_evadere')[$riga->id])) {
                $qta = post('qta_da_evadere')[$riga->id];

                $copia = $riga->copiaIn($contratto, $qta);

                $copia->save();
            }
        }

        // Modifica finale dello stato
        if (post('create_document') == 'on') {
            $contratto->idstato = post('id_stato');
            $contratto->save();
        }

        ricalcola_costiagg_ordine($id_record);

        // Messaggio informativo
        $message = tr('_DOC_ aggiunto!', [
            '_DOC_' => $documento->getReference(),
        ]);
        flash()->info($message);

        break;
}
