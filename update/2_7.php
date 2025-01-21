<?php

include __DIR__.'/../config.inc.php';
use Models\Module;

$modules = Module::all();

foreach ($modules as $module) {
    $module->getTranslation('title', 2) ?: $module->setTranslation('title', $module->getTranslation('title'), 2);
    $module->save();
}

foreach ($modules as $module) {
    if ($module->name == 'Fatture di vendita' || $module->name == 'Fatture di acquisto' || $module->name == 'Ddt di vendita' || $module->name == 'Ddt di acquisto' || $module->name == 'Interventi' || $module->name == 'Ordini fornitore' || $module->name == 'Ordini cliente' || $module->name == 'Preventivi' || $module->name == 'Contratti') {
        $module->setTranslation('meta_title', $module->getTranslation('title').' {numero} - {ragione_sociale}');
        $module->setTranslation('meta_title', $module->getTranslation('title', 2).' {numero} - {ragione_sociale}', 2);
    } elseif ($module->name == 'Impianti') {
        $module->setTranslation('meta_title', $module->getTranslation('title').' {matricola} - {ragione_sociale}');
        $module->setTranslation('meta_title', $module->getTranslation('title', 2).' {matricola} - {ragione_sociale}', 2);
    } elseif ($module->name == 'Anagrafiche') {
        $module->setTranslation('meta_title', $module->getTranslation('title').' - {ragione_sociale}');
        $module->setTranslation('meta_title', $module->getTranslation('title', 2).' - {ragione_sociale}', 2);
    } elseif ($module->name == 'Articoli') {
        $module->setTranslation('meta_title', $module->getTranslation('title').' - {codice} {descrizione}');
        $module->setTranslation('meta_title', $module->getTranslation('title', 2).' - {codice} {descrizione}', 2);
    } else {
        $module->setTranslation('meta_title', $module->getTranslation('title'));
        $module->setTranslation('meta_title', $module->getTranslation('title', 2), 2);
    }

    $module->save();
}
