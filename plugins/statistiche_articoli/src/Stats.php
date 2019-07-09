<?php

namespace Plugins\StatisticheArticoli;

class Stats
{
    public static function prezzi($id_articolo, $start, $end, $dir = 'uscita')
    {
        $database = database();

        $from = 'FROM co_righe_documenti INNER JOIN co_documenti ON co_righe_documenti.iddocumento = co_documenti.id WHERE co_righe_documenti.subtotale != 0 AND co_documenti.idtipodocumento IN (SELECT id FROM co_tipidocumento WHERE dir = '.prepare($dir).') AND co_documenti.data BETWEEN '.prepare($start).' AND '.prepare($end).' AND co_righe_documenti.idarticolo='.prepare($id_articolo);

        $prezzo_medio = $database->fetchOne('SELECT (SUM(subtotale) - SUM(sconto)) / SUM(qta) AS prezzo '.$from)['prezzo'];

        $prezzo = 'SELECT (subtotale - sconto) / qta AS prezzo, co_documenti.data '.$from.' ORDER BY prezzo';
        $prezzo_min = $database->fetchOne($prezzo.' ASC');
        $prezzo_max = $database->fetchOne($prezzo.' DESC');

        return [
            'media' => $prezzo_medio,
            'max' => $prezzo_max,
            'min' => $prezzo_min,
        ];
    }
}
