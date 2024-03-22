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

$azienda = $dbo->fetchOne('SELECT * FROM an_anagrafiche WHERE idanagrafica='.prepare(setting('Azienda predefinita')));

$where = [];
$search_targa = get('search_targa');
$search_nome = get('search_nome');

$where[] = 'movimenti.qta > 0';
$where[] = 'movimenti.qta > 0';
if ($search_targa) {
    $where[] = 'an_sedi.targa like '.prepare('%'.$search_targa.'%');
}
if ($search_nome) {
    $where[] = 'an_sedi.nome like '.prepare('%'.$search_nome.'%');
}

// Ciclo tra gli articoli selezionati
$query = 'SELECT
        `an_sedi`.`targa`,
        `an_sedi`.`nome`,
        `mg_articoli`.`codice`,
        `mg_articoli_lang`.`name` as descrizione,
        `mg_categorie_lang`.`name` AS subcategoria,
        SUM(`mg_movimenti`.`qta`) AS qta,
        `mg_articoli`.`um`
    FROM 
        `an_sedi`
        INNER JOIN `mg_movimenti` ON `mg_movimenti`.`idsede` = `an_sedi`.`id`
        INNER JOIN `mg_articoli` ON `mg_movimenti`.`idarticolo` = `mg_articoli`.`id`
        LEFT JOIN `mg_articoli_lang` ON (`mg_articoli`.`id`=`mg_articoli_lang`.`id_record` AND `mg_articoli_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
        LEFT JOIN `mg_categorie` ON `mg_categorie`.`id` = `mg_articoli`.`id_sottocategoria`
        LEFT JOIN `mg_categorie_lang` ON (`mg_categorie`.`id`=`mg_categorie_lang`.`id_record` AND `mg_categorie_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
    WHERE
        '.implode(' AND ', $where).'
    GROUP BY 
        `an_sedi`.`targa`, `an_sedi`.`nome`, `an_sedi`.`descrizione`, `mg_articoli`.`codice`, `mg_articoli_lang`.`name`, `mg_categorie_lang`.`name`, `mg_articoli`.`um`
    ORDER BY 
        `an_sedi`.`targa`, `an_sedi`.`descrizione`';

$rs = $dbo->fetchArray($query);
$totrows = sizeof($rs);
