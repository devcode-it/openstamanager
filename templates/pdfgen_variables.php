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

include_once __DIR__.'/info.php';

// Retrocompatibilità con le stampe gestite da HTML2PDF
$replaces['default_header'] = str_replace(['{PAGENO}', '{nb}'], ['[[page_cu]]', '[[page_nb]]'], $replaces['default_header']);

$replaces['footer'] = str_replace(['{PAGENO}', '{nb}'], ['[[page_cu]]', '[[page_nb]]'], $replaces['footer']);
$replaces['default_footer'] = str_replace(['{PAGENO}', '{nb}'], ['[[page_cu]]', '[[page_nb]]'], $replaces['default_footer']);

$prefixes = [
    'c_',
    'f_',
];

foreach ($prefixes as $prefix) {
    if ($replaces[$prefix.'piva'] != $replaces[$prefix.'codicefiscale']) {
        $replaces[$prefix.'piva'] = !empty($replaces[$prefix.'piva']) ? 'P.Iva: '.$replaces[$prefix.'piva'] : '';
        $replaces[$prefix.'codicefiscale'] = !empty($replaces[$prefix.'codicefiscale']) ? 'C.F.: '.$replaces[$prefix.'codicefiscale'] : '';
    } else {
        $replaces[$prefix.'piva'] = !empty($replaces[$prefix.'piva']) ? 'P.Iva/C.F.: '.$replaces[$prefix.'piva'] : '';
        $replaces[$prefix.'codicefiscale'] = '';
    }

    $replaces[$prefix.'codice_destinatario'] = !empty($replaces[$prefix.'codice_destinatario']) ? 'Cod.Fatturazione: '.$replaces[$prefix.'codice_destinatario'] : '';
    $replaces[$prefix.'capsoc'] = !empty($replaces[$prefix.'capsoc']) ? 'Cap.Soc.: '.$replaces[$prefix.'capsoc'] : '';
    $replaces[$prefix.'sitoweb'] = !empty($replaces[$prefix.'sitoweb']) ? 'Web: '.$replaces[$prefix.'sitoweb'] : '';
    $replaces[$prefix.'telefono'] = !empty($replaces[$prefix.'telefono']) ? 'Tel: '.$replaces[$prefix.'telefono'] : '';
    $replaces[$prefix.'fax'] = !empty($replaces[$prefix.'fax']) ? 'Fax: '.$replaces[$prefix.'fax'] : '';
    $replaces[$prefix.'cellulare'] = !empty($replaces[$prefix.'cellulare']) ? 'Cell: '.$replaces[$prefix.'cellulare'] : '';
    $replaces[$prefix.'email'] = !empty($replaces[$prefix.'email']) ? 'Email: '.$replaces[$prefix.'email'] : '';
    $replaces[$prefix.'codiceiban'] = !empty($replaces[$prefix.'codiceiban']) ? 'IBAN: '.$replaces[$prefix.'codiceiban'] : '';
    $replaces[$prefix.'pec'] = !empty($replaces[$prefix.'pec']) ? 'PEC: '.$replaces[$prefix.'pec'] : '';

    foreach ($replaces as $key => $value) {
        if (string_starts_with($key, $prefix)) {
            $replaces[$key] = empty($value) ? $value : $value.'<br/>';
        }
    }
}
