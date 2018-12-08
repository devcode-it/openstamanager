<?php

include_once __DIR__.'/../../core.php';

$op = post('op');

$upload_dir = $docroot.'/files/'.Modules::get('MyImpianti')['directory'];

switch ($op) {
    // Aggiorno informazioni di base impianto
    case 'update':
        $matricola = post('matricola');

        if (!empty($matricola)) {
            $dbo->update('my_impianti', [
                'idanagrafica' => post('idanagrafica'),
                'nome' => post('nome'),
                'matricola' => $matricola,
                'id_categoria' => post('id_categoria') ?: null,
                'descrizione' => post('descrizione'),
                'idsede' => post('idsede'),
                'data' => post('data'),
                'proprietario' => post('proprietario'),
                'palazzo' => post('palazzo'),
                'ubicazione' => post('ubicazione'),
                'idtecnico' => post('idtecnico'),
                'scala' => post('scala'),
                'piano' => post('piano'),
                'interno' => post('interno'),
                'occupante' => post('occupante'),
            ], ['id' => $id_record]);

            flash()->info(tr('Informazioni salvate correttamente!'));

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
                    $dbo->update('my_impianti', [
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

                $dbo->update('my_impianti', [
                    'immagine' => null,
                ], [
                    'id' => $id_record,
                ]);
            }
        }
        break;

    // Aggiungo impianto
    case 'add':
        $matricola = post('matricola');
        $idanagrafica = post('idanagrafica');
        $nome = post('nome');
        $idtecnico = post('idtecnico');

        if (!empty($matricola)) {
            $dbo->query('INSERT INTO my_impianti(matricola, idanagrafica, nome, data, idtecnico) VALUES ('.prepare($matricola).', '.prepare($idanagrafica).', '.prepare($nome).', NOW(), '.prepare($idtecnico).')');

            $id_record = $dbo->lastInsertedID();

            if (isAjaxRequest() && post('source') != '') {
                echo json_encode(['id' => $id_record, 'text' => $matricola.' - '.$nome]);
            }

            flash()->info(tr('Aggiunto nuovo impianto!'));
        }

        break;

    // Carica i campi da compilare del componente
    case 'load_componente':
        include_once Modules::filepath('MyImpianti', 'modutil.php');

        $filename = post('filename');
        $idarticolo = post('idarticolo');

        // Se è stato specificato un idarticolo, carico il file .ini dal campo `contenuto` di quell'idarticolo
        $rs = $dbo->fetchArray('SELECT contenuto, componente_filename FROM mg_articoli WHERE id='.prepare($idarticolo));

        // Se i campi da caricare sono del componente già salvato leggo dal campo `contenuto`...
        if ($rs[0]['componente_filename'] == $filename) {
            $contenuto = $rs[0]['contenuto'];
        }

        // ...altrimenti carico dal file .ini
        elseif (file_exists($docroot.'/files/my_impianti/'.$filename)) {
            $contenuto = file_get_contents($docroot.'/files/my_impianti/'.$filename);
        }

        genera_form_componente($contenuto);

        break;

    // Rimuovo impianto e scollego tutti i suoi componenti
    case 'delete':
        $dbo->query('DELETE FROM my_impianti WHERE id='.prepare($id_record));

        flash()->info(tr('Impianto e relativi componenti eliminati!'));
        break;
}

// Operazioni aggiuntive per l'immagine
if (filter('op') == 'unlink_file' && filter('filename') == $record['immagine']) {
    $dbo->update('my_impianti', [
        'immagine' => null,
    ], [
        'id' => $id_record,
    ]);
} elseif (filter('op') == 'link_file' && filter('nome_allegato') == 'Immagine') {
    $dbo->update('my_impianti', [
        'immagine' => $upload,
    ], [
        'id' => $id_record,
    ]);
}
