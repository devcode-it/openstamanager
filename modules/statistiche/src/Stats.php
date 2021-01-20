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

namespace Modules\Statistiche;

use ArrayObject;
use DateTime;

class Stats
{
    public static function monthly($original, $start, $end)
    {
        // Copia dei dati
        $array = new ArrayObject($original);
        $data = $array->getArrayCopy();

        // Ordinamento
        array_multisort(array_column($data, 'year'), SORT_ASC,
            array_column($data, 'month'), SORT_ASC,
            $data);

        // Differenza delle date in mesi
        $d1 = new DateTime($start);
        $d2 = new DateTime($end);
        $count = $d1->diff($d2)->m + ($d1->diff($d2)->y * 12) + 1;

        $year = $d1->format('Y');
        $month = intval($d1->format('m')) - 1;
        for ($i = 0; $i < $count; ++$i) {
            $year = $month >= 12 ? $year + 1 : $year;
            $month = $month % 12;

            if (!isset($data[$i]) || intval($data[$i]['month']) != $month + 1) {
                array_splice($data, $i, 0, [[
                    'result' => 0,
                    'year' => $year,
                    'month' => $month + 1,
                ]]);
            }

            ++$month;
        }

        return $data;
    }
}
