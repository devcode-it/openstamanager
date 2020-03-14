<?php

include_once __DIR__.'/../../core.php';

$dir = get('dir');
$date_start = $_SESSION['period_start'];
$date_end = $_SESSION['period_end'];

// Raggruppamento
$query = "SELECT data,
       DATE_FORMAT(data, '%m-%Y') AS periodo,
       SUM(co_righe_documenti.subtotale - co_righe_documenti.sconto) as imponibile,
       SUM(iva) as iva,
      SUM(co_righe_documenti.subtotale - co_righe_documenti.sconto + iva) as totale
FROM co_documenti
    INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento = co_tipidocumento.id
    INNER JOIN co_righe_documenti ON co_righe_documenti.iddocumento = co_documenti.id
WHERE
    (data >= ".prepare($date_start).' AND data <= '.prepare($date_end).')
    AND dir = '.prepare($dir).'
    '.$add_where.'
GROUP BY periodo
ORDER BY data ASC';
$raggruppamenti = $dbo->fetchArray($query);
