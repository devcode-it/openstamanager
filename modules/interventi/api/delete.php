<?php

switch ($resource) {
    case 'sessioni_intervento':
        $dbo->query('DELETE FROM in_interventi_tecnici WHERE idintervento = :id_intervento', [
            ':id_intervento' => $request['id_intervento'],
        ]);

        break;

    case 'articoli_intervento':
        $dbo->query('DELETE FROM mg_articoli_interventi WHERE idintervento = :id_intervento', [
            ':id_intervento' => $request['id_intervento'],
        ]);

        // TODO: prevedere la modifica di quantità!

        break;
}

return [
    'sessioni_intervento',
    'articoli_intervento',
];
