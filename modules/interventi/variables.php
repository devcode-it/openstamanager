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

$r = $dbo->fetchOne('SELECT *,
    (SELECT MAX(orario_fine) FROM in_interventi_tecnici WHERE idintervento=in_interventi.id) AS data_fine,
    (SELECT email FROM an_anagrafiche WHERE an_anagrafiche.idanagrafica=in_interventi.idanagrafica) AS email,
    (SELECT descrizione FROM in_statiintervento WHERE idstatointervento=in_interventi.idstatointervento) AS stato
FROM in_interventi WHERE id='.prepare($id_record));

// Variabili da sostituire
return [
    'email' => $r['email'],
    'numero' => $r['codice'],
    'richiesta' => $r['richiesta'],
    'descrizione' => $r['descrizione'],
    'data' => Translator::dateToLocale($r['data_richiesta']),
    'data richiesta' => Translator::dateToLocale($r['data_richiesta']),
    'data fine intervento' => empty($r['data_fine']) ? Translator::dateToLocale($r['data_richiesta']) : Translator::dateToLocale($r['data_fine']),
    'id_anagrafica' => $r['idanagrafica'],
    'stato' => $r['stato'],
];
