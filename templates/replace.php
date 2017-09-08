<?php

/**
 * Sostituisce a delle stringhe ($nome_stringa$) i valori delle anagrafiche.
 */
$replaces = array_merge($replaces, (array) $custom);

foreach ($replaces as $key => $value) {
    $replaces['$'.$key.'$'] = $value;
    unset($replaces[$key]);
}

// Sostituisce alle variabili del template i valori
$head = str_replace(array_keys($replaces), array_values($replaces), $head);
$foot = str_replace(array_keys($replaces), array_values($replaces), $foot);
$report = str_replace(array_keys($replaces), array_values($replaces), $report);
