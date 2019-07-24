<?php

include_once __DIR__.'/../../core.php';

use Modules\Interventi\Intervento;

$documento = Intervento::find($id_record);

$preventivo = $dbo->fetchOne('SELECT numero FROM co_preventivi WHERE id = '.prepare($documento['id_preventivo']));
$contratto = $dbo->fetchOne('SELECT nome, numero FROM co_contratti WHERE id = '.prepare($documento['id_contratto']));

$id_cliente = $documento['idanagrafica'];
$id_sede = $documento['idsede'];
