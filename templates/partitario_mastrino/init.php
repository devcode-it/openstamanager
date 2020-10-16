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
$azienda = $dbo->fetchOne('SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica='.setting('Azienda predefinita'));
$date_start = $_SESSION['period_start'];
$date_end = $_SESSION['period_end'];

if (get('lev') == '3') {
    $conto3 = $dbo->fetchOne('SELECT * FROM co_pianodeiconti3 WHERE id='.prepare($id_record));
    $conto2 = $dbo->fetchOne('SELECT * FROM co_pianodeiconti2 WHERE id='.prepare($conto3['idpianodeiconti2']));
    $conto1 = $dbo->fetchOne('SELECT * FROM co_pianodeiconti1 WHERE id='.prepare($conto2['idpianodeiconti1']));
    // Movimenti
    $records = $dbo->fetchArray('SELECT * FROM co_movimenti WHERE idconto='.prepare($id_record).' AND co_movimenti.data>='.prepare($date_start).' AND co_movimenti.data<='.prepare($date_end));
} elseif (get('lev') == '2') {
    $records = $dbo->fetchArray('SELECT CONCAT(co_pianodeiconti3.numero, " ",co_pianodeiconti3.descrizione) AS descrizione, SUM(totale) AS totale FROM `co_movimenti` INNER JOIN co_pianodeiconti3 ON co_movimenti.idconto=co_pianodeiconti3.id WHERE idconto IN(SELECT id FROM co_pianodeiconti3 WHERE idpianodeiconti2='.prepare($id_record).') GROUP BY idconto HAVING totale!=0');
    $conto2 = $dbo->fetchOne('SELECT * FROM co_pianodeiconti2 WHERE id='.prepare($id_record));
    $conto1 = $dbo->fetchOne('SELECT * FROM co_pianodeiconti1 WHERE id='.prepare($conto2['idpianodeiconti1']));
} elseif (get('lev') == '1') {
    $records = $dbo->fetchArray('SELECT CONCAT(co_pianodeiconti2.numero, " ", co_pianodeiconti2.descrizione) AS titolo, CONCAT(co_pianodeiconti3.numero, " ",co_pianodeiconti3.descrizione) AS descrizione, SUM(totale) AS totale FROM `co_movimenti` INNER JOIN co_pianodeiconti3 ON co_movimenti.idconto=co_pianodeiconti3.id INNER JOIN co_pianodeiconti2 ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id WHERE idconto IN(SELECT id FROM co_pianodeiconti3 WHERE idpianodeiconti2 IN(SELECT id FROM co_pianodeiconti2 WHERE idpianodeiconti1='.prepare($id_record).')) GROUP BY idconto HAVING totale!=0 ORDER BY co_pianodeiconti2.numero');
    $conto1 = $dbo->fetchOne('SELECT * FROM co_pianodeiconti1 WHERE id='.prepare($id_record));
    $risultato = $dbo->fetchOne('SELECT SUM(totale) AS totale FROM `co_movimenti` INNER JOIN co_pianodeiconti3 ON co_movimenti.idconto=co_pianodeiconti3.id INNER JOIN co_pianodeiconti2 ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id WHERE idconto IN(SELECT id FROM co_pianodeiconti3 WHERE idpianodeiconti2 IN(SELECT id FROM co_pianodeiconti2 WHERE idpianodeiconti1=(SELECT id FROM co_pianodeiconti1 WHERE descrizione="Economico")))');
}
$prev_titolo = '';
