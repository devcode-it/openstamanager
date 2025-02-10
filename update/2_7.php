<?php

include __DIR__.'/../config.inc.php';
use Models\Module;

$modules = Module::all();

foreach ($modules as $module) {
    $module->getTranslation('title', 2) ?: $module->setTranslation('title', $module->getTranslation('title'), 2);
    $module->save();

    $metaTitle = match ($module->name) {
        'Fatture di vendita' => 'Fattura di vendita {numero} - {ragione_sociale}',
        'Fatture di acquisto' => 'Fattura di acquisto {numero} - {ragione_sociale}',
        'Ddt di vendita', 'Ddt di acquisto' => $module->getTranslation('title').' {numero} - {ragione_sociale}',
        'Interventi' => 'Attività {numero} - {ragione_sociale}',
        'Ordini fornitore' => 'Ordine fornitore {numero} - {ragione_sociale}',
        'Ordini cliente' => 'Ordine cliente {numero} - {ragione_sociale}',
        'Preventivi' => 'Preventivo {numero} - {ragione_sociale}',
        'Contratti' => 'Contratto {numero} - {ragione_sociale}',
        'Impianti' => 'Impianto {matricola} - {ragione_sociale}',
        'Anagrafiche' => 'Anagrafica - {ragione_sociale}',
        'Articoli' => 'Articolo - {codice} {descrizione}',
        default => $module->getTranslation('title'),
    };

    $module->setTranslation('meta_title', $metaTitle);
    $module->setTranslation('meta_title', $module->getTranslation('title', 2), 2);
    $module->save();
}
