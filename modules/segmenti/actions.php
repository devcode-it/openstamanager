<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
        case 'update':

            $name = post('name');
			$category = post('category');
            (strpos(post('pattern'), '#') !== false) ? $pattern = post('pattern') : $maschera = '####';
            $id_module_ = post('id_module_');
            $note = post('note');

            $query = "UPDATE zz_segments SET name=\"$name\", category=\"$category\", pattern=\"$pattern\", id_module=\"$id_module_\", note=\"$note\" WHERE id=\"$id_record\"";

            $rs = $dbo->query($query);

            $_SESSION['infos'][] = tr('Modifiche salvate correttamente.');

            break;

        case 'add':

            $name = post('name');
			$category = post('category');
            (strpos(post('pattern'), '#') !== false) ? $pattern = post('pattern') : $pattern = '####';
            $id_module_ = post('id_module_');
            $note = post('note');

            $dbo->query("INSERT INTO zz_segments( name, category,  pattern, id_module, note ) VALUES ( \"$name\", \"$category\", \"$pattern\", \"$id_module_\", \"$note\" )");
            $id_record = $dbo->last_inserted_id();

            $_SESSION['infos'][] = tr('Nuovo segmento aggiunto.');

            break;

        case 'delete':

            $query = "DELETE FROM zz_segments WHERE id=\"$id_record\"";
            $rs = $dbo->query($query);

            // TODO
            // eliminare riferimento sulle fatture eventuali collegate a questo segmento?

            $_SESSION['infos'][] = tr('Segmento eliminato.');

            break;
    }
