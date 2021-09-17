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

use Modules\Scadenzario\Gruppo;

include_once __DIR__.'/../../core.php';

if (isset($id_record)) {
    $gruppo = Gruppo::find($id_record);
    $documento = $gruppo->fattura;
    $scadenze = $gruppo->scadenze;

    $record = $gruppo->toArray();

    // Scelgo la query in base alla scadenza
    if (!empty($documento)) {
        $totale_da_pagare = $documento->netto;
    } else {
        $totale_da_pagare = sum(array_column($scadenze->toArray(), 'da_pagare'));
    }
}
