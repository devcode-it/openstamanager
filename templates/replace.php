<?php

/**
 * Sostituisce a delle stringhe ($nome_stringa$) i valori delle anagrafiche.
 */
$replaces = array_merge($replaces, (array) $custom);

foreach ($replaces as $key => $value) {
    $new_key = '$'.str_replace("$", "", $key).'$';
    unset($replaces[$key]);

    $replaces[$new_key] = $value;
}

// Sostituisce alle variabili del template i valori
$head = str_replace(array_keys($replaces), array_values($replaces), $head);
$foot = str_replace(array_keys($replaces), array_values($replaces), $foot);
$report = str_replace(array_keys($replaces), array_values($replaces), $report);

