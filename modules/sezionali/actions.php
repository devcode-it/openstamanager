<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
        case 'update':

            $nome = post('nome');
            (strpos(post('maschera'), '#') !== false) ? $maschera = post('maschera') : $maschera = '##';
            $dir = post('dir');
            $idautomezzo = post('idautomezzo');
            $note = post('note');

            $query = "UPDATE co_sezionali SET nome=\"$nome\", maschera=\"$maschera\", dir=\"$dir\", idautomezzo=\"$idautomezzo\", note=\"$note\" WHERE id=\"$id_record\"";

            $rs = $dbo->query($query);

            $_SESSION['infos'][] = tr('Modifiche salvate correttamente.');

            break;

        case 'add':

            $nome = post('nome');
            (strpos(post('maschera'), '#') !== false) ? $maschera = post('maschera') : $maschera = '##';
            $dir = post('dir');
            $idautomezzo = post('idautomezzo');
            $note = post('note');

            $dbo->query("INSERT INTO co_sezionali( nome, maschera, dir, idautomezzo, note ) VALUES ( \"$nome\", \"$maschera\", \"$dir\", \"$idautomezzo\", \"$note\" )");
            $id_record = $dbo->last_inserted_id();

            $_SESSION['infos'][] = tr('Nuovo sezionale aggiunto.');

            break;

        case 'delete':

            $query = "DELETE FROM co_sezionali WHERE id=\"$id_record\"";
            $rs = $dbo->query($query);

            // TODO
            // eliminare riferimento sulle fatture eventuali collegate a questo sezionale?

            $_SESSION['infos'][] = tr('Sezionale eliminato.');

            break;
    }
