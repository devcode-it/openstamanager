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

$r = $dbo->fetchOne('SELECT *,
    an_anagrafiche.ragione_sociale,
    an_anagrafiche.email
FROM co_preventivi INNER JOIN an_anagrafiche ON co_preventivi.idanagrafica=an_anagrafiche.idanagrafica WHERE co_preventivi.id='.prepare($id_record));

$revisione = $dbo->fetchNum('SELECT * FROM co_preventivi WHERE master_revision = (SELECT master_revision FROM co_preventivi WHERE id = '.prepare($id_record).') AND id < '.prepare($id_record)) + 1;

// Variabili da sostituire
return [
    'email' => $r['email'],
    'numero' => $r['numero'],
    'ragione_sociale' => $r['ragione_sociale'],
    'descrizione' => $r['descrizione'],
    'data' => Translator::dateToLocale($r['data_bozza']),
    'id_anagrafica' => $r['idanagrafica'],
    'revisione' => $revisione,
];
