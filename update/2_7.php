<?php

include __DIR__.'/../config.inc.php';

$dbo = database();
$modules = $dbo->fetchArray('SELECT m.*, ml1.title FROM zz_modules m LEFT JOIN zz_modules_lang ml1 ON m.id = ml1.id_record AND ml1.id_lang = 1');

foreach ($modules as $module) {
    $title = $module['title'];
    if (empty($title)) {
        $title = $module['name'];
    }

    $metaTitle = match ($module['name']) {
        'Fatture di vendita' => 'Fattura di vendita {numero} - {ragione_sociale}',
        'Fatture di acquisto' => 'Fattura di acquisto {numero} - {ragione_sociale}',
        'Ddt in entrata', 'Ddt in uscita' => $title.' {numero} - {ragione_sociale}',
        'Interventi' => 'Attività {numero} - {ragione_sociale}',
        'Ordini fornitore' => 'Ordine fornitore {numero} - {ragione_sociale}',
        'Ordini cliente' => 'Ordine cliente {numero} - {ragione_sociale}',
        'Preventivi' => 'Preventivo {numero} - {ragione_sociale}',
        'Contratti' => 'Contratto {numero} - {ragione_sociale}',
        'Impianti' => 'Impianto {matricola} - {ragione_sociale}',
        'Anagrafiche' => 'Anagrafica - {ragione_sociale}',
        'Articoli' => 'Articolo - {codice} {descrizione}',
        default => $title,
    };

    $record1 = $dbo->fetchOne('SELECT id FROM zz_modules_lang WHERE id_record = ? AND id_lang = 1', [$module['id']]);
    if (!$record1) {
        $dbo->query('INSERT INTO zz_modules_lang (id_record, id_lang, title) VALUES (?, 1, ?)', [$module['id'], $title]);
    }

    $record2 = $dbo->fetchOne('SELECT id FROM zz_modules_lang WHERE id_record = ? AND id_lang = 2', [$module['id']]);
    if (!$record2) {
        $dbo->query('INSERT INTO zz_modules_lang (id_record, id_lang, title) VALUES (?, 2, ?)', [$module['id'], $title]);
    }

    $dbo->query('UPDATE zz_modules_lang SET meta_title = ? WHERE id_record = ? AND id_lang IN (1, 2)', [$metaTitle, $module['id']]);
}
