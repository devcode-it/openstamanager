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

$record = $dbo->fetchOne('SELECT `ns_note_spese`.*, `an_anagrafiche`.`ragione_sociale`, `an_anagrafiche`.`indirizzo`, `an_anagrafiche`.`citta`, `an_anagrafiche`.`provincia`, `an_anagrafiche`.`cap`, COALESCE(SUM(`ns_righe_note_spese`.`importo`), 0) AS totale FROM `ns_note_spese` LEFT JOIN `an_anagrafiche` ON `an_anagrafiche`.`id` = `ns_note_spese`.`id_anagrafica` LEFT JOIN `ns_righe_note_spese` ON `ns_righe_note_spese`.`id_nota_spesa` = `ns_note_spese`.`id` WHERE `ns_note_spese`.`id` = ? GROUP BY `ns_note_spese`.`id`', [$id_record]);
$righe = $dbo->fetchArray('SELECT * FROM `ns_righe_note_spese` WHERE `id_nota_spesa` = ? ORDER BY `data` ASC, `id` ASC', [$id_record]);
