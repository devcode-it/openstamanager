<?php

include_once __DIR__.'/core.php';

$posizione = $id_module;
if (isset($id_record)) {
    $posizione .= ', '.$id_record;
}

$dbo->query('UPDATE zz_semaphores SET updated = NOW() WHERE id_utente = '.prepare(Auth::user()['id_utente']).' AND posizione = '.prepare($posizione));
$dbo->query('DELETE FROM zz_semaphores WHERE DATE_ADD(updated, INTERVAL '.(setting('Timeout notifica di presenza (minuti)') * 2).' SECOND) <= NOW()');

$datas = $dbo->fetchArray('SELECT DISTINCT username FROM zz_semaphores INNER JOIN zz_users ON zz_semaphores.id_utente=zz_users.id WHERE zz_semaphores.id_utente != '.prepare(Auth::user()['id_utente']).' AND posizione = '.prepare($posizione));

$result = [];
foreach ($datas as $data) {
    $result[] = [
        'username' => $data['username'],
    ];
}

echo json_encode($result);
