<?php

include_once __DIR__.'/../../core.php';

use Modules\Anagrafiche\Anagrafica;
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Preventivi\Components\Articolo;
use Modules\Preventivi\Components\Descrizione;
use Modules\Preventivi\Components\Riga;
use Modules\Preventivi\Components\Sconto;
use Modules\Preventivi\Preventivo;
use Modules\TipiIntervento\Tipo as TipoSessione;

switch (post('op')) {
    case 'add':
        $idanagrafica = post('idanagrafica');
        $nome = post('nome');
        $idtipointervento = post('idtipointervento');

        $anagrafica = Anagrafica::find($idanagrafica);
        $tipo = TipoSessione::find($idtipointervento);

        $preventivo = Preventivo::build($anagrafica, $tipo, $nome);
        $id_record = $preventivo->id;

        flash()->info(tr('Aggiunto preventivo numero _NUM_!', [
            '_NUM_' => $preventivo['numero'],
        ]));

        break;

    case 'update':
        if (isset($id_record)) {
            $preventivo->idstato = post('idstato');
            $preventivo->nome = post('nome');
            $preventivo->idanagrafica = post('idanagrafica');
            $preventivo->idsede = post('idsede');
            $preventivo->idagente = post('idagente');
            $preventivo->idreferente = post('idreferente');
            $preventivo->idpagamento = post('idpagamento');
            $preventivo->idporto = post('idporto');
            $preventivo->tempi_consegna = post('tempi_consegna');
            $preventivo->numero = post('numero');
            $preventivo->data_bozza = post('data_bozza');
            $preventivo->data_accettazione = post('data_accettazione');
            $preventivo->data_rifiuto = post('data_rifiuto');
            $preventivo->data_conclusione = post('data_conclusione');
            $preventivo->esclusioni = post('esclusioni');
            $preventivo->descrizione = post('descrizione');
            $preventivo->id_documento_fe = post('id_documento_fe');
            $preventivo->num_item = post('num_item');
            $preventivo->codice_cig = post('codice_cig');
            $preventivo->codice_cup = post('codice_cup');
            $preventivo->validita = post('validita');
            $preventivo->idtipointervento = post('idtipointervento');
            $preventivo->idiva = post('idiva');

            $preventivo->save();

            flash()->info(tr('Preventivo modificato correttamente!'));
        }

        break;

    // Duplica preventivo
    case 'copy':
        $new = $preventivo->replicate();
        $new->numero = Preventivo::getNextNumero();
        $new->idstato = 1;
        $new->save();

        $new->master_revision = $new->id;
        $new->save();

        $id_record = $new->id;

        $righe = $preventivo->getRighe();
        foreach ($righe as $riga) {
            $new_riga = $riga->replicate();
            $new_riga->setParent($new);

            $new_riga->qta_evasa = 0;
            $new_riga->save();
        }

        flash()->info(tr('Preventivo duplicato correttamente!'));
    break;

    case 'addintervento':
        if (post('idintervento') !== null) {
            // Selezione costi da intervento
            $idintervento = post('idintervento');
            $rs = $dbo->fetchArray('SELECT * FROM in_interventi WHERE id='.prepare($idintervento));
            $costo_km = $rs[0]['prezzo_km_unitario'];
            $costo_orario = $rs[0]['prezzo_ore_unitario'];

            $dbo->update('in_interventi', [
                'id_preventivo' => $id_record,
            ], ['id' => $idintervento]);

            // Imposto il preventivo nello stato "In lavorazione" se inizio ad aggiungere interventi
            $dbo->query("UPDATE `co_preventivi` SET idstato=(SELECT `id` FROM `co_statipreventivi` WHERE `descrizione`='In lavorazione') WHERE `id`=".prepare($id_record));

            flash()->info(tr('Intervento _NUM_ aggiunto!', [
                '_NUM_' => $rs[0]['codice'],
            ]));
        }
        break;

    // Scollegamento intervento da preventivo
    case 'unlink':
        if (isset($_GET['idpreventivo']) && isset($_GET['idintervento'])) {
            $idintervento = get('idintervento');

            $dbo->update('in_interventi', [
                'id_preventivo' => null,
            ], ['id' => $idintervento]);

            flash()->info(tr('Intervento _NUM_ rimosso!', [
                '_NUM_' => $idintervento,
            ]));
        }
        break;

    // Eliminazione preventivo
    case 'delete':
        $preventivo->delete();

        flash()->info(tr('Preventivo eliminato!'));

        break;

    case 'manage_articolo':
        if (post('idriga') != null) {
            $articolo = Articolo::find(post('idriga'));
        } else {
            $originale = ArticoloOriginale::find(post('idarticolo'));
            $articolo = Articolo::build($preventivo, $originale);
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
            $sconto = Sconto::build($preventivo);
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
            $riga = Riga::build($preventivo);
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
            $riga = Descrizione::build($preventivo);
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

    case 'add_revision':
        //Copio il preventivo
        $rs_preventivo = $dbo->fetchArray("SELECT * FROM co_preventivi WHERE id='".$id_record."'");

        //Tolgo il flag default_revision da tutte le revisioni e dal record_principale
        $dbo->query('UPDATE co_preventivi SET default_revision=0 WHERE master_revision='.prepare($rs_preventivo[0]['master_revision']));

        $preventivo = [
            'numero' => $rs_preventivo[0]['numero'],
            'nome' => $rs_preventivo[0]['nome'],
            'idagente' => $rs_preventivo[0]['idagente'],
            'data_bozza' => $rs_preventivo[0]['data_bozza'],
            'data_accettazione' => $rs_preventivo[0]['data_accettazione'],
            'data_rifiuto' => $rs_preventivo[0]['data_rifiuto'],
            'data_conclusione' => $rs_preventivo[0]['data_conclusione'],
            'data_pagamento' => $rs_preventivo[0]['data_pagamento'],
            'budget' => $rs_preventivo[0]['budget'],
            'descrizione' => $rs_preventivo[0]['descrizione'],
            'idstato' => $rs_preventivo[0]['idstato'],
            'validita' => $rs_preventivo[0]['validita'],
            'tempi_consegna' => $rs_preventivo[0]['tempi_consegna'],
            'idanagrafica' => $rs_preventivo[0]['idanagrafica'],
            'esclusioni' => $rs_preventivo[0]['esclusioni'],
            'idreferente' => $rs_preventivo[0]['idreferente'],
            'idpagamento' => $rs_preventivo[0]['idpagamento'],
            'idporto' => $rs_preventivo[0]['idporto'],
            'idtipointervento' => $rs_preventivo[0]['idtipointervento'],
            'idiva' => $rs_preventivo[0]['idiva'],
            'costo_diritto_chiamata' => $rs_preventivo[0]['costo_diritto_chiamata'],
            'ore_lavoro' => $rs_preventivo[0]['ore_lavoro'],
            'costo_orario' => $rs_preventivo[0]['costo_orario'],
            'costo_km' => $rs_preventivo[0]['costo_km'],
            'master_revision' => $rs_preventivo[0]['master_revision'],
            'default_revision' => '1',
        ];

        $dbo->insert('co_preventivi', $preventivo);
        $id_record_new = $dbo->lastInsertedID();

        $rs_righe_preventivo = $dbo->fetchArray('SELECT * FROM co_righe_preventivi WHERE idpreventivo='.prepare($id_record));

        for ($i = 0; $i < sizeof($rs_righe_preventivo); ++$i) {
            $righe_preventivo = [
                'idpreventivo' => $id_record_new,
                'idarticolo' => $rs_righe_preventivo[$i]['idarticolo'],
                'is_descrizione' => $rs_righe_preventivo[$i]['is_descrizione'],
                'idiva' => $rs_righe_preventivo[$i]['idiva'],
                'desc_iva' => $rs_righe_preventivo[$i]['desc_iva'],
                'iva' => $rs_righe_preventivo[$i]['iva'],
                'iva_indetraibile' => $rs_righe_preventivo[$i]['iva_indetraibile'],
                'descrizione' => $rs_righe_preventivo[$i]['descrizione'],
                'subtotale' => $rs_righe_preventivo[$i]['subtotale'],
                'sconto' => $rs_righe_preventivo[$i]['sconto'],
                'sconto_unitario' => $rs_righe_preventivo[$i]['sconto_unitario'],
                'tipo_sconto' => $rs_righe_preventivo[$i]['tipo_sconto'],
                'um' => $rs_righe_preventivo[$i]['um'],
                'qta' => $rs_righe_preventivo[$i]['qta'],
                'order' => $rs_righe_preventivo[$i]['order'],
            ];
            $dbo->insert('co_righe_preventivi', $righe_preventivo);
        }

        $id_record = $id_record_new;

        flash()->info(tr('Aggiunta nuova revisione!'));
        break;

    case 'update_position':
        $orders = explode(',', $_POST['order']);
        $order = 0;

        foreach ($orders as $idriga) {
            $dbo->query('UPDATE `co_righe_preventivi` SET `order`='.prepare($order).' WHERE id='.prepare($idriga));
            ++$order;
        }

        break;
}
