<?php

include_once __DIR__.'/../../core.php';

$op = post('op');

$upload_dir = $docroot.'/files/'.Modules::getModule('MyImpianti')['directory'];

switch ($op) {
    // Aggiorno informazioni di base impianto
    case 'update':
        $matricola = post('matricola');

        if (!empty($matricola)) {
            $idanagrafica = post('idanagrafica');
            $data = Translator::dateToEnglish($_POST['data']);
            $idtecnico = post('idtecnico');
            $idsede = post('idsede');
            $nome = post('nome');
            $descrizione = post('descrizione');

            $proprietario = post('proprietario');
            $palazzo = post('palazzo');
            $ubicazione = post('ubicazione');
            $scala = post('scala');
            $piano = post('piano');
            $interno = post('interno');
            $occupante = post('occupante');

            $query = 'UPDATE my_impianti SET '.
                ' idanagrafica='.prepare($idanagrafica).','.
                ' nome='.prepare($nome).','.
                ' matricola='.prepare($matricola).','.
                ' descrizione='.prepare($descrizione).','.
                ' idsede='.prepare($idsede).','.
                ' data='.prepare($data).','.
                ' proprietario='.prepare($proprietario).','.
                ' palazzo='.prepare($palazzo).','.
                ' ubicazione='.prepare($ubicazione).','.
                ' idtecnico='.prepare($idtecnico).','.
                ' scala='.prepare($scala).','.
                ' piano='.prepare($piano).','.
                ' interno='.prepare($interno).','.
                ' occupante='.prepare($occupante).
                ' WHERE id='.prepare($id_record);
            $dbo->query($query);

            $_SESSION['infos'][] = tr('Informazioni salvate correttamente!');

            // Upload file
            if (!empty($_FILES) && !empty($_FILES['immagine']['name'])) {
                $filename = $_FILES['immagine']['name'];
                $tmp = $_FILES['immagine']['tmp_name'];

                $filename = unique_filename($filename, $upload_dir);

                if (move_uploaded_file($tmp, $upload_dir.'/'.$filename)) {
                    $dbo->query('UPDATE my_impianti SET immagine='.prepare($filename).' WHERE id='.prepare($id_record));
                } else {
                    $_SESSION['warnings'][] = tr('Errore durante il caricamento del file in _DIR_!', [
                        '_DIR_' => $upload_dir,
                    ]);
                }
            }

            // Eliminazione file
            if (post('delete_immagine') !== null) {
                $filename = basename(post('immagine'));
                delete($upload_dir.'/'.$filename);

                $dbo->query("UPDATE my_impianti SET immagine='' WHERE id=".prepare($id_record));
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

            $_SESSION['infos'][] = tr('Aggiunto nuovo impianto!');
        }

        break;

    // Carica i campi da compilare del componente
    case 'load_componente':
        include_once $docroot.'/modules/my_impianti/modutil.php';

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

        $_SESSION['infos'][] = tr('Impianto e relativi componenti eliminati!');
        break;
}
