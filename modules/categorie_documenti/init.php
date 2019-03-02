<?php

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT *, (SELECT COUNT(id) FROM zz_documenti WHERE idcategoria = '.prepare($id_record).') AS doc_associati FROM zz_documenti_categorie WHERE id='.prepare($id_record));
}
