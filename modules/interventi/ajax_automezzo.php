<?php

/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

include_once __DIR__.'/../../core.php';

header('Content-Type: application/json');

$op = post('op');

if ($op === 'default') {
    $id_tecnico = post('id_tecnico');

    $automezzi = database()->table('zz_user_sedi')
        ->join('zz_users', 'zz_user_sedi.id_user', '=', 'zz_users.id')
        ->join('an_sedi', 'zz_user_sedi.id_sede', '=', 'an_sedi.id')
        ->where('zz_users.id_anagrafica', $id_tecnico)
        ->where('an_sedi.is_automezzo', 1)
        ->distinct()
        ->pluck('an_sedi.id')
        ->toArray();

    echo json_encode([
        'id_automezzo' => count($automezzi) === 1 ? reset($automezzi) : null,
    ]);
    exit;
}

if ($op === 'set') {
    $id_sessione = post('id_sessione');
    $id_record = post('id_record');
    $id_automezzo = post('id_automezzo') ?: null;

    $sessione = database()->table('in_interventi_tecnici')
        ->where('id', $id_sessione)
        ->where('id_intervento', $id_record)
        ->first();

    if (empty($sessione)) {
        http_response_code(404);
        echo json_encode(['error' => tr('Sessione non trovata')]);
        exit;
    }

    if (!empty($id_automezzo)) {
        $automezzo = database()->table('an_sedi')
            ->where('id', $id_automezzo)
            ->where('is_automezzo', 1)
            ->first();

        if (empty($automezzo)) {
            http_response_code(422);
            echo json_encode(['error' => tr('Automezzo non valido')]);
            exit;
        }
    }

    database()->table('in_interventi_tecnici')
        ->where('id', $id_sessione)
        ->where('id_intervento', $id_record)
        ->update(['id_automezzo' => $id_automezzo]);

    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => tr('Operazione non valida')]);
