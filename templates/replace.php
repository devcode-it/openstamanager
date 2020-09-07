<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Sostituisce a delle stringhe ($nome_stringa$) i valori delle anagrafiche.
 */
$replaces = array_merge($replaces, (array) $custom);

foreach ($replaces as $key => $value) {
    $new_key = '$'.str_replace('$', '', $key).'$';
    unset($replaces[$key]);

    $replaces[$new_key] = $value;
}

// Sostituisce alle variabili del template i valori
$head = str_replace(array_keys($replaces), array_values($replaces), $head);
$foot = str_replace(array_keys($replaces), array_values($replaces), $foot);
$report = str_replace(array_keys($replaces), array_values($replaces), $report);
