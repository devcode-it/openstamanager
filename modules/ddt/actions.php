<?php

include_once __DIR__.'/../../core.php';

use Modules\Anagrafiche\Anagrafica;
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\DDT\Components\Articolo;
use Modules\DDT\Components\Descrizione;
use Modules\DDT\Components\Riga;
use Modules\DDT\Components\Sconto;
use Modules\DDT\DDT;
use Modules\DDT\Tipo;

$module = Modules::get($id_module);

if ($module['name'] == 'Ddt di vendita') {
    $dir = 'entrata';
} else {
    $dir = 'uscita';
}

switch (post('op')) {
    case 'add':
        $idanagrafica = post('idanagrafica');
        $data = post('data');
        $id_tipo = post('idtipoddt');

        $anagrafica = Anagrafica::find($idanagrafica);
        $tipo = Tipo::find($id_tipo);

        $ddt = DDT::build($anagrafica, $tipo, $data);
        $id_record = $ddt->id;

        $ddt->idcausalet = post('idcausalet');
        $ddt->save();

        flash()->info(tr('Aggiunto ddt in _TYPE_ numero _NUM_!', [
            '_TYPE_' => $dir,
            '_NUM_' => $ddt->numero,
        ]));

        break;

    case 'update':
        $idstatoddt = post('idstatoddt');
        $idpagamento = post('idpagamento');
        $numero_esterno = post('numero_esterno');
        $id_anagrafica = post('idanagrafica');

        if ($dir == 'uscita') {
            $idrivalsainps = post('id_rivalsa_inps');
            $idritenutaacconto = post('id_ritenuta_acconto');
            $bollo = post('bollo');
        } else {
            $idrivalsainps = 0;
            $idritenutaacconto = 0;
            $bollo = 0;
        }

        $tipo_sconto = post('tipo_sconto_generico');
        $sconto = post('sconto_generico');

        // Leggo la descrizione del pagamento
        $query = 'SELECT descrizione FROM co_pagamenti WHERE id='.prepare($idpagamento);
        $rs = $dbo->fetchArray($query);
        $pagamento = $rs[0]['descrizione'];

        // Query di aggiornamento
        $dbo->update('dt_ddt', [
            'data' => post('data'),
            'numero_esterno' => $numero_esterno,
            'note' => post('note'),
            'note_aggiuntive' => post('note_aggiuntive'),

            'idstatoddt' => $idstatoddt,
            'idpagamento' => $idpagamento,
            'idconto' => post('idconto'),
            'idanagrafica' => $id_anagrafica,
            'idspedizione' => post('idspedizione'),
            'idcausalet' => post('idcausalet'),
            'idsede_partenza' => post('idsede_partenza'),
            'idsede_destinazione' => post('idsede_destinazione'),
            'idvettore' => post('idvettore'),
            'data_ora_trasporto' => post('data_ora_trasporto') ?: null;
            'idporto' => post('idporto'),
            'idaspettobeni' => post('idaspettobeni'),
            'idrivalsainps' => $idrivalsainps,
            'idritenutaacconto' => $idritenutaacconto,

            'n_colli' => post('n_colli'),
            'bollo' => 0,
            'rivalsainps' => 0,
            'ritenutaacconto' => 0,

            'id_documento_fe' => post('id_documento_fe'),
            'codice_cup' => post('codice_cup'),
            'codice_cig' => post('codice_cig'),
            'num_item' => post('num_item'),
        ], ['id' => $id_record]);

        $query = 'SELECT descrizione FROM dt_statiddt WHERE id='.prepare($idstatoddt);
        $rs = $dbo->fetchArray($query);

        // Ricalcolo inps, ritenuta e bollo (se l'ddt non è stato evaso)
        if ($dir == 'entrata') {
            if ($rs[0]['descrizione'] != 'Pagato') {
                ricalcola_costiagg_ddt($id_record);
            }
        } else {
            if ($rs[0]['descrizione'] != 'Pagato') {
                ricalcola_costiagg_ddt($id_record, $idrivalsainps, $idritenutaacconto, $bollo);
            }
        }

        aggiorna_sedi_movimenti('ddt', $id_record);

        // Controllo sulla presenza di DDT con lo stesso numero secondario
        $direzione = $ddt->direzione;
        if ($direzione == 'uscita' and !empty($numero_esterno)) {
            $count = DDT::where('numero_esterno', $numero_esterno)
                ->where('id', '!=', $id_record)
                ->where('idanagrafica', '=', $id_anagrafica)
                ->whereHas('tipo', function ($query) use ($direzione) {
                    $query->where('dir', '=', $direzione);
                })->count();
            if (!empty($count)) {
                flash()->warning(tr('Esiste già un DDT con lo stesso numero secondario e la stessa anagrafica collegata!'));
            }
        }

        flash()->info(tr('Ddt modificato correttamente!'));
        break;

    case 'manage_articolo':
        if (post('idriga') != null) {
            $articolo = Articolo::find(post('idriga'));
        } else {
            $originale = ArticoloOriginale::find(post('idarticolo'));
            $articolo = Articolo::build($ddt, $originale);
        }

        $articolo->descrizione = post('descrizione');
        $articolo->um = post('um') ?: null;

        $articolo->costo_unitario = post('costo_unitario') ?: 0;
        $articolo->setPrezzoUnitario(post('prezzo_unitario'), post('idiva'));
        $articolo->setSconto(post('sconto'), post('tipo_sconto'));

        try {
            $articolo->qta = post('qta');
        } catch (UnexpectedValueException $e) {
            flash()->error(tr('Alcuni serial number sono già stati utilizzati!'));
        }

        $articolo->save();

        if (post('idriga') != null) {
            flash()->info(tr('Articolo modificato!'));
        } else {
            flash()->info(tr('Articolo aggiunto!'));
        }

        // Ricalcolo inps, ritenuta e bollo
        ricalcola_costiagg_ddt($id_record);

        break;

    case 'manage_sconto':
        if (post('idriga') != null) {
            $sconto = Sconto::find(post('idriga'));
        } else {
            $sconto = Sconto::build($ddt);
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

        // Ricalcolo inps, ritenuta e bollo
        ricalcola_costiagg_ddt($id_record);

        break;

    case 'manage_riga':
        if (post('idriga') != null) {
            $riga = Riga::find(post('idriga'));
        } else {
            $riga = Riga::build($ddt);
        }

        $riga->descrizione = post('descrizione');
        $riga->um = post('um') ?: null;

        $riga->costo_unitario = post('costo_unitario') ?: 0;
        $riga->setPrezzoUnitario(post('prezzo_unitario'), post('idiva'));
        $riga->setSconto(post('sconto'), post('tipo_sconto'));

        $riga->qta = post('qta');

        $riga->save();

        if (post('idriga') != null) {
            flash()->info(tr('Riga modificata!'));
        } else {
            flash()->info(tr('Riga aggiunta!'));
        }

        // Ricalcolo inps, ritenuta e bollo
        ricalcola_costiagg_ddt($id_record);

        break;

    case 'manage_descrizione':
        if (post('idriga') != null) {
            $riga = Descrizione::find(post('idriga'));
        } else {
            $riga = Descrizione::build($ddt);
        }

        $riga->descrizione = post('descrizione');

        $riga->save();

        if (post('idriga') != null) {
            flash()->info(tr('Riga descrittiva modificata!'));
        } else {
            flash()->info(tr('Riga descrittiva aggiunta!'));
        }

        break;

    // Aggiunta di un ordine in ddt
    case 'add_ordine':
        $ordine = \Modules\Ordini\Ordine::find(post('id_documento'));

        // Creazione del ddt al volo
        if (post('create_document') == 'on') {
            $tipo = Tipo::where('dir', $dir)->first();

            $ddt = DDT::build($ordine->anagrafica, $tipo, post('data'));
            $ddt->idpagamento = $ordine->idpagamento;

            $ddt->id_documento_fe = $ordine->id_documento_fe;
            $ddt->codice_cup = $ordine->codice_cup;
            $ddt->codice_cig = $ordine->codice_cig;
            $ddt->num_item = $ordine->num_item;
            $ddt->idsede_destinazione = $ordine->idsede;

            $ddt->save();

            $id_record = $ddt->id;
        }

        $righe = $ordine->getRighe();
        foreach ($righe as $riga) {
            if (post('evadere')[$riga->id] == 'on' and !empty(post('qta_da_evadere')[$riga->id])) {
                $qta = post('qta_da_evadere')[$riga->id];

                $copia = $riga->copiaIn($ddt, $qta);

                // Aggiornamento seriali dalla riga dell'ordine
                if ($copia->isArticolo()) {
                    //$copia->movimenta($copia->qta);

                    $serials = is_array(post('serial')[$riga->id]) ? post('serial')[$riga->id] : [];

                    $copia->serials = $serials;
                }

                $copia->save();
            }
        }

        ricalcola_costiagg_ddt($id_record);

        flash()->info(tr('Ordine _NUM_ aggiunto!', [
            '_NUM_' => $ordine->numero,
        ]));

        break;

    // Scollegamento riga generica da ddt
    case 'delete_riga':
        $id_riga = post('idriga');
        $type = post('type');

        $riga = $ddt->getRiga($type, $id_riga);

        if (!empty($riga)) {
            try {
                $riga->delete();

                flash()->info(tr('Riga rimossa!'));
            } catch (InvalidArgumentException $e) {
                flash()->error(tr('Alcuni serial number sono già stati utilizzati!'));
            }
        }

        ricalcola_costiagg_ddt($id_record);

        break;

    // eliminazione ddt
    case 'delete':
        try {
            $ddt->delete();

            flash()->info(tr('Ddt eliminato!'));
        } catch (InvalidArgumentException $e) {
            flash()->error(tr('Sono stati utilizzati alcuni serial number nel documento: impossibile procedere!'));
        }

        break;

    case 'add_serial':
        $idriga = post('idriga');
        $idarticolo = post('idarticolo');

        $serials = (array) post('serial');
        foreach ($serials as $key => $value) {
            if (empty($value)) {
                unset($serials[$key]);
            }
        }

        $dbo->sync('mg_prodotti', ['id_riga_ddt' => $idriga, 'dir' => $dir, 'id_articolo' => $idarticolo], ['serial' => $serials]);

        break;

        case 'update_position':
            $orders = explode(',', $_POST['order']);
            $order = 0;

            foreach ($orders as $idriga) {
                $dbo->query('UPDATE `dt_righe_ddt` SET `order`='.prepare($order).' WHERE id='.prepare($idriga));
                ++$order;
            }

            break;
}

// Aggiornamento stato degli ordini presenti in questa fattura in base alle quantità totali evase
if (!empty($id_record) && setting('Cambia automaticamente stato ordini fatturati')) {
    $rs = $dbo->fetchArray('SELECT idordine FROM dt_righe_ddt WHERE idddt='.prepare($id_record));

    for ($i = 0; $i < sizeof($rs); ++$i) {
        $dbo->query('UPDATE or_ordini SET idstatoordine=(SELECT id FROM or_statiordine WHERE descrizione="'.get_stato_ordine($rs[$i]['idordine']).'") WHERE id = '.prepare($rs[$i]['idordine']));
    }
}
