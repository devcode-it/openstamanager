<?php

include_once __DIR__.'/../../core.php';

// Necessaria per la funzione add_movimento_magazzino
include_once $docroot.'/modules/articoli/modutil.php';

switch (post('op')) {
    case 'update':
        $targa = post('targa');
        $nome = post('nome');
        $descrizione = post('descrizione');

        if ($dbo->fetchNum('SELECT targa FROM dt_automezzi WHERE targa='.prepare($targa).' AND NOT id='.prepare($id_record)) == 0) {
            $query = 'UPDATE dt_automezzi SET targa='.prepare($targa).', descrizione='.prepare($descrizione).', nome='.prepare($nome).' WHERE id='.prepare($id_record);
            if ($dbo->query($query)) {
                $_SESSION['infos'][] = _('Informazioni salvate correttamente!');
            }
        } else {
            $_SESSION['errors'][] = _('Esiste già un automezzo con questa targa!');
        }

        break;

    // Aggiunta automezzo
    case 'add':
        $targa = post('targa');
        $nome = post('nome');

        // Inserisco l'automezzo solo se non esiste un altro articolo con stesso targa
        if ($dbo->fetchNum('SELECT targa FROM dt_automezzi WHERE targa='.prepare($targa)) == 0) {
            $query = 'INSERT INTO dt_automezzi(targa, nome) VALUES ('.prepare($targa).', '.prepare($nome).')';
            $dbo->query($query);

            $id_record = $dbo->lastInsertedID();

            $_SESSION['infos'][] = _('Aggiunto un nuovo automezzo!');
        } else {
            $_SESSION['errors'][] = _('Esiste già un automezzo con questa targa!');
        }
        break;

    // Aggiunta tecnico
    case 'addtech':
        $idtecnico = post('idtecnico');
        $data_inizio = post('data_inizio');
        $data_fine = null;

        // Controllo sull'effettivo inserimento di una data di fine successiva a quella di inizio
        if (!empty($post['data_fine'])) {
            if (Translator::getEnglishFormatter()->toDateObject(post('data_fine')) >= Translator::getEnglishFormatter()->toDateObject($data_inizio)) {
                $data_fine = post('data_fine');
            }
        }
        $data_fine = isset($data_fine) ? $data_fine : '0000-00-00';

        // Inserisco il tecnico
        $query = 'INSERT INTO dt_automezzi_tecnici(idtecnico, idautomezzo, data_inizio, data_fine) VALUES ('.prepare($idtecnico).', '.prepare($id_record).', '.prepare($data_inizio).', '.prepare($data_fine).')';
        $dbo->query($query);

        $_SESSION['infos'][] = _('Collegato un nuovo tecnico!');
        break;

    // Salvataggio tecnici collegati
    case 'savetech':
        $errors = 0;

        foreach (post('data_inizio') as $idautomezzotecnico => $data) {
            $idautomezzotecnico = $idautomezzotecnico;
            $data_inizio = post('data_inizio')[$idautomezzotecnico];
            $data_fine = null;

            // Controllo sull'effettivo inserimento di una data di fine successiva a quella di inizio
            if (!empty($post['data_fine'][$idautomezzotecnico])) {
                if (Translator::getEnglishFormatter()->toDateObject(post('data_fine')[$idautomezzotecnico]) >= Translator::getEnglishFormatter()->toDateObject($data_inizio)) {
                    $data_fine = post('data_fine')[$idautomezzotecnico];
                }
            }
            $data_fine = isset($data_fine) ? $data_fine : '0000-00-00';

            $query = 'UPDATE dt_automezzi_tecnici SET data_inizio='.prepare($data_inizio).', data_fine='.prepare($data_fine).' WHERE id='.prepare($idautomezzotecnico);

            if (!$dbo->query($query)) {
                ++$errors;
            }
        }

        if ($errors == 0) {
            $_SESSION['infos'][] = _('Informazioni salvate correttamente!');
        } else {
            $_SESSION['errors'][] = _('Errore durante il salvataggio del tecnico!');
        }
        break;

    // Eliminazione associazione con tecnico
    case 'deltech':
        $idautomezzotecnico = post('id');

        $query = 'DELETE FROM dt_automezzi_tecnici WHERE id='.prepare($idautomezzotecnico);

        if ($dbo->query($query)) {
            $_SESSION['infos'][] = _('Tecnico rimosso!');
        }
        break;

    // Aggiunta quantità nell'automezzo
    case 'addrow':
        $idarticolo = post('idarticolo');
        $qta = post('qta');

        // Decremento la quantità dal magazzino centrale
        add_movimento_magazzino($idarticolo, -$qta, ['idautomezzo' => $id_record]);

        // Verifico se nell'automezzo c'è già questo articolo
        $rs = $dbo->fetchArray("SELECT id, qta FROM mg_articoli_automezzi WHERE idarticolo=".prepare($idarticolo)." AND idautomezzo=".prepare($id_record));

        // Se nell'automezzo c'è già questo articolo incremento la quantità...
        if (!empty($rs) && $rs[0]['qta'] >= 0) {
            $dbo->query('UPDATE mg_articoli_automezzi SET qta=qta+'.$qta." WHERE id=".prepare($rs[0]['id']));
        } else {  // ...altrimenti inserisco la scorta nell'automezzo da zero
            $dbo->query('INSERT INTO mg_articoli_automezzi(idarticolo, idautomezzo, qta) VALUES ('.prepare($idarticolo).', '.prepare($id_record).', '.prepare($qta).')');
        }

        $_SESSION['infos'][] = _("Caricato il magazzino dell'automezzo!");
        break;

    // Spostamento scorta da automezzo a magazzino generale
    case 'moverow':
        $idautomezzotecnico = post('idautomezzotecnico');

        // Leggo la quantità del lotto
        $rs = $dbo->fetchArray('SELECT qta, idarticolo FROM mg_articoli_automezzi WHERE id='.prepare($idautomezzotecnico));

        if (!empty($rs)) {
            // Elimino l'articolo dall'automezzo
            $dbo->query('DELETE FROM mg_articoli_automezzi WHERE id='.prepare($idautomezzotecnico));

            // Aggiungo la quantità al magazzino
            add_movimento_magazzino($rs[0]['idarticolo'], $rs[0]['qta'], ['idautomezzo' => $id_record]);

            $_SESSION['infos'][] = _('Articoli riportati nel magazzino centrale!');
        }
        break;

    case 'delete':
        // per ogni articolo caricato in questo automezzo
        $rs = $dbo->fetchArray('SELECT qta, idarticolo FROM mg_articoli_automezzi WHERE idautomezzo='.prepare($id_record));

        for ($i = 0; $i < sizeof($rs); ++$i) {
            // Ripristino la quantità nel magazzino centrale
            add_movimento_magazzino($rs[$i]['idarticolo'], +$rs[$i]['qta'], ['idautomezzo' => $id_record]);
        }

        // Elimino tutti gli articoli dall'automezzo
        $dbo->query('DELETE FROM mg_articoli_automezzi WHERE idautomezzo='.prepare($id_record));
        // Elimino definitivamente l'automezzo
        $dbo->query('DELETE FROM dt_automezzi WHERE id='.prepare($id_record));

        $_SESSION['infos'][] = _('Automezzo eliminato e articoli riportati in magazzino!');

        break;
}
