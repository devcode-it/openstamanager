<?php

include_once __DIR__.'/../../core.php';

$records = $dbo->fetchArray('SELECT *, (SELECT COUNT(id) FROM zz_documenti WHERE idcategoria = '.prepare($id_record).') AS doc_associati FROM zz_documenti_categorie WHERE id='.prepare($id_record));
