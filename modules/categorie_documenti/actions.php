<?php

include_once __DIR__.'/../../core.php';

use Modules\CategorieDocumentali\Categoria;

switch (post('op')) {
    case 'update':
        $descrizione = post('descrizione');

        // Verifico che il nome non sia duplicato
        $count = $dbo->fetchNum('SELECT descrizione FROM do_categorie WHERE descrizione='.prepare($descrizione).' AND deleted_at IS NULL AND id !='.prepare($id_record));
        if ($count != 0) {
            flash()->error(tr('Categoria _NAME_ già esistente!', [
                '_NAME_' => $descrizione,
            ]));
        } else {
            $categoria->descrizione = $descrizione;
            $categoria->save();

            $categoria->syncPermessi(post('permessi') ?: []);

            flash()->info(tr('Informazioni salvate correttamente!'));
        }

        break;

    case 'add':
        $descrizione = post('descrizione');

        // Verifico che il nome non sia duplicato
        $count = $dbo->fetchNum('SELECT descrizione FROM do_categorie WHERE descrizione='.prepare($descrizione).' AND deleted_at IS NULL');
        if ($count != 0) {
            flash()->error(tr('Categoria _NAME_ già esistente!', [
                '_NAME_' => $descrizione,
            ]));
        } else {
            $categoria = Categoria::build($descrizione);
            $id_record = $categoria->id;

            if (isAjaxRequest()) {
                echo json_encode(['id' => $id_record, 'text' => $descrizione]);
            }

            flash()->info(tr('Nuova categoria documenti aggiunta!'));
        }

        break;

    case 'delete':
        $dbo->query('UPDATE do_categorie SET deleted_at = NOW() WHERE id = '.prepare($id_record));

        flash()->info(tr('Categoria documenti eliminata!'));

        break;
}
