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

include_once __DIR__.'/../../../core.php';

$rs = $dbo->fetchArray('
SELECT 
	`co_contratti`.`id`,
    ((SELECT SUM(`co_righe_contratti`.`qta`) FROM `co_righe_contratti` WHERE `co_righe_contratti`.`um` = "ore" AND `co_righe_contratti`.`idcontratto` = `co_contratti`.`id`) - IFNULL((SELECT SUM(`in_interventi_tecnici`.`ore`) FROM `in_interventi_tecnici` INNER JOIN `in_interventi` ON `in_interventi_tecnici`.`idintervento` = `in_interventi`.`id` WHERE `in_interventi`.`id_contratto` = `co_contratti`.`id` AND `in_interventi`.`idstatointervento` IN (SELECT `in_statiintervento`.`id` FROM `in_statiintervento` WHERE `in_statiintervento`.`is_bloccato` = 1)),0)) AS `ore_rimanenti`,
    `co_contratti`.`nome`, 
    DATEDIFF(`data_conclusione`, NOW()) AS giorni_rimanenti, 
    `co_contratti`.`data_accettazione`,
    `data_conclusione`, 
    `ore_preavviso_rinnovo`, 
    `giorni_preavviso_rinnovo`, 
    (SELECT `ragione_sociale` FROM `an_anagrafiche` WHERE `idanagrafica` = `co_contratti`.`idanagrafica`) AS ragione_sociale 
FROM 
	`co_contratti` 
    INNER JOIN `co_staticontratti` ON `co_staticontratti`.`id` = `co_contratti`.`idstato` 
    LEFT JOIN `co_staticontratti_lang` ON (`co_staticontratti`.`id` = `co_staticontratti_lang`.`id_record` AND `co_staticontratti_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
WHERE 
	`rinnovabile` = 1 
    AND YEAR(`data_conclusione`) > 1970 
    AND `co_contratti`.`id` NOT IN (SELECT `idcontratto_prev` FROM `co_contratti` contratti) AND `co_staticontratti_lang`.`title` NOT IN ("Concluso", "Rifiutato", "Bozza") 
HAVING 
    (`ore_rimanenti` <= `ore_preavviso_rinnovo` OR DATEDIFF(`data_conclusione`, NOW()) <= ABS(`giorni_preavviso_rinnovo`)) 
ORDER BY 
    `giorni_rimanenti` ASC,`ore_rimanenti` ASC');

if (!empty($rs)) {
    echo '
<table class="table table-hover">
    <tr>
        <th width="50%">'.tr('Contratto').'</th>
        <th width="15%" class="text-center">'.tr('Data inizio').'</th>
        <th width="15%" class="text-center">'.tr('Data conclusione').'</th>
        <th width="20%">'.tr('Scadenza').'</th>
    </tr>';

    foreach ($rs as $r) {
        $data_accettazione = !empty($r['data_accettazione']) ? Translator::dateToLocale($r['data_accettazione']) : '';

        $data_conclusione = !empty($r['data_conclusione']) ? Translator::dateToLocale($r['data_conclusione']) : '';

        // Se scaduto, segna la riga in rosso
        $class = (strtotime((string) $r['data_conclusione']) < strtotime(date('Y-m-d')) && !empty($data_conclusione)) ? 'danger' : '';

        if (isset($r['ore_rimanenti'])) {
            // Se ore finite, segna la riga in rosso
            if ($class != 'danger') {
                $class = ($r['ore_rimanenti'] < 0) ? 'warning' : '';
            }

            $ore_rimanenti = ($r['ore_rimanenti'] >= 0) ? tr('ore rimanenti: _NUM_') : tr('ore in aggiunta: _NUM_');
            $ore_rimanenti = str_replace('_NUM_', abs($r['ore_rimanenti']), $ore_rimanenti);
        }

        if ($r['giorni_rimanenti'] > 0) {
            if ($r['giorni_rimanenti'] > 1) {
                $scadenza = tr('tra _DAYS_ giorni');
            } else {
                $scadenza = tr('tra _DAYS_ giorno');
            }
        } else {
            $scadenza = tr('scaduto da _DAYS_ giorni');
        }

        $scadenza = str_replace('_DAYS_', abs($r['giorni_rimanenti']), $scadenza);

        echo '
    <tr class="'.$class.'">
        <td>
            '.Modules::link('Contratti', $r['id'], $r['nome']).'<br>
            <small class="help-block">'.$r['ragione_sociale'].'</small>
        </td>
        <td class="text-center">'.$data_accettazione.'</td>
        <td class="text-center">'.$data_conclusione.'</td>
        <td>'.$scadenza.(isset($r['ore_rimanenti']) ? ' ('.$ore_rimanenti.')' : '').'</td>
    </tr>';
    }
    echo '
</table>';
} else {
    echo '
<p>'.tr('Non ci sono contratti in scadenza').'.</p>';
}
