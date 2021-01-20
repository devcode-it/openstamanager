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

if (isset($id_record)) {
    $preventivo = Modules\Preventivi\Preventivo::with('stato')->find($id_record);

    $record = $dbo->fetchOne('SELECT co_preventivi.*,
        (SELECT tipo FROM an_anagrafiche WHERE idanagrafica = co_preventivi.idanagrafica) AS tipo_anagrafica,
        co_statipreventivi.is_fatturabile,
        co_statipreventivi.is_completato,
        co_statipreventivi.is_revisionabile,
        co_statipreventivi.descrizione AS stato
    FROM co_preventivi LEFT JOIN co_statipreventivi ON co_preventivi.idstato=co_statipreventivi.id
    WHERE co_preventivi.id='.prepare($id_record));
}
