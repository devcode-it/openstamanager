<?php

use Modules\Statistiche\Stats;

include_once __DIR__.'/../../core.php';

$calendar_id = filter('calendar_id');
$start = filter('start');
$end = filter('end');

switch (filter('op')) {
    case 'fatturato':
        $results = $dbo->fetchArray("SELECT ROUND(SUM(co_righe_documenti.subtotale - co_righe_documenti.sconto), 2) AS result, YEAR(co_documenti.data) AS year, MONTH(co_documenti.data) AS month FROM co_documenti INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id INNER JOIN co_righe_documenti ON co_righe_documenti.iddocumento=co_documenti.id WHERE co_tipidocumento.dir='entrata' AND co_tipidocumento.descrizione!='Bozza' AND co_documenti.data BETWEEN ".prepare($start).' AND '.prepare($end).' GROUP BY YEAR(co_documenti.data), MONTH(co_documenti.data) ORDER BY YEAR(co_documenti.data) ASC, MONTH(co_documenti.data) ASC');

        $results = Stats::monthly($results, $start, $end);

        echo json_encode([
            'label' => tr('Fatturato').' - '.tr('Periodo _NUM_', [
                '_NUM_' => $calendar_id
            ]),
            'results' => $results
        ]);

        break;
    case 'acquisti':
        $results = $dbo->fetchArray("SELECT ROUND(SUM(co_righe_documenti.subtotale - co_righe_documenti.sconto), 2) AS result, YEAR(co_documenti.data) AS year, MONTH(co_documenti.data) AS month FROM co_documenti INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id INNER JOIN co_righe_documenti ON co_righe_documenti.iddocumento=co_documenti.id WHERE co_tipidocumento.dir='uscita' AND co_tipidocumento.descrizione!='Bozza' AND co_documenti.data BETWEEN ".prepare($start).' AND '.prepare($end).' GROUP BY YEAR(co_documenti.data), MONTH(co_documenti.data) ORDER BY YEAR(co_documenti.data) ASC, MONTH(co_documenti.data) ASC');

        $results = Stats::monthly($results, $start, $end);

        echo json_encode([
            'label' => tr('Acquisti').' - '.tr('Periodo _NUM_', [
                '_NUM_' => $calendar_id
            ]),
            'results' => $results
        ]);

        break;
}
