<?php

switch ($resource) {
    // Elenco sedi per l'applicazione
    case 'tipi_intervento':
        $table = 'in_tipiintervento';

        $select = [
            '*',
            'id' => 'id_tipo_intervento',
        ];

        break;
}

return [
    'tipi_intervento',
];
