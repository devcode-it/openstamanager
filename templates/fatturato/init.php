<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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
