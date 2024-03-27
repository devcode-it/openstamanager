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
use Models\Module;

$dir = $_GET['dir'];

$id_sezionale = filter('id_sezionale');
$sezionale = $dbo->fetchOne('SELECT `zz_segmnets_lang`.`name` FROM `zz_segments` LEFT JOIN `zz_segments_lang` ON (`zz_segments`.`id` = `zz_segments_lang`.`id_record` AND `zz_segments_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `zz_segments`.`id` = '.$id_sezionale)['name'];

$date_start = filter('date_start');
$date_end = filter('date_end');

$tipo = $dir == 'entrata' ? 'vendite' : 'acquisti';
$vendita_banco = (new Module())->getByField('name', 'Vendita al banco', \Models\Locale::getPredefined()->id);

$v_iva = [];
$v_totale = [];

if ((!empty($vendita_banco)) && ($id_sezionale == -1) && ($tipo == 'vendite')) {
    $query = '
    SELECT
        `data_registrazione`,
        `numero_esterno`,
        `data`,
        `codice_tipo_documento_fe`,
        `percentuale`,
        `descrizione`,
        `id`,
        `numero`,
        SUM(`subtotale`) as subtotale,
        SUM(`totale`) as totale,
        SUM(`iva`) AS iva,
        `ragione_sociale`,
        `codice_anagrafica`
    FROM
    (
        SELECT 
        `co_documenti`.`data_registrazione`, 
        `co_documenti`.`numero_esterno`,
        `co_documenti`.`data`,
        `co_tipidocumento`.`codice_tipo_documento_fe`,
        `co_iva`.`percentuale`,
        `co_iva_lang`.`name` as descrizione,
        `co_documenti`.`id` AS id,
        IF(`numero` = "", `numero_esterno`, `numero`) AS numero,
        SUM((`subtotale`-`sconto`+`co_righe_documenti`.`rivalsainps`)*(IF(`co_tipidocumento`.`reversed` = 0, 1,-1 ))) AS subtotale,
        SUM((`subtotale`-`sconto`+`co_righe_documenti`.`rivalsainps`+`iva`+`co_righe_documenti`.`rivalsainps` * `percentuale`/100)*(IF(`co_tipidocumento`.`reversed` = 0, 1,-1 ))) AS totale,
        SUM((`subtotale`-`sconto`+`co_righe_documenti`.`rivalsainps`) *`percentuale`/100 *(100-`indetraibile`)/100 *(IF(`co_tipidocumento`.`reversed` = 0, 1,-1 ))) AS iva,
        `an_anagrafiche`.`ragione_sociale`,
        `an_anagrafiche`.`codice` AS codice_anagrafica
    FROM
        `co_iva`
        LEFT JOIN `co_iva_lang` ON (`co_iva`.`id` = `co_iva_lang`.`id_record` AND `co_iva_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
        INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`idiva` = `co_iva`.`id`
        INNER JOIN `co_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento`
        INNER JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento`
        INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `co_documenti`.`idanagrafica`
    WHERE 
        `dir` = '.prepare($dir).' AND `idstatodocumento` NOT IN (SELECT `id_record` FROM `co_statidocumento_lang` WHERE `name` IN ("Bozza", "Annullata")) AND `is_descrizione` = 0 AND `co_documenti`.`data_competenza` >= '.prepare($date_start).' AND `co_documenti`.`data_competenza` <= '.prepare($date_end).' AND '.(($id_sezionale != -1) ? '`co_documenti`.`id_segment` = '.prepare($id_sezionale).'' : '1=1').'
    GROUP BY 
        `co_iva`.`id`, `co_documenti`.`id`
    UNION
    SELECT 
        `vb_venditabanco`.`data` as data_registrazione, 
        `vb_venditabanco`.`numero` as numero_esterno,
        `vb_venditabanco`.`data` as data,
        "Vendita al banco" as codice_tipo_documento_fe,
        `co_iva`.`percentuale`,
        `co_iva_lang`.`name` as descrizione,
        `vb_venditabanco`.`id` AS id,
        `vb_venditabanco`.`numero` AS numero,
        SUM(`vb_righe_venditabanco`.`subtotale`) as subtotale,
        SUM(`subtotale`-`sconto` + `iva`) as totale,
        SUM(`iva`) as iva,
        `an_anagrafiche`.`ragione_sociale`,
        `an_anagrafiche`.`codice` AS codice_anagrafica
    FROM `co_iva`
        LEFT JOIN `co_iva_lang` ON (`co_iva`.`id` = `co_iva_lang`.`id_record` AND `co_iva_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
        INNER JOIN `vb_righe_venditabanco` ON `vb_righe_venditabanco`.`idiva` = `co_iva`.`id`
        INNER JOIN `vb_venditabanco` ON `vb_venditabanco`.`id` = `vb_righe_venditabanco`.`idvendita`
        INNER JOIN `vb_stati_vendita` ON `vb_venditabanco`.`idstato` = `vb_stati_vendita`.`id`
        LEFT JOIN `in_interventi` ON `vb_righe_venditabanco`.`idintervento` = `in_interventi`.`id`
        LEFT JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `in_interventi`.`idanagrafica`
    WHERE
        `vb_venditabanco`.`data` >= '.prepare($date_start).' AND `vb_venditabanco`.`data` <= '.prepare($date_end).' AND `vb_stati_vendita`.`descrizione` = "Pagato"
    GROUP BY
        `co_iva`.`id`, `id`, `an_anagrafiche`.`idanagrafica`
    ) AS tabella
    GROUP BY
    `iva`, `id`, `data_registrazione`, `data`, `numero_esterno`, `codice_tipo_documento_fe`, `percentuale`, `descrizione`, `numero`, `ragione_sociale`, `codice_anagrafica`
    ORDER BY CAST(`numero_esterno` AS UNSIGNED)';
} else {
    $query = '
SELECT 
    `co_documenti`.`data_registrazione`, 
    `co_documenti`.`numero_esterno`,
    `co_documenti`.`data`,
    `co_tipidocumento`.`codice_tipo_documento_fe`,
    `co_iva`.`percentuale`,
    `co_iva_lang`.`name` as descrizione,
    `co_documenti`.`id` AS id,
    IF(`numero` = "", `numero_esterno`, `numero`) AS numero,
    SUM((`subtotale`-`sconto`+`co_righe_documenti`.`rivalsainps`)*(IF(`co_tipidocumento`.`reversed` = 0, 1,-1 ))) AS subtotale,
    SUM((`subtotale`-`sconto`+`co_righe_documenti`.`rivalsainps`+`iva`+`co_righe_documenti`.`rivalsainps` * `percentuale`/100)*(IF(`co_tipidocumento`.`reversed` = 0, 1,-1 ))) AS totale,
    SUM((`subtotale`-`sconto`+`co_righe_documenti`.`rivalsainps`) *`percentuale`/100 *(100-`indetraibile`)/100 *(IF(`co_tipidocumento`.`reversed` = 0, 1,-1 ))) AS iva,
    `an_anagrafiche`.`ragione_sociale`,
    `an_anagrafiche`.`codice` AS codice_anagrafica
FROM
    `co_iva`
    LEFT JOIN `co_iva_lang` ON (`co_iva`.`id` = `co_iva_lang`.`id_record` AND `co_iva_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
    INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`idiva` = `co_iva`.`id`
    INNER JOIN `co_documenti` ON `co_documenti`.`id` = `co_righe_documenti`.`iddocumento`
    INNER JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento`
    INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `co_documenti`.`idanagrafica`
WHERE 
    `dir` = '.prepare($dir).' AND `idstatodocumento` NOT IN (SELECT `id_record` FROM `co_statidocumento_lang` WHERE `name` IN ("Bozza", "Annullata")) AND `is_descrizione` = 0 AND `co_documenti`.`data_competenza` >= '.prepare($date_start).' AND `co_documenti`.`data_competenza` <= '.prepare($date_end).' AND '.(($id_sezionale != -1) ? '`co_documenti`.`id_segment` = '.prepare($id_sezionale).'' : '1=1').'
GROUP BY 
    `co_iva`.`id`, `co_documenti`.`id`
ORDER BY 
    CAST( IF(`dir`="entrata", `co_documenti`.`numero_esterno`, `co_documenti`.`numero`) AS UNSIGNED)';
}
$records = $dbo->fetchArray($query);

if (empty(get('notdefinitiva'))) {
    $page = $dbo->fetchOne('SELECT `first_page` FROM `co_stampecontabili` WHERE `dir`='.prepare(filter('dir')).' AND  `date_start`='.prepare(filter('date_start')).' AND `date_end`='.prepare(filter('date_end')))['first_page'];
}

// Sostituzioni specifiche
$custom = [
    'tipo' => $tipo,
];
