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
use Modules\Anagrafiche\Anagrafica;

$search_targa = get('search_targa');
$search_nome = get('search_nome');
$dt_carico = get('data_carico');
$data_carico = strtotime(str_replace('/', '-', $dt_carico));
$startTM = date('Y-m-d', $data_carico).' 00:00:00';
$endTM = date('Y-m-d', $data_carico).' 23:59:59';

$query = "
    SELECT
        `mg_movimenti`.`data`,
        `an_sedi`.`targa`,
        `an_sedi`.`nome`,
        `mg_articoli`.`codice`,
        `mg_articoli`.`prezzo_vendita`,
        `co_iva`.`percentuale` AS iva,
        `mg_categorie_lang`.`name` AS subcategoria,
        `mg_articoli_lang`.`name` AS descrizione,
        `mg_movimenti`.`qta`,
        `mg_movimenti`.`idutente`,
        `zz_users`.`username`,
        `mg_articoli`.`um`,
        `zz_groups_lang`.`name` as gruppo
    FROM 
        `mg_movimenti`
        INNER JOIN `mg_articoli` ON `mg_movimenti`.`idarticolo`=`mg_articoli`.`id`
        INNER JOIN `co_iva` ON `mg_articoli`.`idiva_vendita` = `co_iva`.`id`
        INNER JOIN `zz_users` ON `mg_movimenti`.'idutente'=`zz_users`.'id'
        INNER JOIN `zz_groups` ON 'zz_users'.`idgruppo`=`zz_groups`.`id`
        LEFT JOIN `zz_groups_lang` ON (`zz_groups`.`id` = `zz_groups_lang`.`id_record` AND `zz_groups_lang`.`id_lang` = ".prepare(setting('Lingua')).")
        INNER JOIN `an_sedi` ON `mg_movimenti`.`idsede`=`an_sedi`.`id`
        LEFT JOIN `co_iva_lang` ON (`co_iva`.`id` = `co_iva_lang`.`id_record` AND `co_iva_lang`.`id_lang` = ".prepare(setting('Lingua')).")
        LEFT JOIN `mg_categorie` ON `mg_categorie`.`id`=`mg_articoli`.`id_sottocategoria`
        LEFT JOIN `mg_categorie_lang` ON (`mg_categorie`.`id`=`mg_categorie_lang`.`id_record` AND `mg_categorie_lang`.`id_lang` = ".prepare(setting('Lingua')).")
        LEFT JOIN `mg_articoli_lang` ON (`mg_articoli`.`id`=`mg_articoli_lang`.`id_record` AND `mg_articoli_lang`.`id_lang` = ".prepare(setting('Lingua')).")
    WHERE 
        `mg_movimenti`.`qta`>0 AND (`mg_movimenti`.`idsede` > 0) AND (`mg_movimenti`.`idintervento` IS NULL) AND
        ((`mg_movimenti`.`data` BETWEEN ".prepare($startTM)." AND ".prepare($endTM).") AND (`zz_groups_lang`.`name` = 'Amministratori'))";

$query .= ' AND (`an_sedi`.`targa` LIKE '.prepare('%'.$search_targa.'%').') AND (`an_sedi`.`nome` LIKE '.prepare('%'.$search_nome.'%').') ';
$query .= '	ORDER BY `an_sedi`.`targa`, `mg_articoli`.`descrizione`';

$rs = $dbo->fetchArray($query);
$totrows = sizeof($rs);
$azienda = Anagrafica::where('id', setting('Azienda predefinita'))->first();