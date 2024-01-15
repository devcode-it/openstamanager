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
$query = '
    SELECT
        an_sedi.targa, an_sedi.nome,
        mg_articoli.codice, mg_articoli.descrizione,
        (SELECT mg_categorie.nome FROM mg_categorie WHERE mg_categorie.id=mg_articoli.id_sottocategoria) AS subcategoria,
        movimenti.qta,
        mg_articoli.um
        FROM an_sedi
        INNER JOIN (SELECT SUM(mg_movimenti.qta) AS qta, idarticolo, idsede FROM mg_movimenti GROUP BY idsede,idarticolo) AS movimenti ON movimenti.idsede = an_sedi.id
        INNER JOIN mg_articoli ON movimenti.idarticolo = mg_articoli.id
    WHERE 
        '.implode(' AND ', $where).'
    ORDER BY 
        an_sedi.targa, an_sedi.descrizione';

$rs = $dbo->fetchArray($query);
$totrows = sizeof($rs);
