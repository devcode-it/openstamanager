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

if (!empty($id_record)) {
    $record = $dbo->fetchOne('SELECT `co_ritenuta_acconto`.*, `doc_count`.`doc_associati` FROM `co_ritenuta_acconto` LEFT JOIN (SELECT `id_ritenuta_acconto`, COUNT(`id`) AS doc_associati FROM `co_righe_documenti` GROUP BY `id_ritenuta_acconto`) AS `doc_count` ON `doc_count`.`id_ritenuta_acconto` = `co_ritenuta_acconto`.`id` WHERE `co_ritenuta_acconto`.`id`='.prepare($id_record));
}
