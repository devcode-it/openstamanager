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

include_once __DIR__.'/../../core.php';

use Modules\Preventivi\Preventivo;

function get_imponibile_preventivo($idpreventivo)
{
    $preventivo = Preventivo::find($idpreventivo);

    return $preventivo->totale_imponibile;
}

/**
 * Restituisce lo stato dell'ordine in base alle righe.
 */
function get_stato_preventivo($idpreventivo)
{
    $dbo = database();

    $rs = $dbo->fetchArray('SELECT SUM(qta) AS qta, SUM(qta_evasa) AS qta_evasa FROM co_righe_preventivi GROUP BY idpreventivo HAVING idpreventivo='.prepare($idpreventivo));

    if ($rs[0]['qta_evasa'] > 0) {
        if ($rs[0]['qta'] > $rs[0]['qta_evasa']) {
            return 'Parzialmente evaso';
        } elseif ($rs[0]['qta'] == $rs[0]['qta_evasa']) {
            return 'Evaso';
        }
    } else {
        return 'Non evaso';
    }
}
