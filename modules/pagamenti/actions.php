<?php

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'update':
        $descrizione = filter('descrizione');

        if (isset($descrizione)) {
            foreach ($post['id'] as $key => $id) {
                // Data fatturazione
                $giorno = 0;

                // Data fatturazione fine mese
                if ($post['scadenza'][$key] == 2) {
                    $giorno = -1;
                }

                // Data fatturazione giorno fisso
                if ($post['scadenza'][$key] == 3) {
                    $giorno = $post['giorno'][$key];
                }

                // Data fatturazione fine mese (giorno fisso)
                elseif ($post['scadenza'][$key] == 4) {
                    $giorno = -$post['giorno'][$key] - 1;
                }

                $array = [
                    'num_giorni' => $post['distanza'][$key],
                    'giorno' => $giorno,
                    'prc' => $post['percentuale'][$key],
                    'descrizione' => $descrizione,
                ];

                if (!empty($id)) {
                    $dbo->update('co_pagamenti', $array, ['id' => $id]);
                } else {
                    $dbo->INSERT('co_pagamenti', $array);
                }
            }
            $_SESSION['infos'][] = _('Salvataggio completato!');
        } else {
            $_SESSION['errors'][] = _('Ci sono stati alcuni errori durante il salvataggio!');
        }

        break;

    case 'add':
        $descrizione = filter('descrizione');

        if (isset($descrizione)) {
            $dbo->query('INSERT INTO `co_pagamenti` (`descrizione`) VALUES ('.prepare($descrizione).')');
            $id_record = $dbo->lastInsertedID();

            $_SESSION['infos'][] = str_replace('_TYPE_', 'pagamento', _('Aggiunta nuova tipologia di _TYPE_'));
        } else {
            $_SESSION['errors'][] = _('Ci sono stati alcuni errori durante il salvataggio!');
        }

        break;

    case 'delete':
        if (isset($id_record)) {
            $dbo->query('DELETE FROM `co_pagamenti` WHERE `descrizione`='.prepare($records[0]['descrizione']));
            $_SESSION['infos'][] = str_replace('_TYPE_', 'pagamento', _('Tipologia di _TYPE_ eliminata con successo!'));
        }

        break;

    case 'delete_rata':
        $id = filter('id');
        if (isset($id)) {
            $dbo->query('DELETE FROM `co_pagamenti` WHERE `id`='.prepare($id));
            $_SESSION['infos'][] = _('Elemento eliminato con successo!');

            if ($id_record == $id) {
                $res = $dbo->fetchArray('SELECT * FROM `co_pagamenti` WHERE `id`!='.prepare($id).' AND `descrizione`='.prepare($records[0]['descrizione']));
                if (count($res) != 0) {
                    redirect($rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$res[0]['id']);
                } else {
                    // $_POST['backto'] = 'record-list';
                    redirect($rootdir.'/controller.php?id_module='.$id_module);
                }
            }
        }

        break;
}
