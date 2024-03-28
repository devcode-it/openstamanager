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

use Modules\Anagrafiche\Anagrafica;
use Modules\Anagrafiche\Tipo;

$rs = Tipo::get();

foreach ($rs as $riga) {
    ${'id_'.strtolower($riga->getTranslation('name', Models\Locale::getPredefined()->id))} = $riga->id;
}

if (!empty($id_record)) {
    $anagrafica = Anagrafica::withTrashed()->find($id_record);

    $record = $dbo->fetchOne('SELECT 
        `an_anagrafiche`.*,
        GROUP_CONCAT(`an_tipianagrafiche`.`id`) AS idtipianagrafica,
        GROUP_CONCAT(`an_anagrafiche_agenti`.`idagente`) AS idagenti,
        GROUP_CONCAT(`an_tipianagrafiche_lang`.`name`) AS tipianagrafica
    FROM 
        `an_anagrafiche`
        INNER JOIN `an_tipianagrafiche_anagrafiche` ON `an_anagrafiche`.`idanagrafica`=`an_tipianagrafiche_anagrafiche`.`idanagrafica`
        INNER JOIN `an_tipianagrafiche` ON `an_tipianagrafiche`.`id`=`an_tipianagrafiche_anagrafiche`.`idtipoanagrafica`
        LEFT JOIN `an_tipianagrafiche_lang` ON (`an_tipianagrafiche`.`id`=`an_tipianagrafiche_lang`.`id_record` AND `an_tipianagrafiche_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).')
        LEFT JOIN `an_anagrafiche_agenti` ON `an_anagrafiche`.`idanagrafica`=`an_anagrafiche_agenti`.`idanagrafica`
    WHERE 
        `an_anagrafiche`.`idanagrafica`='.prepare($id_record));

    // Cast per latitudine e longitudine
    if (!empty($record)) {
        $record['lat'] = floatval($record['lat']);
        $record['lng'] = floatval($record['lng']);
    }

    if (!empty($anagrafica)) {
        $tipi_anagrafica = $anagrafica->tipi->toArray();
        $tipi_anagrafica = array_column($tipi_anagrafica, 'id');
    }
}
