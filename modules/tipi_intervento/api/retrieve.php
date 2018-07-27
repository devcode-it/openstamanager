<?php

switch ($resource) {
    // Elenco sedi per l'applicazione
    case 'tipi_intervento':
        $table = 'in_tipiintervento';

        $select = [
            '*',
            'id' => 'idtipointervento',
        ];

        break;
}

return [
    'tipi_intervento',
];
