<?php

// File e cartelle deprecate
$files = [
    'modules/scadenzario/controller_after.php',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(base_dir().'/'.$value);
}

delete($files);

$scadenze = $dbo->fetchArray('SELECT * FROM co_scadenziario');

foreach ($scadenze as $scadenza) {
    $idanagrafica = $dbo->selectOne('co_documenti', 'idanagrafica', ['id' => $scadenza['iddocumento']])['idanagrafica'];
    $dbo->update('co_scadenziario', [
        'idanagrafica' => $idanagrafica ?: setting('Azienda predefinita'),
    ], ['id' => $scadenza['id']]);
}

?>