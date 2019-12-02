<?php

include_once __DIR__.'/../../core.php';

use Modules\CategorieDocumentali\Categoria;

if (isset($id_record)) {
    $categoria = Categoria::find($id_record);

    $record = $dbo->fetchOne("SELECT *,
        (SELECT COUNT(id) FROM do_documenti WHERE idcategoria = '.prepare($id_record).') AS doc_associati,
        GROUP_CONCAT(do_permessi.id_gruppo SEPARATOR ',') AS permessi
    FROM do_categorie
        LEFT JOIN do_permessi ON do_permessi.id_categoria = do_categorie.id
    WHERE id=".prepare($id_record).'
    GROUP BY do_categorie.id');
}
