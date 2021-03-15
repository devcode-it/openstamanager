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
$azienda = $dbo->fetchOne('SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica='.setting('Azienda predefinita'));
$date_start = session('period_start');
$date_end = session('period_end');

$records = $dbo->fetchArray('SELECT co_movimenti.*, co_pianodeiconti3.descrizione AS conto, co_pianodeiconti2.numero AS numero2, co_pianodeiconti3.numero, SUM(co_movimenti.totale) AS totale FROM co_movimenti INNER JOIN co_pianodeiconti3 ON co_movimenti.idconto=co_pianodeiconti3.id INNER JOIN co_pianodeiconti2 ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id WHERE co_movimenti.data>='.prepare($date_start).' AND co_movimenti.data<='.prepare($date_end).' GROUP BY co_movimenti.idmastrino, co_movimenti.idconto ORDER BY co_movimenti.data, co_movimenti.idmastrino');
