<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
        case 'update':

            $name = post('name');
            $category = post('category');
            (strpos(post('pattern'), '#') !== false) ? $pattern = post('pattern') : $maschera = '####';
            $id_module_ = post('id_module_');
            $note = post('note');
            $clause = post('clause');
            $predefined = $post['predefined'];
            $position = post('position');

            if (count($dbo->fetchArray("SELECT id FROM zz_segments WHERE id_module = \"$id_module_\"")) == 0) {
                $predefined = 1;
            }

            if ($predefined) {
                $dbo->query("UPDATE zz_segments SET predefined = 0 WHERE id_module = \"$id_module_\"");
            }

            $query = "UPDATE zz_segments SET name=\"$name\", clause=\"$clause\",  position=\"$position\", pattern=\"$pattern\", id_module=\"$id_module_\", note=\"$note\", predefined=\"$predefined\" WHERE id=\"$id_record\"";

            $rs = $dbo->query($query);

            $_SESSION['infos'][] = tr('Modifiche salvate correttamente.');

            break;

        case 'add':

            $name = post('name');
            $category = post('category');
            (strpos(post('pattern'), '#') !== false) ? $pattern = post('pattern') : $pattern = '####';
            $id_module_ = post('id_module_');
            $note = post('note');
            $predefined = $post['predefined'];
            $clause = '1=1';

            if (count($dbo->fetchArray("SELECT id FROM zz_segments WHERE id_module = \"$id_module_\"")) == 0) {
                $predefined = 1;
            }

            if ($predefined) {
                $dbo->query("UPDATE zz_segments SET predefined = 0 WHERE id_module = \"$id_module_\"");
            }

            $dbo->query("INSERT INTO zz_segments( name, clause,  pattern, id_module, note, predefined ) VALUES ( \"$name\", \"$clause\", \"$pattern\", \"$id_module_\", \"$note\", \"$predefined\" )");
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
