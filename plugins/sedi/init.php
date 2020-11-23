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

// id_record = sede
if (isset($id_record)) {
    $record = $dbo->fetchOne('SELECT * FROM an_sedi WHERE id='.prepare($id_record));
    $record['lat'] = floatval($record['lat']);
    $record['lng'] = floatval($record['lng']);
}

// id_parent = anagrafica
if (isset($id_parent)) {
    $record['tipo_anagrafica'] = $dbo->fetchOne('SELECT tipo FROM an_anagrafiche WHERE an_anagrafiche.idanagrafica ='.prepare($id_parent))['tipo'];
    $record['iso2'] = $dbo->fetchOne('SELECT iso2 FROM an_nazioni INNER JOIN an_anagrafiche ON an_nazioni.id = an_anagrafiche.id_nazione WHERE an_anagrafiche.idanagrafica ='.prepare($id_parent))['iso2'];
}
