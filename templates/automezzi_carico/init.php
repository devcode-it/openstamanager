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

$search_targa = get('search_targa');
$search_nome = get('search_nome');
$dt_carico = get('data_carico');
$data_carico = strtotime(str_replace('/', '-', $dt_carico));
$startTM = date('Y-m-d', $data_carico).' 00:00:00';
$endTM = date('Y-m-d', $data_carico).' 23:59:59';

$query = "
    SELECT
        mg_movimenti.data,
        an_sedi.targa,
        an_sedi.nome,
        mg_articoli.codice,
        mg_articoli.prezzo_vendita,
        co_iva.percentuale AS 'iva',
        (SELECT mg_categorie.nome FROM mg_categorie WHERE mg_categorie.id=mg_articoli.id_sottocategoria) AS subcategoria,
        (SELECT mg_articoli.descrizione FROM mg_articoli WHERE mg_articoli.id=mg_movimenti.idarticolo) AS 'descrizione',
        IF( mg_movimenti.movimento LIKE '%Scarico%', mg_movimenti.qta*(-1), mg_movimenti.qta) AS qta,
        mg_movimenti.idutente,
        zz_users.username,
        mg_articoli.um,
        zz_groups.nome as 'gruppo'
    FROM 
        mg_movimenti
        INNER JOIN mg_articoli ON mg_movimenti.idarticolo=mg_articoli.id
        INNER JOIN co_iva ON mg_articoli.idiva_vendita = co_iva.id
        INNER JOIN zz_users ON mg_movimenti.idutente=zz_users.id
        INNER JOIN zz_groups ON zz_users.idgruppo=zz_groups.id
        INNER JOIN an_sedi ON mg_movimenti.idsede=an_sedi.id
    WHERE 
        (mg_movimenti.idsede > 0) AND (mg_movimenti.idintervento IS NULL) AND
        ((mg_movimenti.data BETWEEN ".prepare($startTM).' AND '.prepare($endTM).") AND (zz_groups.nome IN ('Titolari', 'Amministratori')))";

$query .= ' AND (an_sedi.targa LIKE '.prepare('%'.$search_targa.'%').') AND (an_sedi.nome LIKE '.prepare('%'.$search_nome.'%').') ';
$query .= '	ORDER BY an_sedi.targa, mg_articoli.descrizione';

$rs = $dbo->fetchArray($query);
$totrows = sizeof($rs);
$azienda = $dbo->fetchOne('SELECT * FROM an_anagrafiche WHERE idanagrafica='.prepare(setting('Azienda predefinita')));
