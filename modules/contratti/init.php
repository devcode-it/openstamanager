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

use Modules\Contratti\Contratto;

if (isset($id_record)) {
    $contratto = Contratto::find($id_record);

    $record = $dbo->fetchOne('SELECT *,
       (SELECT tipo FROM an_anagrafiche WHERE idanagrafica = co_contratti.idanagrafica) AS tipo_anagrafica,
       (SELECT is_fatturabile FROM co_staticontratti WHERE id=idstato) AS is_fatturabile,
       (SELECT is_pianificabile FROM co_staticontratti WHERE id=idstato) AS is_pianificabile,
       (SELECT is_completato FROM co_staticontratti WHERE id=idstato) AS is_completato,
       (SELECT descrizione FROM co_staticontratti WHERE id=idstato) AS stato,
       (SELECT GROUP_CONCAT(my_impianti_contratti.idimpianto) FROM my_impianti_contratti WHERE idcontratto = co_contratti.id) AS idimpianti
   FROM co_contratti WHERE id='.prepare($id_record));
}
