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

use PHPSQLParser\PHPSQLParser;

if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT *, (SELECT options FROM zz_modules WHERE id = zz_segments.id_module) options, (SELECT name FROM zz_modules WHERE id = zz_segments.id_module) AS modulo, (SELECT COUNT(t.id) FROM zz_segments t WHERE t.id_module = zz_segments.id_module) AS n_sezionali FROM zz_segments WHERE id='.prepare($id_record));

    $parser = new PHPSQLParser();
    $parsed = $parser->parse($record['options']);
    $table = $parsed['FROM'][0]['table'];

    $righe = $dbo->fetchArray('SELECT COUNT(*) AS tot FROM '.$table.' WHERE id_segment = '.prepare($id_record));
    $tot = $righe[0]['tot'];
}
