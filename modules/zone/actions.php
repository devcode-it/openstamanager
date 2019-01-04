<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'update':
        $id_zona = post('id_record');
        $nome = post('nome');
        $descrizione = post('descrizione');

        // Verifico che il nome o la descrizione non esistano già
        $n = $dbo->fetchNum('SELECT id FROM an_zone WHERE (nome='.prepare($nome).' OR descrizione='.prepare($descrizione).') AND NOT id='.prepare($id_zona));

        // Zona già esistente
        if ($n > 0) {
            flash()->error(tr('Zona già esistente!'));
        }
        // Zona non esistente
        else {
            $dbo->query('UPDATE an_zone SET nome='.prepare($nome).', descrizione='.prepare($descrizione).' WHERE id='.prepare($id_zona).' AND `default`=0');
            flash()->info(tr('Informazioni salvate correttamente!'));
        }

        break;

    case 'add':
        $nome = post('nome');
        $descrizione = post('descrizione');

        // Verifico che il nome non sia duplicato
        $n = $dbo->fetchNum('SELECT id FROM an_zone WHERE nome='.prepare($nome).' OR descrizione='.prepare($descrizione));

        if ($n > 0) {
            flash()->error(tr('Nome già esistente!'));
        } else {
            $query = 'INSERT INTO an_zone(`nome`, `descrizione`, `default`) VALUES ('.prepare($nome).', '.prepare($descrizione).', 0)';
            $dbo->query($query);

            $id_record = $dbo->lastInsertedID();

            if (isAjaxRequest()) {
                echo json_encode(['id' => $id_record, 'text' => $nome.' - '.$descrizione]);
            }

            flash()->info(tr('Aggiunta una nuova zona!'));
        }

        break;

    case 'delete':
        $dbo->query('DELETE FROM an_zone WHERE id='.prepare($id_record).' AND `default`=0');

        // Reimposto a 0 tutti gli idzona su an_anagrafiche (scollego la zona da tutte le anagrafiche associate)
        $dbo->query('UPDATE an_anagrafiche SET idzona = 0 WHERE idanagrafica='.prepare($id_record));

        flash()->info(tr('Zona eliminata!'));

        break;
}
