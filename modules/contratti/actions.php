<?php

include_once __DIR__.'/../../core.php';

use Modules\Anagrafiche\Anagrafica;
use Modules\Contratti\Components\Articolo;
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
            $idstato = post('idstato');
            $idanagrafica = post('idanagrafica');
            $idsede = post('idsede');
            $nome = post('nome');
            $idagente = post('idagente');
            $idpagamento = post('idpagamento');
            $numero = post('numero');

            // Se non specifico un budget me lo vado a ricalcolare
            if ($budget != '') {
                $budget = post('budget');
            } else {
                $q = "SELECT (SELECT SUM(subtotale) FROM co_righe_contratti GROUP BY idcontratto HAVING idcontratto=co_contratti.id) AS 'budget' FROM co_contratti WHERE id=".prepare($id_record);
                $rs = $dbo->fetchArray($q);
                $budget = $rs[0]['budget'];
            }

            $data_bozza = post('data_bozza');
            $data_accettazione = post('data_accettazione');
            $data_rifiuto = post('data_rifiuto');
            $data_conclusione = post('data_conclusione');
            $rinnovabile = post('rinnovabile');

            $giorni_preavviso_rinnovo = post('giorni_preavviso_rinnovo');
            $validita = post('validita');
            $idreferente = post('idreferente');
            $esclusioni = post('esclusioni');
            $descrizione = post('descrizione');
            // $ore_lavoro = post('ore_lavoro');

            $costo_orario = post('costo_orario');
            $costo_km = post('costo_km');
            $costo_diritto_chiamata = post('costo_diritto_chiamata');

            $id_documento_fe = post('id_documento_fe');
            $num_item = post('num_item');
            $codice_cig = post('codice_cig');
            $codice_cup = post('codice_cup');

            $query = 'UPDATE co_contratti SET idanagrafica='.prepare($idanagrafica).', 
				idsede='.prepare($idsede).',
				idstato='.prepare($idstato).',
				nome='.prepare($nome).',
				idagente='.prepare($idagente).',
				idpagamento='.prepare($idpagamento).',
				numero='.prepare($numero).',
				budget='.prepare($budget).',
				idreferente='.prepare($idreferente).',
				validita='.prepare($validita).',
				data_bozza='.prepare($data_bozza).',
				data_accettazione='.prepare($data_accettazione).',
				data_rifiuto='.prepare($data_rifiuto).',
				data_conclusione='.prepare($data_conclusione).',
				rinnovabile='.prepare($rinnovabile).',
				giorni_preavviso_rinnovo='.prepare($giorni_preavviso_rinnovo).',
				esclusioni='.prepare($esclusioni).', descrizione='.prepare($descrizione).',
				id_documento_fe='.prepare($id_documento_fe).',
				num_item='.prepare($num_item).',
				codice_cig='.prepare($codice_cig).',
				codice_cup='.prepare($codice_cup).' WHERE id='.prepare($id_record);

            // costo_diritto_chiamata='.prepare($costo_diritto_chiamata).', ore_lavoro='.prepare($ore_lavoro).', costo_orario='.prepare($costo_orario).', costo_km='.prepare($costo_km).'

            $dbo->query($query);

            $dbo->query('DELETE FROM my_impianti_contratti WHERE idcontratto='.prepare($id_record));
            foreach ((array) post('matricolaimpianto') as $matricolaimpianto) {
                $dbo->query('INSERT INTO my_impianti_contratti(idcontratto,idimpianto) VALUES('.prepare($id_record).', '.prepare($matricolaimpianto).')');
            }

            // Salvataggio costi attività unitari del contratto
            foreach (post('costo_ore') as $idtipointervento => $valore) {
                $rs = $dbo->fetchArray('SELECT * FROM co_contratti_tipiintervento WHERE idcontratto='.prepare($id_record).' AND idtipointervento='.prepare($idtipointervento));

                // Se non esiste il record lo inserisco...
                if (sizeof($rs) == 0) {
                    // Se almeno un valore è diverso da 0 inserisco l'importo...
                    if (post('costo_ore')[$idtipointervento] != 0 || post('costo_km')[$idtipointervento] != 0 || post('costo_dirittochiamata')[$idtipointervento] != 0) {
                        $dbo->query('INSERT INTO co_contratti_tipiintervento(idcontratto, idtipointervento, costo_ore, costo_km, costo_dirittochiamata, costo_ore_tecnico, costo_km_tecnico, costo_dirittochiamata_tecnico) VALUES('.prepare($id_record).', '.prepare($idtipointervento).', '.prepare(post('costo_ore')[$idtipointervento]).', '.prepare(post('costo_km')[$idtipointervento]).', '.prepare(post('costo_dirittochiamata')[$idtipointervento]).', '.prepare(post('costo_ore_tecnico')[$idtipointervento]).', '.prepare(post('costo_km_tecnico')[$idtipointervento]).', '.prepare(post('costo_dirittochiamata_tecnico')[$idtipointervento]).')');
                    }
                }

                // ...altrimenti...
                else {
                    // Aggiorno il nuovo valore se è diverso da 0...
                    if (post('costo_ore')[$idtipointervento] != 0 || post('costo_km')[$idtipointervento] != 0 || post('costo_dirittochiamata')[$idtipointervento] != 0) {
                        $dbo->query('UPDATE co_contratti_tipiintervento SET costo_ore='.prepare(post('costo_ore')[$idtipointervento]).', costo_km='.prepare(post('costo_km')[$idtipointervento]).', costo_dirittochiamata='.prepare(post('costo_dirittochiamata')[$idtipointervento]).', costo_ore_tecnico='.prepare(post('costo_ore_tecnico')[$idtipointervento]).', costo_km_tecnico='.prepare(post('costo_km_tecnico')[$idtipointervento]).', costo_dirittochiamata_tecnico='.prepare(post('costo_dirittochiamata_tecnico')[$idtipointervento]).' WHERE idcontratto='.prepare($id_record).' AND idtipointervento='.prepare($idtipointervento));
                    }

                    // ...altrimenti cancello l'eventuale riga
                    else {
                        $dbo->query('DELETE FROM co_contratti_tipiintervento WHERE idcontratto='.prepare($id_record).' AND idtipointervento='.prepare($idtipointervento));
                    }
                }
            }

            flash()->info(tr('Contratto modificato correttamente!'));
        }

        break;
	
	 // Duplica contratto
    case 'copy':
        $dbo->query('CREATE TEMPORARY TABLE tmp SELECT * FROM co_contratti WHERE id = '.prepare($id_record));
        $dbo->query('ALTER TABLE tmp DROP id');
        $dbo->query('INSERT INTO co_contratti SELECT NULL,tmp.* FROM tmp');
        $id_record = $dbo->lastInsertedID();
        $dbo->query('DROP TEMPORARY TABLE tmp');

        // Codice contratto: calcolo il successivo in base al formato specificato
        $numero = Contratto::getNextNumero();

        $dbo->query('UPDATE co_contratti SET idstato=1, numero = '.prepare($numero).' WHERE id='.prepare($id_record));

        //copio anche le righe del preventivo
        $dbo->query('CREATE TEMPORARY TABLE tmp SELECT * FROM co_righe_contratti WHERE idcontratto = '.filter('id_record'));
        $dbo->query('ALTER TABLE tmp DROP id');
        $dbo->query('UPDATE tmp SET idcontratto = '.prepare($id_record));
        $dbo->query('INSERT INTO co_righe_contratti SELECT NULL,tmp.* FROM tmp');
        $dbo->query('DROP TEMPORARY TABLE tmp');
		
		//Azzero eventuale quantità evasa
		$dbo->query('UPDATE co_righe_contratti SET qta_evasa=0 WHERE id='.prepare($id_record));

        flash()->info(tr('Contratto duplicato correttamente!'));

    break;


    case 'manage_sconto':
        if (post('idriga') != null) {
            $sconto = Sconto::find(post('idriga'));
        } else {
            $sconto = Sconto::build($contratto);
        }

        $sconto->qta = 1;

        $sconto->descrizione = post('descrizione');
        $sconto->id_iva = post('idiva');

        $sconto->sconto_unitario = post('sconto_unitario');
        $sconto->tipo_sconto = 'UNT';

        $sconto->save();

        if (post('idriga') != null) {
            flash()->info(tr('Sconto/maggiorazione modificato!'));
        } else {
            flash()->info(tr('Sconto/maggiorazione aggiunta!'));
        }

        break;

    // Aggiungo una riga al contratto
    case 'addriga':
        $idiva = post('idiva');
        $idarticolo = post('idarticolo');
        $descrizione = post('descrizione');

        $qta = post('qta');
        $prezzo = post('prezzo');

        // Calcolo dello sconto
        $sconto_unitario = post('sconto');
        $tipo_sconto = post('tipo_sconto');
        $sconto = calcola_sconto([
            'sconto' => $sconto_unitario,
            'prezzo' => $prezzo,
            'tipo' => $tipo_sconto,
            'qta' => $qta,
        ]);

        $subtot = $prezzo * $qta;

        $um = post('um');

        // Lettura iva dell'articolo
        $rs2 = $dbo->fetchArray('SELECT percentuale, descrizione, indetraibile FROM co_iva WHERE id='.prepare($idiva));
        $iva = ($prezzo * $qta - $sconto) / 100 * $rs2[0]['percentuale'];
        $iva_indetraibile = $iva / 100 * $rs2[0]['indetraibile'];
        $desc_iva = $rs2[0]['descrizione'];

        $dbo->query('INSERT INTO co_righe_contratti(idcontratto, idarticolo, idiva, desc_iva, iva, iva_indetraibile, descrizione, subtotale, um, qta, sconto, sconto_unitario, tipo_sconto, is_descrizione, `order`) VALUES ('.prepare($id_record).', '.prepare($idarticolo).', '.prepare($idiva).', '.prepare($desc_iva).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare($descrizione).', '.prepare($subtot).', '.prepare($um).', '.prepare($qta).', '.prepare($sconto).', '.prepare($sconto_unitario).', '.prepare($tipo_sconto).', '.prepare(empty($qta)).', (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_contratti AS t WHERE idcontratto='.prepare($id_record).'))');

        // Messaggi informativi
        if (!empty($idarticolo)) {
            flash()->info(tr('Articolo aggiunto!'));
        } elseif (!empty($qta)) {
            flash()->info(tr('Riga aggiunta!'));
        } else {
            flash()->info(tr('Riga descrittiva aggiunta!'));
        }

        break;

    case 'editriga':
        $idriga = post('idriga');

        $rs = $dbo->fetchArray("SELECT * FROM co_righe_contratti WHERE id='".$idriga."'");
        $is_descrizione = $rs[0]['is_descrizione'];

        $idarticolo = post('idarticolo');
        $descrizione = post('descrizione');

        $qta = post('qta');
        $prezzo = post('prezzo');
        $subtot = $prezzo * $qta;

        // Calcolo dello sconto
        $sconto_unitario = post('sconto');
        $tipo_sconto = post('tipo_sconto');
        $sconto = calcola_sconto([
            'sconto' => $sconto_unitario,
            'prezzo' => $prezzo,
            'tipo' => $tipo_sconto,
            'qta' => $qta,
        ]);

        $idiva = post('idiva');
        $um = post('um');

        // Calcolo iva
        $query = 'SELECT percentuale, descrizione, indetraibile FROM co_iva WHERE id='.prepare($idiva);
        $rs = $dbo->fetchArray($query);
        $iva = ($subtot - $sconto) / 100 * $rs[0]['percentuale'];
        $iva_indetraibile = $iva / 100 * $rs[0]['indetraibile'];
        $desc_iva = $rs[0]['descrizione'];

        // Modifica riga generica sul documento
        if ($is_descrizione == 0) {
            $query = 'UPDATE co_righe_contratti SET idarticolo='.prepare($idarticolo).', idiva='.prepare($idiva).', desc_iva='.prepare($desc_iva).', iva='.prepare($iva).', iva_indetraibile='.prepare($iva_indetraibile).', descrizione='.prepare($descrizione).', subtotale='.prepare($subtot).', sconto='.prepare($sconto).', sconto_unitario='.prepare($sconto_unitario).', tipo_sconto='.prepare($tipo_sconto).', um='.prepare($um).', qta='.prepare($qta).' WHERE id='.prepare($idriga);
        } else {
            $query = 'UPDATE co_righe_contratti SET descrizione='.prepare($descrizione).' WHERE id='.prepare($idriga);
        }
        $dbo->query($query);

        flash()->info(tr('Riga modificata!'));

        break;

    // Eliminazione riga
    case 'delriga':
        if (post('idriga') !== null) {
            $idriga = post('idriga');

            $query = 'DELETE FROM `co_righe_contratti` WHERE idcontratto='.prepare($id_record).' AND id='.prepare($idriga);

            if ($dbo->query($query)) {
                flash()->info(tr('Riga eliminata!'));
            }
        }

        // Ricalcolo il budget
        $dbo->query('UPDATE co_contratti SET budget=( SELECT SUM(subtotale) FROM co_righe_contratti GROUP BY idcontratto HAVING idcontratto=co_contratti.id ) WHERE id='.prepare($id_record));

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
        $dbo->query('DELETE FROM co_contratti WHERE id='.prepare($id_record));
        $dbo->query('DELETE FROM co_promemoria WHERE idcontratto='.prepare($id_record));
        $dbo->query('DELETE FROM co_righe_contratti WHERE idcontratto='.prepare($id_record));

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
                    $dbo->query('INSERT INTO co_righe_contratti(idcontratto, descrizione, subtotale, um, qta) VALUES('.prepare($new_idcontratto).', '.prepare($rs[$i]['descrizione']).', '.prepare($rs[$i]['subtotale']).', '.prepare($rs[$i]['um']).', '.prepare($rs[$i]['qta']).')');
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
                    $dbo->query('INSERT INTO co_promemoria_articoli(idarticolo, id_promemoria, idimpianto, idautomezzo, descrizione, prezzo_vendita, prezzo_acquisto, sconto, sconto_unitario, tipo_sconto, idiva, desc_iva, iva, qta, um, abilita_serial) SELECT idarticolo, :id_new, idimpianto, idautomezzo, descrizione, prezzo_vendita, prezzo_acquisto, sconto, sconto_unitario, tipo_sconto, idiva, desc_iva, iva, qta, um, abilita_serial FROM co_promemoria_articoli AS z WHERE id_promemoria = :id_old', [
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
}
