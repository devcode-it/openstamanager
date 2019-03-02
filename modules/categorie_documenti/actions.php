<?php

switch (post('op')) {
    case 'update':
        $descrizione = post('descrizione');

        // Verifico che il nome non sia duplicato
        $count = $dbo->fetchNum('SELECT descrizione FROM zz_documenti_categorie WHERE descrizione='.prepare($descrizione).' AND deleted_at IS NULL');
        if ($count != 0) {
            flash()->error(tr('Categoria _NAME_ già esistente!', [
                '_NAME_' => $descrizione,
            ]));
        } else {
            $dbo->update('zz_documenti_categorie', [
                'descrizione' => $descrizione,
            ], ['id' => $id_record]);

            flash()->info(tr('Informazioni salvate correttamente!'));
        }

        break;

    case 'add':
        $descrizione = post('descrizione');

        if (isset($_POST['descrizione'])) {
            // Verifico che il nome non sia duplicato
            $count = $dbo->fetchNum('SELECT descrizione FROM zz_documenti_categorie WHERE descrizione='.prepare($descrizione).' AND deleted_at IS NULL');
            if ($count != 0) {
                flash()->error(tr('Categoria _NAME_ già esistente!', [
                    '_NAME_' => $descrizione,
                ]));
            } else {
                $dbo->insert('zz_documenti_categorie', [
                    'descrizione' => $descrizione,
                ]);
                $id_record = $dbo->last_inserted_id();

                if (isAjaxRequest()) {
                    echo json_encode(['id' => $id_record, 'text' => $descrizione]);
                }

                flash()->info(tr('Nuova categoria documenti aggiunta!'));
            }
        }

        break;

    case 'delete':
        $dbo->query('UPDATE zz_documenti_categorie SET deleted_at = NOW() WHERE id = '.prepare($id_record));

        flash()->info(tr('Categoria docimenti eliminata!'));

        break;
}
