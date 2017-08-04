<?php

include_once __DIR__.'/core.php';

$id_module = filter('id_module');
$id_record = filter('id_record');

$posizione = $id_module;
if (isset($id_record)) {
    $posizione .= ', '.$id_record;
}

$dbo->query('UPDATE zz_semaphores SET updated_at = NOW() WHERE id_utente = '.prepare($_SESSION['idutente']).' AND posizione = '.prepare($posizione));
$dbo->query('DELETE FROM zz_semaphores WHERE DATE_ADD(updated_at, INTERVAL '.(get_var('Timeout notifica di presenza (minuti)') * 2).' SECOND) <= NOW()');
$datas = $dbo->fetchArray('SELECT DISTINCT * FROM zz_semaphores INNER JOIN zz_users ON zz_semaphores.id_utente=zz_users.idutente WHERE id_utente != '.prepare($_SESSION['idutente']).' AND posizione = '.prepare($posizione));

$result = [];
if ($datas != null) {
    foreach ($datas as $data) {
        array_push($result, ['username' => $data['username']]);
    }
}

echo json_encode($result);
