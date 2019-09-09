<?php

include_once __DIR__.'/../../core.php';

use Modules\Anagrafiche\Anagrafica;
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Contratti\Components\Articolo;
use Modules\Contratti\Components\Descrizione;
use Modules\Contratti\Components\Riga;
use Modules\Contratti\Components\Sconto;
use Modules\Contratti\Contratto;

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
            $contratto->validita = post('validita');
            $contratto->data_bozza = post('data_bozza');
            $contratto->data_accettazione = post('data_accettazione');
            $contratto->data_rifiuto = post('data_rifiuto');
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
        $new->idstato = 1;
        $new->save();

        $id_record = $new->id;

        $righe = $contratto->getRighe();
        foreach ($righe as $riga) {
            $new_riga = $riga->replicate();
            $new_riga->setParent($new);

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
        }

        $qta = post('qta');

        $articolo->descrizione = post('descrizione');
        $articolo->um = post('um') ?: null;

        $articolo->id_iva = post('idiva');

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

        break;

    case 'manage_sconto':
        if (post('idriga') != null) {
            $sconto = Sconto::find(post('idriga'));
        } else {
            $sconto = Sconto::build($contratto);
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
            $riga = Riga::build($contratto);
        }

        $qta = post('qta');

        $riga->descrizione = post('descrizione');
        $riga->um = post('um') ?: null;

        $riga->id_iva = post('idiva');

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
        $id_riga = post('idriga');
        if ($id_riga !== null) {
            $riga = Descrizione::find($id_riga) ?: Riga::find($id_riga);
            $riga = $riga ? $riga : Articolo::find($id_riga);
            $riga = $riga ? $riga : Sconto::find($id_riga);

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
        $orders = explode(',', $_POST['order']);
        $order = 0;

        foreach ($orders as $idriga) {
            $dbo->query('UPDATE `co_righe_contratti` SET `order`='.prepare($order).' WHERE id='.prepare($idriga));
            ++$order;
        }

        break;

    // eliminazione contratto
    case 'delete':
        $dbo->query('DELETE FROM co_promemoria WHERE idcontratto='.prepare($id_record));

        $contratto->delete();

        flash()->info(tr('Contratto eliminato!'));

        break;

    // Rinnovo contratto
    case 'renew':
        $rs = $dbo->fetchArray('SELECT *, DATEDIFF(data_conclusione, data_accettazione) AS giorni FROM co_contratti WHERE id='.prepare($id_record));

        if (sizeof($rs) == 1) {
            // Verifico se il rinnovo contratto è un numero accettabile con la differenza di data inizio e data fine
            if ($rs[0]['giorni'] > 0 && $rs[0]['giorni'] < 365 * 10) {
                $giorni_add = $rs[0]['giorni'];
            } else {
                $giorni_add = 0;
            }

            // Calcolo numero successivo contratti
            $rs2 = $dbo->fetchArray('SELECT MAX(CAST(numero AS UNSIGNED)) AS maxn FROM co_contratti');
            $numero = $rs2[0]['maxn'] + 1;

            if ($dbo->query('INSERT INTO co_contratti(numero, nome, idagente, data_bozza, data_accettazione, data_rifiuto, data_conclusione, rinnovabile, giorni_preavviso_rinnovo, budget, descrizione, idstato, idreferente, validita, esclusioni, idanagrafica, idpagamento, costo_diritto_chiamata, ore_lavoro, costo_orario, costo_km, idcontratto_prev) VALUES('.prepare($numero).', '.prepare($rs[0]['nome']).', '.prepare($rs[0]['idagente']).', NOW(), '.prepare(date('Y-m-d', strtotime($rs[0]['data_conclusione'].' +1 day'))).', "", '.prepare(date('Y-m-d', strtotime($rs[0]['data_conclusione'].' +'.$giorni_add.' day'))).', '.prepare($rs[0]['rinnovabile']).', '.prepare($rs[0]['giorni_preavviso_rinnovo']).', '.prepare($rs[0]['budget']).', '.prepare($rs[0]['descrizione']).', '.prepare($rs[0]['idstato']).', '.prepare($rs[0]['idreferente']).', '.prepare($rs[0]['validita']).', '.prepare($rs[0]['esclusioni']).', '.prepare($rs[0]['idanagrafica']).', '.prepare($rs[0]['idpagamento']).', '.prepare($rs[0]['costo_diritto_chiamata']).', '.prepare($rs[0]['ore_lavoro']).', '.prepare($rs[0]['costo_orario']).', '.prepare($rs[0]['costo_km']).', '.prepare($id_record).')')) {
                $new_idcontratto = $dbo->lastInsertedID();

                $dbo->query('INSERT INTO co_contratti_tipiintervento(idcontratto, idtipointervento, costo_ore, costo_km, costo_dirittochiamata, costo_ore_tecnico, costo_km_tecnico, costo_dirittochiamata_tecnico) SELECT '.prepare($new_idcontratto).', idtipointervento, costo_ore, costo_km, costo_dirittochiamata, costo_ore_tecnico, costo_km_tecnico, costo_dirittochiamata_tecnico FROM co_contratti_tipiintervento AS z WHERE idcontratto='.prepare($id_record));

                // Replico le righe del contratto
                $rs = $dbo->fetchArray('SELECT * FROM co_righe_contratti WHERE idcontratto='.prepare($id_record));

                for ($i = 0; $i < sizeof($rs); ++$i) {
                    unset($rs[$i]['id']);
                    unset($rs[$i]['created_at']);
                    unset($rs[$i]['updated_at']);
                    unset($rs[$i]['qta_evasa']);
                    $rs[$i]['idcontratto'] = $new_idcontratto;

                    $dbo->insert('co_righe_contratti', $rs);
                }

                // Replicazione degli impianti
                $impianti = $dbo->fetchArray('SELECT idimpianto FROM my_impianti_contratti WHERE idcontratto='.prepare($id_record));
                $dbo->sync('my_impianti_contratti', ['idcontratto' => $new_idcontratto], ['idimpianto' => array_column($impianti, 'idimpianto')]);

                // Replicazione dei promemoria
                $promemoria = $dbo->fetchArray('SELECT * FROM co_promemoria WHERE idcontratto='.prepare($id_record));
                foreach ($promemoria as $p) {
                    $dbo->insert('co_promemoria', [
                        'idcontratto' => $new_idcontratto,
                        'data_richiesta' => date('Y-m-d', strtotime($p['data_richiesta'].' +'.$giorni_add.' day')),
                        'idtipointervento' => $p['idtipointervento'],
                        'richiesta' => $p['richiesta'],
                        'idimpianti' => $p['idimpianti'],
                    ]);
                    $id_promemoria = $dbo->lastInsertedID();

                    // Copia degli articoli
                    $dbo->query('INSERT INTO co_promemoria_articoli(idarticolo, id_promemoria, idimpianto, descrizione, prezzo_vendita, prezzo_acquisto, sconto, sconto_unitario, tipo_sconto, idiva, desc_iva, iva, qta, um, abilita_serial) SELECT idarticolo, :id_new, idimpianto, descrizione, prezzo_vendita, prezzo_acquisto, sconto, sconto_unitario, tipo_sconto, idiva, desc_iva, iva, qta, um, abilita_serial FROM co_promemoria_articoli AS z WHERE id_promemoria = :id_old', [
                        ':id_new' => $id_promemoria,
                        ':id_old' => $p['id'],
                    ]);

                    // Copia delle righe
                    $dbo->query('INSERT INTO co_promemoria_righe(id_promemoria, descrizione, qta, um, prezzo_vendita, prezzo_acquisto, idiva, desc_iva, iva, sconto, sconto_unitario, tipo_sconto) SELECT :id_new, descrizione, qta, um, prezzo_vendita, prezzo_acquisto, idiva, desc_iva, iva, sconto, sconto_unitario, tipo_sconto FROM co_promemoria_righe AS z WHERE id_promemoria = :id_old', [
                        ':id_new' => $id_promemoria,
                        ':id_old' => $p['id'],
                    ]);

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
            } else {
                flash()->error(tr('Errore durante il rinnovo del contratto!'));
            }
        }

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
}
