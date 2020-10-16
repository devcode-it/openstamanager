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

use Modules\Articoli\Articolo;
use Modules\Articoli\Categoria;
use Util\Ini;

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    // Aggiunta articolo
    case 'add':
        //Se non specifico il codice articolo lo imposto uguale all'id della riga
        if (empty(post('codice'))) {
            $codice = $dbo->fetchOne('SELECT MAX(id) as codice FROM mg_articoli')['codice'] + 1;
        } else {
            $codice = post('codice');
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
        $articolo->prezzo_acquisto = post('prezzo_acquisto');
        $articolo->setPrezzoVendita(post('prezzo_vendita'), post('idiva_vendita'));
        $articolo->save();

        if (!empty(post('qta'))) {
            $data_movimento = new Carbon();
            $articolo->movimenta(post('qta'), tr('Carico manuale'), $data_movimento->format('Y-m-d'), true);
        }

        $id_record = $articolo->id;

        if (isAjaxRequest()) {
            echo json_encode([
                'id' => $id_record,
                'text' => post('descrizione'),
                'data' => [
                    'descrizione' => post('descrizione'),
                    'prezzo_acquisto' => post('prezzo_acquisto'),
                    'prezzo_vendita' => post('prezzo_vendita'),
                    'idiva_vendita' => post('idiva_vendita'),
                ],
            ]);
        }

        flash()->info(tr('Aggiunto un nuovo articolo'));

        break;

    // Modifica articolo
    case 'update':
        $qta = post('qta');

        // Inserisco l'articolo e avviso se esiste un altro articolo con stesso codice.
        $numero_codice = Articolo::where([
            ['codice', $value],
            ['id', '<>', $id_record],
        ])->count();
        if ($numero_codice > 0) {
            flash()->warning(tr('Attenzione: il codice _CODICE_ è già stato utilizzato _N_ volta', [
                '_CODICE_' => post('codice'),
                '_N_' => $numero_codice,
            ]));
        }

        $articolo->codice = post('codice');
        $articolo->barcode = post('barcode');
        $articolo->descrizione = post('descrizione');
        $articolo->um = post('um');
        $articolo->id_categoria = post('categoria');
        $articolo->id_sottocategoria = post('subcategoria');
        $articolo->abilita_serial = post('abilita_serial');
        $articolo->ubicazione = post('ubicazione');
        $articolo->threshold_qta = post('threshold_qta');
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

        $articolo->setPrezzoVendita(post('prezzo_vendita'), post('idiva_vendita'));

        $componente = post('componente_filename');
        $articolo->componente_filename = $componente;
        $articolo->attivo = post('attivo');
        $articolo->note = post('note');

        $articolo->save();

        // Leggo la quantità attuale per capire se l'ho modificata
        $old_qta = $record['qta'];
        $movimento = $qta - $old_qta;

        if (post('qta_manuale') == 1) {
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
            $filename = Uploads::upload($_FILES['immagine'], [
                'name' => 'Immagine',
                'id_module' => $id_module,
                'id_record' => $id_record,
            ], [
                'thumbnails' => true,
            ]);

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
        $new->codice = post('codice');
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
            ]), date());
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
                ]), date());
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
}

// Operazioni aggiuntive per l'immagine
if (filter('op') == 'unlink_file' && filter('filename') == $record['immagine']) {
    $dbo->update('mg_articoli', [
        'immagine' => null,
    ], [
        'id' => $id_record,
    ]);
} elseif (filter('op') == 'link_file' && filter('nome_allegato') == 'Immagine') {
    $dbo->update('mg_articoli', [
        'immagine' => $upload,
    ], [
        'id' => $id_record,
    ]);
}
