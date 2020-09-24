<?php

// Script per aggiornare le date dei movimenti con le date dei documenti

$movimenti = $dbo->fetchArray('SELECT * FROM mg_movimenti');

foreach ($movimenti as $movimento) {
    $documento = null;

    if (!empty($movimento['idintervento'])) {
        $documento = $dbo->fetchOne('SELECT IFNULL(MAX(orario_fine), data_richiesta) AS data FROM in_interventi LEFT JOIN in_interventi_tecnici ON in_interventi.id=in_interventi_tecnici.idintervento WHERE in_interventi.id='.prepare($movimento['idintervento']));
    } elseif (!empty($movimento['idddt'])) {
        $documento = $dbo->fetchOne('SELECT data FROM dt_ddt WHERE id='.prepare($movimento['idddt']));
    } elseif (!empty($movimento['iddocumento'])) {
        $documento = $dbo->fetchOne('SELECT data FROM co_documenti WHERE id='.prepare($movimento['iddocumento']));
    }

    if (!empty($documento['data'])) {
        $dbo->update('mg_movimenti', [
            'data' => $documento['data'],
        ], [
            'id' => $movimento['id'],
        ]);
    }
}

$dbo->query("UPDATE mg_movimenti SET data = created_at WHERE data = '0000-00-00'");

// Fix Partite IVA
/*
foreach ($it as $key => $value) {
    $dbo->query("UPDATE `an_anagrafiche` SET `piva` = SUBSTRING(`piva`, 2) WHERE `piva` LIKE '".$key."%'");
}*/

// File e cartelle deprecate
$files = [
    'docs',
    'couscous.yml',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(base_dir().'/'.$value);
}

delete($files);
