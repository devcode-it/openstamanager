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

use Carbon\Carbon;
use Modules\Articoli\Articolo;
use Modules\Articoli\Categoria;
use Modules\CombinazioniArticoli\Combinazione;
use Modules\Iva\Aliquota;
use Util\Ini;

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'restore':
        $articolo->restore();
        flash()->info(tr('Articolo ripristinato correttamente!'));

    // Aggiunta articolo
    // no break
    case 'add':
        //Se non specifico il codice articolo lo imposto uguale all'id della riga
        if (empty(post('codice'))) {
            $codice = $dbo->fetchOne('SELECT MAX(id) as codice FROM mg_articoli')['codice'] + 1;
        } else {
            $codice = post('codice', true);
        }

        // Inserisco l'articolo e avviso se esiste un altro articolo con stesso codice.
        $numero_codice = Articolo::where([
            ['codice', $value],
            ['id', '<>', $id_record],
        ])->count();
        if ($numero_codice > 0) {
            flash()->warning(tr('Attenzione: il codice _CODICE_ è già stato utilizzato _N_ volta', [
                '_CODICE_' => $codice,
                '_N_' => $numero_codice,
            ]));
        }

        $categoria = Categoria::find(post('categoria'));
        $sottocategoria = Categoria::find(post('subcategoria'));
        $articolo = Articolo::build($codice, post('descrizione'), $categoria, $sottocategoria);

        $articolo->barcode = post('barcode');
        $articolo->threshold_qta = post('threshold_qta');
        $articolo->coefficiente = post('coefficiente');
        $articolo->idiva_vendita = post('idiva_vendita');
        $articolo->prezzo_acquisto = post('prezzo_acquisto');
        if (empty(post('coefficiente'))) {
            $articolo->setPrezzoVendita(post('prezzo_vendita'), post('idiva_vendita'));
        }
        $articolo->idconto_vendita = post('idconto_vendita');
        $articolo->idconto_acquisto = post('idconto_acquisto');
        $articolo->abilita_serial = post('abilita_serial');

        $articolo->um = post('um');
        $articolo->um_secondaria = post('um_secondaria');
        $articolo->fattore_um_secondaria = post('fattore_um_secondaria');

        $articolo->save();

        // Aggiornamento delle varianti per i campi comuni
        Combinazione::sincronizzaVarianti($articolo);

        if (!empty(post('qta'))) {
            $data_movimento = new Carbon();
            $articolo->movimenta(post('qta'), tr('Carico manuale'), $data_movimento->format('Y-m-d'), true);
        }

        $id_record = $articolo->id;
        $iva = post('idiva_vendita') ? Aliquota::find(post('idiva_vendita')) : null;

        if (isAjaxRequest()) {
            echo json_encode([
                'id' => $id_record,
                'text' => post('codice', true).' - '.post('descrizione'),
                'data' => [
                    'descrizione' => post('descrizione'),
                    'prezzo_acquisto' => post('prezzo_acquisto'),
                    'prezzo_vendita' => post('prezzo_vendita'),
                    'idiva_vendita' => post('idiva_vendita') ?: null,
                    'iva_vendita' => $iva ? $iva->descrizione : null,
                    'um_secondaria' => post('um_secondaria'),
                    'um' => post('um'),
                ],
            ]);
        }

        flash()->info(tr('Aggiunto un nuovo articolo'));

        break;

    // Modifica articolo
    case 'update':
        $qta = post('qta');
        $tresholdSedi = post('threshold_qta_sedi');

        // Inserisco l'articolo e avviso se esiste un altro articolo con stesso codice.
        $numero_codice = Articolo::where([
            ['codice', $value],
            ['id', '<>', $id_record],
        ])->count();
        if ($numero_codice > 0) {
            flash()->warning(tr('Attenzione: il codice _CODICE_ è già stato utilizzato _N_ volta', [
                '_CODICE_' => post('codice', true),
                '_N_' => $numero_codice,
            ]));
        }

        $articolo->codice = post('codice', true);
        $articolo->barcode = post('barcode');
        $articolo->descrizione = post('descrizione');
        $articolo->um = post('um');
        $articolo->id_categoria = post('categoria');
        $articolo->id_sottocategoria = post('subcategoria');
        $articolo->abilita_serial = post('abilita_serial');
        $articolo->ubicazione = post('ubicazione');
        $articolo->threshold_qta = $tresholdSedi[0];
        $articolo->coefficiente = post('coefficiente');
        $articolo->idiva_vendita = post('idiva_vendita');
        $articolo->prezzo_acquisto = post('prezzo_acquisto');
        $articolo->idconto_vendita = post('idconto_vendita');
        $articolo->idconto_acquisto = post('idconto_acquisto');
        $articolo->id_fornitore = post('id_fornitore');
        $articolo->gg_garanzia = post('gg_garanzia');
        $articolo->servizio = post('servizio');
        $articolo->volume = post('volume');
        $articolo->peso_lordo = post('peso_lordo');

        $articolo->um_secondaria = post('um_secondaria');
        $articolo->fattore_um_secondaria = post('fattore_um_secondaria');
        $articolo->qta_multipla = post('qta_multipla');
        $articolo->setMinimoVendita(post('minimo_vendita'), post('idiva_vendita'));

        if (empty(post('coefficiente'))) {
            $articolo->setPrezzoVendita(post('prezzo_vendita'), post('idiva_vendita'));
        }

        $componente = post('componente_filename');
        $articolo->componente_filename = $componente;
        $articolo->attivo = post('attivo');
        $articolo->note = post('note');

        $articolo->save();

        // Aggiorno le soglie minime per le sedi
        $gestisciMagazzini = $dbo->fetchOne('SELECT * FROM zz_settings WHERE nome = "Gestisci soglia minima per magazzino"');

        if ($gestisciMagazzini['valore'] == '1') {
            foreach ($tresholdSedi as $id_sede => $treshold) {
                $item = $dbo->fetchOne(
                    'SELECT * FROM mg_articoli_sedi WHERE id_articolo = ' . prepare($id_record) . ' AND id_sede = ' . prepare($id_sede)
                );

                if (empty($item)) {
                    $dbo->query(
                        'INSERT INTO mg_articoli_sedi(id_articolo, id_sede, threshold_qta)
                        VALUES(' . prepare($id_record) . ', ' . prepare($id_sede) . ', ' . prepare($treshold) . ')'
                    );
                } else {
                    $dbo->query(
                        'UPDATE mg_articoli_sedi SET threshold_qta = '. prepare($treshold) . '
                        WHERE id_articolo = ' . prepare($id_record) . ' AND id_sede = ' . prepare($id_sede)
                    );
                }
            }
        }

        // Aggiornamento delle varianti per i campi comuni
        Combinazione::sincronizzaVarianti($articolo);

        // Leggo la quantità attuale per capire se l'ho modificata
        $old_qta = $record['qta'];
        $movimento = $qta - $old_qta;

        $qta_manuale = post('qta_manuale');
        if (!empty($qta_manuale)) {
            $descrizione_movimento = post('descrizione_movimento');
            $data_movimento = post('data_movimento');

            $articolo->movimenta($movimento, $descrizione_movimento, $data_movimento, true);
        }

        // Salvataggio info componente (campo `contenuto`)
        if (!empty($componente)) {
            $contenuto_precedente_esistente = !empty($articolo->contenuto);
            $contenuto = file_get_contents(base_dir().'/files/impianti/'.$componente);
            $contenuto_componente = Ini::read($contenuto);

            // Lettura dei campi esistenti per preservarne il valore
            // Se non è presente un componente, copia i valori dal file di origine
            $campi_componente = [];
            foreach ($contenuto_componente as $key => $value) {
                //Fix per nomi con spazi che vengono tradotti con "_" (es. Data_di_installazione)
                $key = preg_replace('/\s+/', '_', $key);

                $valore = $contenuto_precedente_esistente ? filter($key) : $value['valore'];

                $campi_componente[$key] = $valore;
            }
            $contenuto = Ini::write($contenuto, $campi_componente);

            // Salvataggio dei dati
            $dbo->query('UPDATE mg_articoli SET contenuto='.prepare($contenuto).' WHERE id='.prepare($id_record));
        } else {
            $dbo->query('UPDATE mg_articoli SET contenuto = \'\' WHERE id='.prepare($id_record));
        }

        // Upload file
        if (!empty($_FILES) && !empty($_FILES['immagine']['name'])) {
            $upload = Uploads::upload($_FILES['immagine'], [
                'name' => 'Immagine',
                'category' => 'Immagini',
                'id_module' => $id_module,
                'id_record' => $id_record,
            ], [
                'thumbnails' => true,
            ]);
            $filename = $upload->filename;

            if (!empty($filename)) {
                $dbo->update('mg_articoli', [
                    'immagine' => $filename,
                ], [
                    'id' => $id_record,
                ]);
            } else {
                flash()->warning(tr("Errore durante il caricamento dell'immagine!"));
            }
        }

        // Eliminazione file
        if (post('delete_immagine') !== null) {
            Uploads::delete($record['immagine'], [
                'id_module' => $id_module,
                'id_record' => $id_record,
            ]);

            $dbo->update('mg_articoli', [
                'immagine' => null,
            ], [
                'id' => $id_record,
            ]);
        }

        flash()->info(tr('Informazioni salvate correttamente!'));

        break;

    // Duplica articolo
    case 'copy':
        $new = $articolo->replicate();

        //Se non specifico il codice articolo lo imposto uguale all'id della riga
        if (empty(post('codice'))) {
            $codice = $dbo->fetchOne('SELECT MAX(id) as codice FROM mg_articoli')['codice'] + 1;
        } else {
            $codice = post('codice', true);
        }

        $new->codice = $codice;
        $new->qta = 0;
        $new->save();

        // Copia degli allegati
        $copia_allegati = post('copia_allegati');
        if (!empty($copia_allegati)) {
            $allegati = $articolo->uploads();
            foreach ($allegati as $allegato) {
                $allegato->copia([
                    'id_module' => $new->getModule()->id,
                    'id_record' => $new->id,
                ]);
            }
        }

        // Salvataggio immagine relativa
        $nome_immagine = $articolo->immagine_upload->name;
        $new->immagine = $new->uploads()->where('name', $nome_immagine)->first()->filename;
        $new->save();

        $id_record = $new->id;

        flash()->info(tr('Articolo duplicato correttamente!'));

    break;

    // Generazione seriali in sequenza
    case 'generate_serials':
        // Seriali
        $serial_start = post('serial_start');
        $serial_end = post('serial_end');

        preg_match("/(.*?)([\d]*$)/", $serial_start, $m);
        $numero_start = intval($m[2]);
        preg_match("/(.*?)([\d]*$)/", $serial_end, $m);
        $numero_end = intval($m[2]);
        $totale = abs($numero_end - $numero_start) + 1;

        $prefix = rtrim($serial_end, $numero_end);
        $pad_length = strlen($serial_end) - strlen($prefix);

        // Combinazione di seriali
        $serials = [];
        for ($s = 0; $s < $totale; ++$s) {
            $serial = $prefix.(str_pad($numero_start + $s, $pad_length, '0', STR_PAD_LEFT));

            $serials[] = $serial;
        }

        // no break
    case 'add_serials':
        $serials = $serials ?: filter('serials');

        $count = $dbo->attach('mg_prodotti', ['id_articolo' => $id_record, 'dir' => 'uscita'], ['serial' => $serials]);

        // Movimento il magazzino se l'ho specificato nelle impostazioni
        if (setting("Movimenta il magazzino durante l'inserimento o eliminazione dei lotti/serial number")) {
            $articolo->movimenta($count, tr('Carico magazzino con serial da _INIZIO_ a _FINE_', [
                '_INIZIO_' => $serial_start,
                '_FINE_' => $serial_end,
            ]), date('Y-m-d'));
        }

        flash()->info(tr('Aggiunti _NUM_ seriali!', [
            '_NUM_' => $count,
        ]));

        if ($count != $totale) {
            flash()->warning(tr('Alcuni seriali erano già presenti').'...');
        }

        break;

    case 'delprodotto':
        $idprodotto = post('idprodotto');

        // Leggo info prodotto per descrizione mg_movimenti
        $rs = $dbo->fetchArray('SELECT lotto, serial, altro FROM mg_prodotti WHERE id='.prepare($idprodotto));

        $query = 'DELETE FROM mg_prodotti WHERE id='.prepare($idprodotto);
        if ($dbo->query($query)) {
            // Movimento il magazzino se l'ho specificato nelle impostazioni
            if (setting("Movimenta il magazzino durante l'inserimento o eliminazione dei lotti/serial number")) {
                $articolo->movimenta(-1, tr('Eliminazione dal magazzino del prodotto con serial _SERIAL_', [
                    '_SERIAL_' => $rs[0]['serial'],
                ]), date('Y-m-d'));
            }

            flash()->info(tr('Prodotto rimosso!'));
        }
        break;

    case 'delmovimento':
        $idmovimento = post('idmovimento');

        // Lettura qtà movimento
        $rs = $dbo->fetchArray('SELECT idarticolo, qta FROM mg_movimenti WHERE id='.prepare($idmovimento));
        $qta = $rs[0]['qta'];
        $idarticolo = $rs[0]['idarticolo'];

        // Aggiorno la quantità dell'articolo
        $dbo->query('UPDATE mg_articoli SET qta=qta-'.$qta.' WHERE id='.prepare($idarticolo));

        $query = 'DELETE FROM mg_movimenti WHERE id='.prepare($idmovimento);
        if ($dbo->query($query)) {
            flash()->info(tr('Movimento rimosso!'));
        }
        break;

    case 'delete':
        $articolo->delete();

        flash()->info(tr('Articolo eliminato!'));
        break;

    case 'add-movimento':

        $articolo = Articolo::find(post('idarticolo'));
        $tipo_movimento = post('tipo_movimento');
        $descrizione = post('movimento');
        $data = post('data');
        $qta = post('qta');

        $idsede_partenza = post('idsede_partenza');
        $idsede_destinazione = post('idsede_destinazione');

        if ($tipo_movimento == 'carico' || $tipo_movimento == 'scarico') {
            if ($tipo_movimento == 'carico') {
                $id_sede_azienda = $idsede_destinazione;
                $id_sede_controparte = 0;
            } elseif ($tipo_movimento == 'scarico') {
                $id_sede_azienda = $idsede_partenza;
                $id_sede_controparte = 0;

                $qta = -$qta;
            }

            // Registrazione del movimento con variazione della quantità
            $articolo->movimenta($qta, $descrizione, $data, 1, [
                'idsede' => $id_sede_azienda,
            ]);

        } elseif ($tipo_movimento == 'spostamento') {
            // Registrazione del movimento verso la sede di destinazione
            $articolo->registra($qta, $descrizione, $data, 1, [
                'idsede' => $idsede_destinazione,
            ]);

            // Registrazione del movimento dalla sede di origine
            $articolo->registra(-$qta, $descrizione, $data, 1, [
                'idsede' => $idsede_partenza,
            ]);
        }

        break;
}

// Operazioni aggiuntive per l'immagine
if (filter('op') == 'rimuovi-allegato' && filter('filename') == $record['immagine']) {
    $dbo->update('mg_articoli', [
        'immagine' => null,
    ], [
        'id' => $id_record,
    ]);
} elseif (filter('op') == 'aggiungi-allegato' && filter('nome_allegato') == 'Immagine') {
    $dbo->update('mg_articoli', [
        'immagine' => $upload->filename,
    ], [
        'id' => $id_record,
    ]);
}
