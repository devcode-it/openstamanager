<?php

include_once __DIR__.'/../../core.php';

use Modules\Preventivi\Preventivo;

$documento = Preventivo::find($id_record);

$id_cliente = $documento['idanagrafica'];
$id_sede = $documento['idsede'];
