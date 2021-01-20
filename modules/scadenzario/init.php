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

use Modules\Fatture\Fattura;

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT * FROM co_scadenziario WHERE id = '.prepare($id_record));
    $documento = Fattura::find($record['iddocumento']);

    // Scelgo la query in base alla scadenza
    if (!empty($documento)) {
        $scadenze = $dbo->fetchArray('SELECT * FROM co_scadenziario WHERE iddocumento = '.prepare($documento->id).' ORDER BY scadenza ASC');
        $totale_da_pagare = $documento->netto;
    } else {
        $scadenze = $dbo->fetchArray('SELECT * FROM co_scadenziario WHERE id = '.prepare($id_record).' ORDER BY scadenza ASC');
        $totale_da_pagare = sum(array_column($scadenze, 'da_pagare'));
    }
}
