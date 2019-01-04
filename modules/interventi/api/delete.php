<?php

switch ($resource) {
    case 'sessioni_intervento':
        $dbo->query('DELETE FROM `in_interventi_tecnici` WHERE `idintervento` = :id_intervento AND `idtecnico` = :id_tecnico', [
            ':id_intervento' => $request['id_intervento'],
            ':id_tecnico' => $user['idanagrafica'],
        ]);

        break;

    case 'articoli_intervento':
        $dbo->query('DELETE FROM `mg_articoli_interventi` WHERE `idintervento` = :id_intervento', [
            ':id_intervento' => $request['id_intervento'],
        ]);

        // TODO: prevedere la modifica di quantità!
        // TODO: prevedere causali

        break;
}

return [
    'sessioni_intervento',
    //'articoli_intervento',
];
