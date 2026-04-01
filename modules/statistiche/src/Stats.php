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

class Stats
{
    public static function monthly($original, $start, $end)
    {
        $start_date = (new \DateTimeImmutable($start));
        $end_date = (new \DateTimeImmutable($end));

        if ($end_date < $start_date) {
            return [];
        }

        $grouped = [];
        foreach ($original as $row) {
            $row = (array) $row;
            $year = (int) ($row['year'] ?? 0);
            $month = (int) ($row['month'] ?? 0);

            if ($year < 1 || $month < 1 || $month > 12) {
                continue;
            }

            $key = sprintf('%04d-%02d', $year, $month);
            $grouped[$key] = round(($grouped[$key] ?? 0) + (float) ($row['result'] ?? 0), 2);
        }

        $data = [];
        for ($cursor = $start_date; $cursor <= $end_date; $cursor = $cursor->modify('+1 month')) {
            $key = $cursor->format('Y-m');
            $data[] = [
                'result' => round((float) ($grouped[$key] ?? 0), 2),
                'year' => (int) $cursor->format('Y'),
                'month' => (int) $cursor->format('m'),
            ];
        }

        return $data;
    }
}
