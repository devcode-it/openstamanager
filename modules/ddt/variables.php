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

$r = $dbo->fetchOne('SELECT dt_ddt.*,
    IF((an_referenti.email IS NOT NULL AND an_referenti.email != ""), an_referenti.email, an_anagrafiche.email) AS email,
    an_anagrafiche.pec
FROM dt_ddt
    INNER JOIN an_anagrafiche ON dt_ddt.idanagrafica = an_anagrafiche.idanagrafica
    LEFT OUTER JOIN an_referenti ON an_referenti.id=dt_ddt.idreferente
WHERE id='.prepare($id_record));

// Variabili da sostituire
return [
    'email' => $options['is_pec'] ? $r['pec'] : $r['email'],
    'numero' => empty($r['numero_esterno']) ? $r['numero'] : $r['numero_esterno'],
    'note' => $r['note'],
    'data' => Translator::dateToLocale($r['data']),
    'id_anagrafica' => $r['idanagrafica'],
];
