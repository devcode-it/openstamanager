<?php

// File e cartelle deprecate
$files = [
    'modules/anagrafiche/plugins/contratti_cliente.php',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(base_dir().'/'.$value);
}

delete($files);

$fascie_orarie = $database->fetchArray('SELECT * FROM in_fasceorarie');
$tipi_intervento = $database->fetchArray('SELECT * FROM in_tipiintervento');

foreach ($fascie_orarie as $fascia_oraria) {
    foreach ($tipi_intervento as $tipo_intervento) {
        $database->insert('in_fasceorarie_tipiintervento', [
            'idfasciaoraria' => $fascia_oraria['id'],
            'idtipointervento' => $tipo_intervento['idtipointervento'],
            'costo_orario' => $tipo_intervento['costo_orario'],
            'costo_km' => $tipo_intervento['costo_km'],
            'costo_diritto_chiamata' => $tipo_intervento['costo_diritto_chiamata'],
            'costo_orario_tecnico' => $tipo_intervento['costo_orario_tecnico'],
            'costo_km_tecnico' => $tipo_intervento['costo_km_tecnico'],
            'costo_diritto_chiamata_tecnico' => $tipo_intervento['costo_km_tecnico'],
        ]);
    }
}
