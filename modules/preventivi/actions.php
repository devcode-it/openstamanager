<?php

include_once __DIR__.'/../../core.php';

use Carbon\Carbon;
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
        $data_bozza = post('data_bozza');
        $id_sede = post('idsede');

        $anagrafica = Anagrafica::find($idanagrafica);
        $tipo = TipoSessione::find($idtipointervento);

        $preventivo = Preventivo::build($anagrafica, $tipo, $nome, $data_bozza, $id_sede);
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

            // Informazioni sulle date del documento
            $preventivo->data_bozza = post('data_bozza');
            $preventivo->data_rifiuto = post('data_rifiuto');

            // Dati relativi alla validità del documento
            $preventivo->validita = post('validita');
            $preventivo->tipo_validita = post('tipo_validita');
            $preventivo->data_accettazione = post('data_accettazione');
            $preventivo->data_conclusione = post('data_conclusione');

            $preventivo->esclusioni = post('esclusioni');
            $preventivo->descrizione = post('descrizione');
            $preventivo->id_documento_fe = post('id_documento_fe');
            $preventivo->num_item = post('num_item');
            $preventivo->codice_cig = post('codice_cig');
            $preventivo->codice_cup = post('codice_cup');
            $preventivo->idtipointervento = post('idtipointervento');
            $preventivo->idiva = post('idiva');

            $preventivo->save();

            flash()->info(tr('Preventivo modificato correttamente!'));
        }

        break;

    // Duplica preventivo
    case 'copy':
        // Copia del preventivo
        $new = $preventivo->replicate();
        $new->numero = Preventivo::getNextNumero(Carbon::now());
        $new->data_bozza = Carbon::now();
        $new->data_conclusione = Carbon::now()->addMonth();
        $new->stato = 'Bozza';
        $new->save();

        $new->master_revision = $new->id;
        $new->save();

        $id_record = $new->id;

        // Copia delle righe
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
        try {
            $preventivo->delete();

            flash()->info(tr('Preventivo eliminato!'));
        } catch (InvalidArgumentException $e) {
            flash()->error(tr('Sono stati utilizzati alcuni serial number nel documento: impossibile procedere!'));
        }

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
            $articolo = Articolo::build($preventivo, $originale);
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
            $articolo = Articolo::build($preventivo, $originale);
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
        $id_riga = post('riga_id');
        $type = post('riga_type');

        $riga = $preventivo->getRiga($type, $id_riga);

        if (!empty($riga)) {
            $riga->delete();

            flash()->info(tr('Riga eliminata!'));
        }

        break;

    case 'add_revision':
        // Rimozione flag default_revision dal record principale e dalle revisioni
        $dbo->query('UPDATE co_preventivi SET default_revision=0 WHERE master_revision = '.prepare($preventivo->master_revision));

        // Copia del preventivo
        $new = $preventivo->replicate();
        $new->stato = 'Bozza';
        $new->save();

        $new->default_revision = 1;
        $new->numero_revision = $new->ultima_revisione + 1;
        $new->save();

        $id_record = $new->id;

        // Copia delle righe
        $righe = $preventivo->getRighe();
        foreach ($righe as $riga) {
            $new_riga = $riga->replicate();
            $new_riga->setParent($new);

            $new_riga->qta_evasa = 0;
            $new_riga->save();
        }

        flash()->info(tr('Aggiunta nuova revisione!'));
        break;

    case 'update_position':
        $order = explode(',', post('order', true));

        foreach ($order as $i => $id_riga) {
            $dbo->query('UPDATE `co_righe_preventivi` SET `order` = '.prepare($i).' WHERE id='.prepare($id_riga));
        }

        break;
}
