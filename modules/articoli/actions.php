<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    // Aggiunta articolo
    case 'add':
        $codice = post('codice');

        // Inserisco l'articolo solo se non esiste un altro articolo con stesso codice
        if ($dbo->fetchNum('SELECT * FROM mg_articoli WHERE codice='.prepare($codice)) == 0) {
            $dbo->insert('mg_articoli', [
                'codice' => $codice,
                'descrizione' => post('descrizione'),
                'id_categoria' => post('categoria'),
                'id_sottocategoria' => post('subcategoria'),
                'attivo' => 1,
            ]);
            $id_record = $dbo->lastInsertedID();

            flash()->info(tr('Aggiunto un nuovo articolo!'));
        } else {
            flash()->error(tr('Esiste già un articolo con questo codice!'));
        }

        break;

    // Modifica articolo
    case 'update':
        $componente = post('componente_filename');
        $qta = post('qta');

        $dbo->update('mg_articoli', [
            'codice' => post('codice'),
            'descrizione' => post('descrizione'),
            'um' => post('um'),
            'id_categoria' => post('categoria'),
            'id_sottocategoria' => post('subcategoria'),
            'abilita_serial' => post('abilita_serial'),
            'threshold_qta' => post('threshold_qta'),
            'prezzo_vendita' => post('prezzo_vendita'),
            'prezzo_acquisto' => post('prezzo_acquisto'),
            'idconto_vendita' => post('idconto_vendita'),
            'idconto_acquisto' => post('idconto_acquisto'),
            'idiva_vendita' => post('idiva_vendita'),
            'gg_garanzia' => post('gg_garanzia'),
            'servizio' => post('servizio'),
            'volume' => post('volume'),
            'peso_lordo' => post('peso_lordo'),
            'componente_filename' => $componente,
            'attivo' => post('attivo'),
            'note' => post('note'),
        ], ['id' => $id_record]);

        // Leggo la quantità attuale per capire se l'ho modificata
        $old_qta = $record['qta'];
        $movimento = $qta - $old_qta;

        if ($movimento != 0) {
            $descrizione_movimento = post('descrizione_movimento');
            $data_movimento = post('data_movimento');

            add_movimento_magazzino($id_record, $movimento, [], $descrizione_movimento, $data_movimento);
        }

        // Salvataggio info componente (campo `contenuto`)
        if (!empty($componente)) {
            $contenuto = \Util\Ini::write(file_get_contents($docroot.'/files/my_impianti/'.$componente), $post);

            $dbo->query('UPDATE mg_articoli SET contenuto='.prepare($contenuto).' WHERE id='.prepare($id_record));
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
                flash()->warning(tr('Errore durante il caricamento del file in _DIR_!', [
                    '_DIR_' => $upload_dir,
                ]));
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

    // Aggiunta prodotto
    case 'addprodotto':
        // Per i 3 campi (lotto, serial, altro) leggo i numeri di partenza e arrivo e creo le combinazioni scelte

        // Lotto
        $lotto__start = post('lotto_start');
        $lotto__end = post('lotto_end');
        preg_match("/(.*?)([\d]*$)/", $lotto__start, $m);
        $lotto_start = intval($m[2]);
        preg_match("/(.*?)([\d]*$)/", $lotto__end, $m);
        $lotto_end = intval($m[2]);
        $n_lotti = abs($lotto_end - $lotto_start) + 1;
        $lotto_prefix = str_replace($lotto_end, '', $lotto__end);
        $lotto_pad_length = strlen($lotto__end) - strlen($lotto_prefix);

        // Serial
        $serial__start = post('serial_start');
        $serial__end = post('serial_end');
        preg_match("/(.*?)([\d]*$)/", $serial__start, $m);
        $serial_start = intval($m[2]);
        preg_match("/(.*?)([\d]*$)/", $serial__end, $m);
        $serial_end = intval($m[2]);
        $n_serial = abs($serial_end - $serial_start) + 1;
        $serial_prefix = str_replace($serial_end, '', $serial__end);
        $serial_pad_length = strlen($serial__end) - strlen($serial_prefix);

        // Altro
        $altro__start = post('altro_start');
        $altro__end = post('altro_end');
        preg_match("/(.*?)([\d]*$)/", $altro__start, $m);
        $altro_start = intval($m[2]);
        preg_match("/(.*?)([\d]*$)/", $altro__end, $m);
        $altro_end = intval($m[2]);
        $n_altro = abs($altro_end - $altro_start) + 1;
        $altro_prefix = str_replace($altro_end, '', $altro__end);
        $altro_pad_length = strlen($altro__end) - strlen($altro_prefix);

        $n_prodotti = $n_lotti * $n_serial * $n_altro;

        // Creo la query per le combinazioni prodotto con ogni combinazione
        $query = 'INSERT INTO mg_prodotti(id_articolo, lotto, serial, altro) VALUES';

        // Contatore prodotti da inserire
        $c = 0;

        // Combinazione con "Lotto"
        for ($l = 0; $l < $n_lotti; ++$l) {
            // Combinazione con "Serial"
            for ($s = 0; $s < $n_serial; ++$s) {
                // Combinazione con "Altro"
                for ($a = 0; $a < $n_altro; ++$a) {
                    $insert = '('.prepare($id_record).', |lotto|, |serial|, |altro|)';

                    $this_lotto = ($lotto__start != '') ? $lotto_prefix.(str_pad($lotto_start + $l, $lotto_pad_length, '0', STR_PAD_LEFT)) : '';
                    $insert = str_replace('|lotto|', 'NULL', $insert); // prepare($this_lotto)

                    $this_serial = ($serial__start != '') ? $serial_prefix.(str_pad($serial_start + $s, $serial_pad_length, '0', STR_PAD_LEFT)) : '';
                    $insert = str_replace('|serial|', prepare($this_serial), $insert);

                    $this_altro = ($altro__start != '') ? $altro_prefix.(str_pad($altro_start + $a, $altro_pad_length, '0', STR_PAD_LEFT)) : '';
                    $insert = str_replace('|altro|', 'NULL', $insert); // prepare($this_altro)

                    // Verifico che questa combinazione non esista già
                    $np = $dbo->fetchNum('SELECT id FROM mg_prodotti WHERE id_articolo='.prepare($id_record).' AND serial='.prepare($this_serial));
                    if ($np == 0) {
                        $query .= $insert.', ';
                        ++$c;
                    }
                }
            }
        }
        $query .= '.';

        // Rimuovo "), ."
        $query = str_replace('), .', ')', $query);

        // Eseguo l'inserimento!!!
        if ($c > 0) {
            if ($dbo->query($query)) {
                // Movimento il magazzino se l'ho specificato nelle impostazioni
                if (setting("Movimenta il magazzino durante l'inserimento o eliminazione dei lotti/serial number")) {
                    add_movimento_magazzino($id_record, $c, [], tr('Carico magazzino con serial da _SERIAL_INIZIO_ a _SERIAL_FINE_', [
                        '_SERIAL_INIZIO_' => $serial__start,
                        '_SERIAL_FINE_' => $serial__end,
                    ]));
                }

                flash()->info(tr('Aggiunti _NUM_ prodotti!', [
                    '_NUM_' => $c,
                ]));
            } else {
                flash()->error(tr("Errore durante l'inserimento!"));
            }
        }

        if ($c != $n_prodotti) {
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
                add_movimento_magazzino($id_record, -1, [], tr('Eliminazione dal magazzino del prodotto con serial _SERIAL_', [
                    '_SERIAL_' => $rs[0]['serial'],
                ]));
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
        // Fix per i seriali utilizzati
        $dbo->query('UPDATE mg_prodotti SET id_articolo = NULL WHERE id_articolo='.prepare($id_record));

        $dbo->query('DELETE FROM mg_articoli WHERE id='.prepare($id_record));
        $dbo->query('DELETE FROM mg_movimenti WHERE idarticolo='.prepare($id_record));
        //$dbo->query('DELETE FROM mg_prodotti WHERE id_articolo='.prepare($id_record));
        $dbo->query('DELETE FROM mg_articoli_automezzi WHERE idarticolo='.prepare($id_record));

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
