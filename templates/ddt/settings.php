<?php

/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
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

// Restituisce un array con le impostazioni per l'altezza del footer, la dimensione del carattere e la dimensione del carattere dell'intestazione
return [    
    // Imposta la dimensione del carattere, utilizzando il valore da $options se disponibile, altrimenti utilizza il valore da $settings
    'font-size' => isset($options['font-size']) && $options['font-size'] ? $options['font-size'] : $settings['font-size'],
    
    // Imposta la dimensione del carattere dell'intestazione, utilizzando il valore da $options se disponibile, altrimenti utilizza il valore da $settings
    'header-font-size' => isset($options['header-font-size']) && $options['header-font-size'] ? $options['header-font-size'] : $settings['header-font-size'],
];