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

$dir = $_GET['dir'];

$id_sezionale = filter('id_sezionale');
$sezionale = $dbo->fetchOne('SELECT name FROM zz_segments WHERE id = '.$id_sezionale)['name'];

$date_start = filter('date_start');
$date_end = filter('date_end');

$tipo = $dir == 'entrata' ? 'vendite' : 'acquisti';

$v_iva = [];
$v_totale = [];

$query = 'SELECT *,
       co_documenti.id AS id,
       IF(numero = "", numero_esterno, numero) AS numero,
       SUM(subtotale - sconto) AS subtotale,
       (SELECT SUM(subtotale - sconto + iva + rivalsainps - ritenutaacconto) FROM co_righe_documenti WHERE co_righe_documenti.iddocumento=co_documenti.id GROUP BY iddocumento) + co_documenti.iva_rivalsainps AS totale,
       SUM(iva) AS iva, an_anagrafiche.ragione_sociale,
       an_anagrafiche.codice AS codice_anagrafica
FROM co_documenti
    INNER JOIN co_righe_documenti ON co_documenti.id=co_righe_documenti.iddocumento
    INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id
    INNER JOIN co_iva ON co_righe_documenti.idiva=co_iva.id
    INNER JOIN an_anagrafiche ON an_anagrafiche.idanagrafica = co_documenti.idanagrafica
WHERE dir = '.prepare($dir).' AND idstatodocumento NOT IN (SELECT id FROM co_statidocumento WHERE descrizione="Bozza" OR descrizione="Annullata") AND is_descrizione = 0 AND co_documenti.data_competenza >= '.prepare($date_start).' AND co_documenti.data_competenza <= '.prepare($date_end).' AND '.((!empty($id_sezionale)) ? 'co_documenti.id_segment = '.prepare($id_sezionale).'' : '1=1').'
GROUP BY co_documenti.id, co_righe_documenti.idiva
ORDER BY CAST(co_documenti.'.(($dir == 'entrata') ? 'data' : 'numero').' AS '.(($dir == 'entrata') ? 'DATE' : 'UNSIGNED').'), co_documenti.'.(($dir == 'entrata') ? 'numero_esterno' : 'data_competenza');
$records = $dbo->fetchArray($query);

// Sostituzioni specifiche
$custom = [
    'tipo' => $tipo,
];
