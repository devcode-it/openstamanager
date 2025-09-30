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
        `an_anagrafiche`.`idanagrafica` AS id_anagrafica,
        `an_anagrafiche`.`email`,
        `an_anagrafiche`.`pec`,
        `an_referenti`.`nome`,
        `in_interventi`.`codice` AS codice,
        (SELECT MAX(`orario_fine`) FROM `in_interventi_tecnici` WHERE `idintervento`=`in_interventi`.`id`) AS data_fine,
        `in_tipiintervento_lang`.`title` AS tipo,
        `in_statiintervento_lang`.`title` AS stato,
        `impianti`.`descrizione` AS impianti,
        `in_interventi`.`descrizione` AS descrizione,
        `an_sedi`.`nomesede` AS sede,
        CONCAT(`an_sedi`.`indirizzo`, \' \', `an_sedi`.`cap`, \'  \', `an_sedi`.`citta`, \' (\', `an_sedi`.`provincia`, \')\') AS sede_indirizzo
    FROM `in_interventi`
        LEFT JOIN `an_sedi` ON `an_sedi`.`id` = `in_interventi`.`idsede_destinazione`
        INNER JOIN `in_statiintervento` ON `in_interventi`.`idstatointervento` = `in_statiintervento`.`id`
        LEFT JOIN `in_statiintervento_lang` ON (`in_statiintervento_lang`.`id_record` = `in_statiintervento`.`id` AND `in_statiintervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
        INNER JOIN `in_tipiintervento` ON `in_interventi`.`idtipointervento` = `in_tipiintervento`.`id`
        LEFT JOIN `in_tipiintervento_lang` ON (`in_tipiintervento_lang`.`id_record` = `in_tipiintervento`.`id` AND `in_tipiintervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
        INNER JOIN `an_anagrafiche` ON `in_interventi`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
        LEFT JOIN `an_referenti` ON `an_referenti`.`id`=`in_interventi`.`idreferente`
        LEFT JOIN (SELECT GROUP_CONCAT(CONCAT(`matricola`, IF(`nome` != "", CONCAT(" - ", `nome`), "")) SEPARATOR "<br>") AS descrizione, `my_impianti_interventi`.`idintervento` FROM `my_impianti` INNER JOIN `my_impianti_interventi` ON `my_impianti`.`id` = `my_impianti_interventi`.`idimpianto` GROUP BY `my_impianti_interventi`.`idintervento`) AS impianti ON `impianti`.`idintervento` = `in_interventi`.`id`
    WHERE `in_interventi`.`id`='.prepare($id_record));

// Variabili da sostituire
return [
    'email' => $options['is_pec'] ? $r['pec'] : $r['email'],
    'numero' => $r['codice'],
    'ragione_sociale' => $r['ragione_sociale'],
    'richiesta' => $r['richiesta'],
    'descrizione' => $r['descrizione'],
    'data' => Translator::dateToLocale($r['data_richiesta']),
    'data richiesta' => Translator::dateToLocale($r['data_richiesta']),
    'data fine intervento' => empty($r['data_fine']) ? Translator::dateToLocale($r['data_richiesta']) : Translator::dateToLocale($r['data_fine']),
    'id_anagrafica' => $r['id_anagrafica'],
    'tipo' => $r['tipo'],
    'stato' => $r['stato'],
    'impianti' => $r['impianti'],
    'nome_referente' => $r['nome'],
    'sede' => $r['sede'],
    'sede_indirizzo' => $r['sede_indirizzo'],
];
