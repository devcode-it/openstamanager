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
            $query = 'UPDATE an_sedi SET targa='.prepare($targa).', descrizione='.prepare($descrizione).', nome='.prepare($nome).', nome_sede='.prepare($nome.' - '.$targa).' WHERE id='.prepare($id_record);
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
                'id_anagrafica' => setting('Azienda predefinita'),
                'nome_sede' => $nome.' - '.$targa,
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

        // Aggiunta quantità nell'automezzo
    case 'addrow':
        $id_articolo = post('id_articolo');
        $qta = post('qta');

        $articolo = Articolo::find($id_articolo);
        $automezzo = $dbo->table('an_sedi')->where('id', $id_record)->first();

        // Registrazione del movimento verso la sede di destinazione
        $articolo->registra($qta, tr('Carico dal magazzino sull\'automezzo _SEDE_', ['_SEDE_' => $automezzo->nome_sede]), Carbon::now(), 1, [
            'id_sede' => $id_record,
        ]);

        // Registrazione del movimento dalla sede di origine
        $articolo->registra(-$qta, tr('Scarico nel magazzino dall\'automezzo  _SEDE_', ['_SEDE_' => $automezzo->nome_sede]), Carbon::now(), 1, [
            'id_sede' => 0,
        ]);

        flash()->info(tr("Caricato il magazzino dell'automezzo!"));
        break;

    case 'editrow':
        $id_articolo = post('id_articolo');

        $articolo = Articolo::find($id_articolo);
        $automezzo = $dbo->table('an_sedi')->where('id', $id_record)->first();

        $qta = post('qta') - $dbo->fetchOne('SELECT SUM(mg_movimenti.qta) AS qta FROM mg_movimenti WHERE mg_movimenti.id_articolo='.prepare($id_articolo).' AND mg_movimenti.id_sede='.prepare($id_record))['qta'];

        // Registrazione del movimento verso la sede di destinazione
        $articolo->registra($qta, tr('Carico dal magazzino sull\'automezzo _SEDE_', ['_SEDE_' => $automezzo->nome_sede]), Carbon::now(), 1, [
            'id_sede' => $id_record,
        ]);

        // Registrazione del movimento dalla sede di origine
        $articolo->registra(-$qta, tr('Scarico nel magazzino dall\'automezzo  _SEDE_', ['_SEDE_' => $automezzo->nome_sede]), Carbon::now(), 1, [
            'id_sede' => 0,
        ]);

        flash()->info(tr("Caricato il magazzino dell'automezzo!"));
        break;

        // Spostamento scorta da automezzo a magazzino generale
    case 'moverow':
        $id_articolo = post('id_articolo');
        $idautomezzotecnico = post('idautomezzotecnico');

        $articolo = Articolo::find($id_articolo);
        $automezzo = $dbo->table('an_sedi')->where('id', $idautomezzotecnico)->first();
        $qta = $dbo->fetchOne('SELECT SUM(qta) AS qta FROM mg_movimenti WHERE id_articolo='.prepare($id_articolo).' AND id_sede='.prepare($idautomezzotecnico))['qta'];

        // Registrazione del movimento verso la sede di destinazione
        $articolo->registra($qta, tr('Carico nel magazzino dall\'automezzo _SEDE_', ['_SEDE_' => $automezzo->nome_sede]), Carbon::now(), 1, [
            'id_sede' => 0,
        ]);

        // Registrazione del movimento dalla sede di origine
        $descrizione = tr('Scarico dall\'automezzo _SEDE_ nel magazzino', [
            '_SEDE_' => $automezzo->nome_sede,
        ]);
        $articolo->registra(-$qta, $descrizione, Carbon::now(), 1, [
            'id_sede' => $idautomezzotecnico,
        ]);

        break;

        // Aggiunta viaggio
    case 'addviaggio':
        $id_tecnico = post('id_tecnico');
        $data_inizio = post('data_inizio');
        $data_fine = post('data_fine') ?: null;
        $km_inizio = post('km_inizio');
        $km_fine = post('km_fine');
        $destinazione = post('destinazione');
        $motivazione = post('motivazione');

        // Inserisco il viaggio
        $dbo->insert('an_automezzi_viaggi', [
            'id_sede' => $id_record,
            'id_tecnico' => $id_tecnico,
            'data_inizio' => $data_inizio,
            'data_fine' => $data_fine,
            'km_inizio' => $km_inizio,
            'km_fine' => $km_fine,
            'destinazione' => $destinazione,
            'motivazione' => $motivazione,
        ]);

        flash()->info(tr('Viaggio aggiunto al registro!'));

        break;

        // Modifica viaggio
    case 'editviaggio':
        $id_viaggio = post('id_viaggio');
        $id_tecnico = post('id_tecnico');
        $data_inizio = post('data_inizio');
        $data_fine = post('data_fine') ?: null;
        $km_inizio = post('km_inizio');
        $km_fine = post('km_fine');
        $destinazione = post('destinazione');
        $motivazione = post('motivazione');

        // Aggiorno il viaggio
        $dbo->update('an_automezzi_viaggi', [
            'id_tecnico' => $id_tecnico,
            'data_inizio' => $data_inizio,
            'data_fine' => $data_fine,
            'km_inizio' => $km_inizio,
            'km_fine' => $km_fine,
            'destinazione' => $destinazione,
            'motivazione' => $motivazione,
        ], ['id' => $id_viaggio]);

        flash()->info(tr('Viaggio aggiornato!'));

        break;

        // Eliminazione viaggio
    case 'delviaggio':
        $id_viaggio = post('id');

        if ($dbo->delete('an_automezzi_viaggi', ['id' => $id_viaggio])) {
            flash()->info(tr('Viaggio rimosso dal registro!'));
        }

        break;

        // Aggiunta rifornimento
    case 'addrifornimento':
        $id_viaggio = post('id_viaggio');
        $data = post('data');
        $luogo = post('luogo');
        $id_carburante = post('id_carburante');
        $quantita = post('quantita');
        $costo = post('costo');
        $id_gestore = post('id_gestore');
        $codice_carta = post('codice_carta');
        $km = post('km');

        // Inserisco il rifornimento
        $dbo->insert('an_automezzi_rifornimenti', [
            'id_viaggio' => $id_viaggio,
            'data' => $data,
            'luogo' => $luogo,
            'id_carburante' => $id_carburante,
            'quantita' => $quantita,
            'costo' => $costo,
            'id_gestore' => $id_gestore,
            'codice_carta' => $codice_carta,
            'km' => $km,
        ]);

        flash()->info(tr('Rifornimento aggiunto!'));

        break;

        // Modifica rifornimento
    case 'editrifornimento':
        $idrifornimento = post('idrifornimento');
        $data = post('data');
        $luogo = post('luogo');
        $id_carburante = post('id_carburante');
        $quantita = post('quantita');
        $costo = post('costo');
        $id_gestore = post('id_gestore');
        $codice_carta = post('codice_carta');
        $km = post('km');

        // Aggiorno il rifornimento
        $dbo->update('an_automezzi_rifornimenti', [
            'data' => $data,
            'luogo' => $luogo,
            'id_carburante' => $id_carburante,
            'quantita' => $quantita,
            'costo' => $costo,
            'id_gestore' => $id_gestore,
            'codice_carta' => $codice_carta,
            'km' => $km,
        ], ['id' => $idrifornimento]);

        flash()->info(tr('Rifornimento aggiornato!'));

        break;

        // Eliminazione rifornimento
    case 'delrifornimento':
        $idrifornimento = post('id');

        if ($dbo->delete('an_automezzi_rifornimenti', ['id' => $idrifornimento])) {
            flash()->info(tr('Rifornimento rimosso!'));
        }

        break;

        // Firma viaggio
    case 'firma_viaggio':
        $id_viaggio = post('id_viaggio');
        $firma_base64 = post('firma_base64');

        if (empty($firma_base64)) {
            flash()->error(tr('Firma mancante!'));
            break;
        }

        if (is_writable(Uploads::getDirectory($id_module))) {
            if (post('firma_base64') != '') {
                // Salvataggio firma
                $data = explode(',', post('firma_base64'));
                $img = getImageManager()->read(base64_decode($data[1]));
                $img->resize(680, 202, function ($constraint) {
                    $constraint->aspectRatio();
                });

                if (setting('Sistema di firma') == 'Tavoletta Wacom') {
                    $img->brightness((float) setting('Luminosità firma Wacom'));
                    $img->contrast((float) setting('Contrasto firma Wacom'));
                }
                $encoded_image = $img->toJpeg();
                $file_content = $encoded_image->toString();

                // Upload del file in zz_files
                $upload = Uploads::upload($file_content, [
                    'name' => 'firma.jpg',
                    'category' => 'Firme',
                    'id_module' => $id_module,
                    'id_record' => $id_record,
                    'key' => 'signature_viaggio:'.$id_viaggio,
                ]);

                if (empty($upload)) {
                    flash()->error(tr('Errore durante il caricamento della firma!'));
                } else {
                    flash()->info(tr('Firma salvata correttamente.'));

                    $dbo->update('an_automezzi_viaggi', [
                        'firma_data' => date('Y-m-d H:i:s'),
                        'firma_nome' => post('firma_nome'),
                    ], ['id' => $id_viaggio]);
                }
            } else {
                flash()->error(tr('Errore durante il salvataggio della firma.').'<br>'.tr('La firma risulta vuota.'));
            }
        } else {
            flash()->error(tr("Non è stato possibile creare la cartella _DIRECTORY_ per salvare l'immagine della firma.", [
                '_DIRECTORY_' => '<b>'.Uploads::getDirectory($id_module).'</b>',
            ]));
        }

        break;

    case 'delete':
        $dbo->delete('an_sedi', ['id' => $id_record]);

        flash()->info(tr('Automezzo eliminato e articoli riportati in magazzino!'));

        break;
}
