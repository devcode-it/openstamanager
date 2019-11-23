<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    // Aggiunta articolo
    case 'add':
        //Se non specifico il codice articolo lo imposto uguale all'id della riga
        if (empty(post('codice'))) {
            $codice = $dbo->fetchOne('SELECT (MAX(id)+1) as codice FROM mg_articoli')['codice'];
        } else {
            $codice = post('codice');
        }

        // Inserisco l'articolo e avviso se esiste un altro articolo con stesso codice.
        if ($n = $dbo->fetchNum('SELECT * FROM mg_articoli WHERE codice='.prepare($codice)) > 0) {
            flash()->warning(tr('Attenzione: il codice _CODICE_ è già stato utilizzato _N_ volta', [
                '_CODICE_' => $codice,
                '_N_' => $n,
            ]));
        }

        $dbo->insert('mg_articoli', [
            'codice' => $codice,
            'descrizione' => post('descrizione'),
            'id_categoria' => post('categoria'),
            'id_sottocategoria' => post('subcategoria'),
            'attivo' => 1,
        ]);
        $id_record = $dbo->lastInsertedID();

        if (isAjaxRequest()) {
            echo json_encode([
                'id' => $id_record,
                'text' => post('descrizione'),
                'data' => [
                    'descrizione' => post('descrizione'),
                ],
            ]);
        }

        flash()->info(tr('Aggiunto un nuovo articolo'));

        break;

    // Modifica articolo
    case 'update':
        $qta = post('qta');

        // Inserisco l'articolo e avviso se esiste un altro articolo con stesso codice.
        if ($n = $dbo->fetchNum('SELECT * FROM mg_articoli WHERE codice='.prepare(post('codice')).' AND id != '.prepare($id_record)) > 0) {
            flash()->warning(tr('Attenzione: il codice _CODICE_ è già stato utilizzato _N_ volta', [
                '_CODICE_' => post('codice'),
                '_N_' => $n,
            ]));
        }

        $articolo->codice = post('codice');
        $articolo->barcode = post('barcode');
        $articolo->descrizione = post('descrizione');
        $articolo->um = post('um');
        $articolo->id_categoria = post('categoria');
        $articolo->id_sottocategoria = post('subcategoria');
        $articolo->abilita_serial = post('abilita_serial');
        $articolo->threshold_qta = post('threshold_qta');
        $articolo->prezzo_vendita = post('prezzo_vendita');
        $articolo->prezzo_acquisto = post('prezzo_acquisto');
        $articolo->idconto_vendita = post('idconto_vendita');
        $articolo->idconto_acquisto = post('idconto_acquisto');
        $articolo->id_fornitore = post('id_fornitore');
        $articolo->idiva_vendita = post('idiva_vendita');
        $articolo->gg_garanzia = post('gg_garanzia');
        $articolo->servizio = post('servizio');
        $articolo->volume = post('volume');
        $articolo->peso_lordo = post('peso_lordo');

        $componente = post('componente_filename');
        $articolo->componente_filename = $componente;
        $articolo->attivo = post('attivo');
        $articolo->note = post('note');

        $articolo->save();

        // Leggo la quantità attuale per capire se l'ho modificata
        $old_qta = $record['qta'];
        $movimento = $qta - $old_qta;

        if ($movimento != 0) {
            $descrizione_movimento = post('descrizione_movimento');
            $data_movimento = post('data_movimento');

            $articolo->movimenta($movimento, $descrizione_movimento, $data_movimento);
        }

        // Salvataggio info componente (campo `contenuto`)
        if (!empty($componente)) {
            $contenuto = \Util\Ini::write(file_get_contents(DOCROOT.'/files/my_impianti/'.$componente), $post);

            $dbo->query('UPDATE mg_articoli SET contenuto='.prepare($contenuto).' WHERE id='.prepare($id_record));
        }else{
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
        $new->qta = 0;
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
