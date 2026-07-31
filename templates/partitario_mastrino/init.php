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
$azienda = $dbo->fetchOne('SELECT ragione_sociale FROM an_anagrafiche WHERE id='.setting('Azienda predefinita'));
$date_start = $_SESSION['period_start'];
$date_end = $_SESSION['period_end'];
$saldo_iniziale = 0;
$i = 0;
$records = [];

if (get('lev') == '3') {
    $conto3 = $dbo->fetchOne('SELECT * FROM co_piano_dei_conti3 WHERE id='.prepare($id_record));
    $conto2 = $dbo->fetchOne('SELECT * FROM co_piano_dei_conti2 WHERE id='.prepare($conto3['id_piano_dei_conti2']));
    $conto1 = $dbo->fetchOne('SELECT * FROM co_piano_dei_conti1 WHERE id='.prepare($conto2['id_piano_dei_conti1']));

    // Se il conto è di stato patrimoniale, devo raggruppare i movimenti anche per segno
    $group_by = $conto1['descrizione'] == 'Patrimoniale' ? ', IF(`totale`>0, 1, 0)' : '';

    // Movimenti
    $records = $dbo->fetchArray('SELECT co_movimenti.*, SUM(totale) AS totale FROM co_movimenti LEFT JOIN co_documenti ON co_movimenti.id_documento=co_documenti.id WHERE co_movimenti.id_conto='.prepare($id_record).' AND co_movimenti.data>='.prepare($date_start).' AND co_movimenti.data<='.prepare($date_end).'
    GROUP BY 
        `co_movimenti`.`id_mastrino` '.$group_by.'
    ORDER BY 
        co_movimenti.data, CAST(co_documenti.numero AS UNSIGNED), CAST(co_documenti.numero_esterno AS UNSIGNED)');

    // Calcolo saldo iniziale se non è presente nessun movimento di apertura tra i movimenti del periodo
    $has_movimento_apertura = false;
    foreach ($records as $record) {
        if ($record['is_apertura'] == 1) {
            $has_movimento_apertura = true;
            continue;
        }
    }

    if (empty($has_movimento_apertura)) {
        $saldo_iniziale = $dbo->fetchOne('SELECT SUM(totale) AS totale FROM co_movimenti WHERE id_conto='.prepare($id_record).' AND co_movimenti.data<'.prepare($date_start).' GROUP BY id_conto')['totale'];
        $data_saldo_iniziale = date('Y-m-d', strtotime($date_start.' -1 day'));
    }
} elseif (get('lev') == '2') {
    $records = $dbo->fetchArray('SELECT CONCAT(co_piano_dei_conti3.numero, " ",co_piano_dei_conti3.descrizione) AS descrizione, SUM(totale) AS totale FROM `co_movimenti` INNER JOIN co_piano_dei_conti3 ON co_movimenti.id_conto=co_piano_dei_conti3.id WHERE id_conto IN(SELECT id FROM co_piano_dei_conti3 WHERE id_piano_dei_conti2='.prepare($id_record).') AND co_movimenti.data>='.prepare($date_start).' AND co_movimenti.data<='.prepare($date_end).' GROUP BY id_conto HAVING totale!=0');
    $conto2 = $dbo->fetchOne('SELECT * FROM co_piano_dei_conti2 WHERE id='.prepare($id_record));
    $conto1 = $dbo->fetchOne('SELECT * FROM co_piano_dei_conti1 WHERE id='.prepare($conto2['id_piano_dei_conti1']));
} elseif (get('lev') == '1') {
    $records = $dbo->fetchArray('SELECT CONCAT(co_piano_dei_conti2.numero, " ", co_piano_dei_conti2.descrizione) AS titolo, CONCAT(co_piano_dei_conti3.numero, " ",co_piano_dei_conti3.descrizione) AS descrizione, SUM(totale) AS totale FROM `co_movimenti` INNER JOIN co_piano_dei_conti3 ON co_movimenti.id_conto=co_piano_dei_conti3.id INNER JOIN co_piano_dei_conti2 ON co_piano_dei_conti3.id_piano_dei_conti2=co_piano_dei_conti2.id WHERE id_conto IN(SELECT id FROM co_piano_dei_conti3 WHERE id_piano_dei_conti2 IN(SELECT id FROM co_piano_dei_conti2 WHERE id_piano_dei_conti1='.prepare($id_record).')) AND co_movimenti.data>='.prepare($date_start).' AND co_movimenti.data<='.prepare($date_end).' GROUP BY id_conto HAVING totale!=0 ORDER BY co_piano_dei_conti2.numero, co_piano_dei_conti3.numero');
    $conto1 = $dbo->fetchOne('SELECT * FROM co_piano_dei_conti1 WHERE id='.prepare($id_record));
    $utile_perdita = $dbo->fetchOne('SELECT SUM(totale) AS totale FROM `co_movimenti` WHERE id_conto IN(SELECT id FROM co_piano_dei_conti3 WHERE id_piano_dei_conti2 IN(SELECT id FROM co_piano_dei_conti2 WHERE id_piano_dei_conti1=(SELECT id FROM co_piano_dei_conti1 WHERE descrizione="Economico")))AND co_movimenti.data>='.prepare($date_start).' AND co_movimenti.data<='.prepare($date_end));
    $patrimoniale = $dbo->fetchArray('SELECT CONCAT(co_piano_dei_conti2.numero, " ", co_piano_dei_conti2.descrizione) AS titolo, CONCAT(co_piano_dei_conti3.numero, " ",co_piano_dei_conti3.descrizione) AS descrizione, SUM(totale) AS totale FROM `co_movimenti` INNER JOIN co_piano_dei_conti3 ON co_movimenti.id_conto=co_piano_dei_conti3.id INNER JOIN co_piano_dei_conti2 ON co_piano_dei_conti3.id_piano_dei_conti2=co_piano_dei_conti2.id WHERE id_conto IN(SELECT id FROM co_piano_dei_conti3 WHERE id_piano_dei_conti2 IN(SELECT id FROM co_piano_dei_conti2 WHERE id_piano_dei_conti1=(SELECT id FROM co_piano_dei_conti1 WHERE descrizione="Patrimoniale"))) AND co_movimenti.data>='.prepare($date_start).' AND co_movimenti.data<='.prepare($date_end).' GROUP BY id_conto HAVING totale!=0 ORDER BY co_piano_dei_conti2.numero');
}
$prev_titolo = '';
