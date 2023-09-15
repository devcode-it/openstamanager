<?php

// File e cartelle deprecate

$files = [
    'modules/scadenzario/controller_after.php',
];

foreach ($files as $key => $value) {
    $files[$key] = realpath(base_dir().'/'.$value);
}

delete($files);

// Set dell'anagrafica a tutte le scadenze
$scadenze = $dbo->fetchArray('SELECT * FROM co_scadenziario');
foreach ($scadenze as $scadenza) {
    $idanagrafica = $dbo->selectOne('co_documenti', 'idanagrafica', ['id' => $scadenza['iddocumento']])['idanagrafica'];
    $dbo->update('co_scadenziario', [
        'idanagrafica' => $idanagrafica ?: setting('Azienda predefinita'),
    ], ['id' => $scadenza['id']]);
}

// Eliminazione definitiva delle aliquote iva già eliminate, non utilizzate in nessuna tabella
$aliquote_eliminate = $dbo->fetchArray('SELECT * FROM co_iva WHERE deleted_at IS NOT NULL');
foreach ($aliquote_eliminate as $aliquota) {
    $elimina_iva = true;
    if (!empty($dbo->select('mg_articoli', 'id', [], ['idiva_vendita' => $aliquota['id']]))) {
        $elimina_iva = false;
    } elseif (!empty($dbo->select('an_anagrafiche', 'idanagrafica', [], ['idiva_vendite' => $aliquota['id']]))) {
        $elimina_iva = false;
    } elseif (!empty($dbo->select('an_anagrafiche', 'idanagrafica', [], ['idiva_acquisti' => $aliquota['id']]))) {
        $elimina_iva = false;
    } elseif (!empty($dbo->select('co_righe_contratti', 'id', [], ['idiva' => $aliquota['id']]))) {
        $elimina_iva = false;
    } elseif (!empty($dbo->select('dt_righe_ddt', 'id', [], ['idiva' => $aliquota['id']]))) {
        $elimina_iva = false;
    } elseif (!empty($dbo->select('co_righe_documenti', 'id', [], ['idiva' => $aliquota['id']]))) {
        $elimina_iva = false;
    } elseif (!empty($dbo->select('in_righe_interventi', 'id', [], ['idiva' => $aliquota['id']]))) {
        $elimina_iva = false;
    } elseif (!empty($dbo->select('co_righe_preventivi', 'id', [], ['idiva' => $aliquota['id']]))) {
        $elimina_iva = false;
    } elseif (!empty($dbo->select('or_righe_ordini', 'id', [], ['idiva' => $aliquota['id']]))) {
        $elimina_iva = false;
    }

    if ($elimina_iva) {
        $dbo->query('DELETE FROM co_iva WHERE id='.prepare($aliquota['id']));
    }
}
