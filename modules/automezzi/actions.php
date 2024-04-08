<?php

include_once __DIR__.'/../../core.php';

use Carbon\Carbon;
use Modules\Articoli\Articolo;

switch (post('op')) {
    case 'update':
        $targa = post('targa');
        $nome = post('nome');
        $descrizione = post('descrizione');

        if ($dbo->fetchNum('SELECT targa FROM an_sedi WHERE targa='.prepare($targa).' AND NOT id='.prepare($id_record)) == 0) {
            $query = 'UPDATE an_sedi SET targa='.prepare($targa).', descrizione='.prepare($descrizione).', nome='.prepare($nome).' WHERE id='.prepare($id_record);
            if ($dbo->query($query)) {
                flash()->info(tr('Informazioni salvate correttamente!'));
            }
        } else {
            flash()->error(tr('Esiste già un automezzo con questa targa!'));
        }

        break;

        // Aggiunta automezzo
    case 'add':
        $targa = post('targa');
        $nome = post('nome');

        // Inserisco l'automezzo solo se non esiste un altro articolo con stesso targa
        if ($dbo->fetchNum('SELECT targa FROM an_sedi WHERE targa='.prepare($targa)) == 0) {
            $dbo->insert('an_sedi', [
                'idanagrafica' => setting('Azienda predefinita'),
                'nomesede' => $nome.' - '.$targa,
                'is_automezzo' => 1,
                'targa' => $targa,
                'nome' => $nome,
            ]);
            $id_record = $dbo->lastInsertedID();

            flash()->info(tr('Aggiunto un nuovo automezzo!'));
        } else {
            flash()->error(tr('Esiste già un automezzo con questa targa!'));
        }
        break;

        // Aggiunta tecnico
    case 'addtech':
        $idtecnico = post('idtecnico');
        $data_inizio = post('data_inizio');
        $data_fine = null;

        // Controllo sull'effettivo inserimento di una data di fine successiva a quella di inizio
        if (!empty(post('data_fine'))) {
            if (new DateTime(post('data_fine')) >= new DateTime($data_inizio)) {
                $data_fine = post('data_fine');
            }
        }
        $data_fine ??= '0000-00-00';

        // Inserisco il tecnico
        $dbo->insert('an_sedi_tecnici', [
            'idtecnico' => $idtecnico,
            'idsede' => $id_record,
            'data_inizio' => $data_inizio,
            'data_fine' => $data_fine,
        ]);

        flash()->info(tr('Collegato un nuovo tecnico!'));
        break;

        // Salvataggio tecnici collegati
    case 'savetech':
        $errors = 0;

        foreach (post('data_inizio') as $idautomezzotecnico => $data) {
            $data_inizio = post('data_inizio')[$idautomezzotecnico];
            $data_fine = null;

            // Controllo sull'effettivo inserimento di una data di fine successiva a quella di inizio
            if (!empty(post('data_fine')[$idautomezzotecnico])) {
                if (new DateTime(post('data_fine')[$idautomezzotecnico]) >= new DateTime($data_inizio)) {
                    $data_fine = post('data_fine')[$idautomezzotecnico];
                }
            }
            $data_fine ??= '0000-00-00';

            $dbo->update('an_sedi_tecnici', [
                'idtecnico' => $idtecnico,
                'idsede' => $id_record,
                'data_inizio' => $data_inizio,
                'data_fine' => $data_fine,
            ], ['id' => $idautomezzotecnico]);

            if (!$dbo->query($query)) {
                ++$errors;
            }
        }

        if ($errors == 0) {
            flash()->info(tr('Informazioni salvate correttamente!'));
        } else {
            flash()->error(tr('Errore durante il salvataggio del tecnico!'));
        }
        break;

        // Eliminazione associazione con tecnico
    case 'deltech':
        $idautomezzotecnico = post('id');

        $query = 'DELETE FROM an_sedi_tecnici WHERE id='.prepare($idautomezzotecnico);

        if ($dbo->query($query)) {
            flash()->info(tr('Tecnico rimosso!'));
        }
        break;

        // Aggiunta quantità nell'automezzo
    case 'addrow':
        $idarticolo = post('idarticolo');
        $qta = post('qta');

        $articolo = Articolo::find($idarticolo);
        $automezzo = $dbo->table('an_sedi')->where('id', $id_record)->first();

        // Registrazione del movimento verso la sede di destinazione
        $articolo->registra($qta, tr('Carico dal magazzino sull\'automezzo _SEDE_', ['_SEDE_' => $automezzo->nomesede]), Carbon::now(), 1, [
            'idsede' => $id_record,
        ]);

        // Registrazione del movimento dalla sede di origine
        $articolo->registra(-$qta, tr('Scarico nel magazzino dall\'automezzo  _SEDE_', ['_SEDE_' => $automezzo->nomesede]), Carbon::now(), 1, [
            'idsede' => 0,
        ]);

        flash()->info(tr("Caricato il magazzino dell'automezzo!"));
        break;

    case 'editrow':
        $idarticolo = post('idarticolo');

        $articolo = Articolo::find($idarticolo);
        $automezzo = $dbo->table('an_sedi')->where('id', $id_record)->first();

        $qta = post('qta') - $dbo->fetchOne('SELECT SUM(mg_movimenti.qta) AS qta FROM mg_movimenti WHERE mg_movimenti.idarticolo='.prepare($idarticolo).' AND mg_movimenti.idsede='.prepare($id_record))['qta'];

        // Registrazione del movimento verso la sede di destinazione
        $articolo->registra($qta, tr('Carico dal magazzino sull\'automezzo _SEDE_', ['_SEDE_' => $automezzo->nomesede]), Carbon::now(), 1, [
            'idsede' => $id_record,
        ]);

        // Registrazione del movimento dalla sede di origine
        $articolo->registra(-$qta, tr('Scarico nel magazzino dall\'automezzo  _SEDE_', ['_SEDE_' => $automezzo->nomesede]), Carbon::now(), 1, [
            'idsede' => 0,
        ]);

        flash()->info(tr("Caricato il magazzino dell'automezzo!"));
        break;

        // Spostamento scorta da automezzo a magazzino generale
    case 'moverow':
        $idarticolo = post('idarticolo');
        $idautomezzotecnico = post('idautomezzotecnico');

        $articolo = Articolo::find($idarticolo);
        $automezzo = $dbo->table('an_sedi')->where('id', $idautomezzotecnico)->first();
        $qta = $dbo->fetchOne('SELECT SUM(qta) AS qta FROM mg_movimenti WHERE idarticolo='.prepare($idarticolo).' AND idsede='.prepare($idautomezzotecnico))['qta'];

        // Registrazione del movimento verso la sede di destinazione
        $articolo->registra($qta, tr('Carico nel magazzino dall\'automezzo _SEDE_', ['_SEDE_' => $automezzo->nomesede]), Carbon::now(), 1, [
            'idsede' => 0,
        ]);

        // Registrazione del movimento dalla sede di origine
        $descrizione = tr('Scarico dall\'automezzo _SEDE_ nel magazzino', [
            '_SEDE_' => $automezzo->nomesede,
        ]);
        $articolo->registra(-$qta, $descrizione, Carbon::now(), 1, [
            'idsede' => $idautomezzotecnico,
        ]);

        break;

    case 'delete':
        $dbo->query('DELETE FROM `an_sedi` WHERE `id`='.prepare($id_record));

        flash()->info(tr('Automezzo eliminato e articoli riportati in magazzino!'));

        break;
}
