<?php

$module_name = 'Contratti';

// Lettura info fattura
$records = $dbo->fetchArray('SELECT *, data_bozza AS data FROM co_contratti WHERE id='.prepare($id_record));

$id_cliente = $records[0]['idanagrafica'];
$id_sede = $records[0]['idsede'];
