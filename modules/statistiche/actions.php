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

use Modules\Statistiche\Stats;

include_once __DIR__.'/../../core.php';

$calendar_id = filter('calendar_id');
$start = filter('start');
$end = filter('end');

switch (filter('op')) {
    case 'fatturato':
        $results = $dbo->fetchArray("SELECT ROUND(SUM(co_righe_documenti.subtotale - co_righe_documenti.sconto), 2) AS result, YEAR(co_documenti.data) AS year, MONTH(co_documenti.data) AS month FROM co_documenti INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id INNER JOIN co_righe_documenti ON co_righe_documenti.iddocumento=co_documenti.id INNER JOIN zz_segments ON co_documenti.id_segment=zz_segments.id  WHERE co_tipidocumento.dir='entrata' AND co_tipidocumento.descrizione!='Bozza' AND co_documenti.data BETWEEN ".prepare($start).' AND '.prepare($end).' AND is_fiscale=1 GROUP BY YEAR(co_documenti.data), MONTH(co_documenti.data) ORDER BY YEAR(co_documenti.data) ASC, MONTH(co_documenti.data) ASC');

        $results = Stats::monthly($results, $start, $end);

        echo json_encode([
            'label' => tr('Fatturato').' - '.tr('Periodo _NUM_', [
                '_NUM_' => $calendar_id,
            ]),
            'results' => $results,
        ]);

        break;
    case 'acquisti':
        $results = $dbo->fetchArray("SELECT ROUND(SUM(co_righe_documenti.subtotale - co_righe_documenti.sconto), 2) AS result, YEAR(co_documenti.data) AS year, MONTH(co_documenti.data) AS month FROM co_documenti INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id INNER JOIN co_righe_documenti ON co_righe_documenti.iddocumento=co_documenti.id INNER JOIN zz_segments ON co_documenti.id_segment=zz_segments.id  WHERE co_tipidocumento.dir='uscita' AND co_tipidocumento.descrizione!='Bozza' AND co_documenti.data BETWEEN ".prepare($start).' AND '.prepare($end).' AND is_fiscale=1 GROUP BY YEAR(co_documenti.data), MONTH(co_documenti.data) ORDER BY YEAR(co_documenti.data) ASC, MONTH(co_documenti.data) ASC');

        $results = Stats::monthly($results, $start, $end);

        echo json_encode([
            'label' => tr('Acquisti').' - '.tr('Periodo _NUM_', [
                '_NUM_' => $calendar_id,
            ]),
            'results' => $results,
        ]);

        break;
}
