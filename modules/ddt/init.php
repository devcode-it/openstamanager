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

use Modules\DDT\DDT;

if ($module['name'] == 'Ddt di vendita') {
    $dir = 'entrata';
} else {
    $dir = 'uscita';
}

if (isset($id_record)) {
    $ddt = DDT::with('tipo', 'stato')->find($id_record);

    $record = $dbo->fetchOne('SELECT dt_ddt.*,
        dt_ddt.id AS idddt,
        dt_statiddt.descrizione AS `stato`,
        dt_statiddt.completato AS `flag_completato`,
        dt_tipiddt.descrizione AS `descrizione_tipodoc`,
        an_anagrafiche.tipo AS tipo_anagrafica
    FROM dt_ddt
        LEFT OUTER JOIN dt_statiddt ON dt_ddt.idstatoddt=dt_statiddt.id
        INNER JOIN an_anagrafiche ON dt_ddt.idanagrafica=an_anagrafiche.idanagrafica
        INNER JOIN dt_tipiddt ON dt_ddt.idtipoddt=dt_tipiddt.id
    WHERE dt_ddt.id='.prepare($id_record));

    if (!empty($record)) {
        $record['idporto'] = $record['idporto'] ?: $dbo->fetchOne('SELECT id FROM dt_porto WHERE predefined = 1')['id'];
        $record['idcausalet'] = $record['idcausalet'] ?: $dbo->fetchOne('SELECT id FROM dt_causalet WHERE predefined = 1')['id'];
        $record['idspedizione'] = $record['idspedizione'] ?: $dbo->fetchOne('SELECT id FROM dt_spedizione WHERE predefined = 1')['id'];
    }

    // Se la sede del ddt non è di mia competenza, blocco il ddt in modifica
    $field_name = ($dir == 'entrata') ? 'idsede_partenza' : 'idsede_destinazione';
    if (!Auth::admin() && !in_array($record[$field_name], $user->sedi)) {
        $record['flag_completato'] = 1;
    }
}
