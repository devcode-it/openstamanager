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

// Recupero parametri
$data_inizio = filter('data_inizio');
$data_fine = filter('data_fine');

// Recupero dati automezzo
$automezzo = $dbo->fetchOne('SELECT * FROM an_sedi WHERE id='.prepare($id_record));

// Recupero viaggi nel periodo
$query = '
SELECT 
    v.*,
    t.ragione_sociale as tecnico_nome
FROM 
    an_automezzi_viaggi v
    LEFT JOIN an_anagrafiche t ON v.idtecnico = t.idanagrafica
WHERE 
    v.idsede = '.prepare($id_record).'
    AND v.data_inizio >= '.prepare($data_inizio).'
    AND v.data_fine <= '.prepare($data_fine).'
ORDER BY 
    v.data_inizio ASC';

$records = $dbo->fetchArray($query);

// Recupero rifornimenti per ogni viaggio
$rifornimenti_per_viaggio = [];
foreach ($records as $viaggio) {
    $rifornimenti = $dbo->fetchArray('
        SELECT
            r.*,
            tc.descrizione AS id_carburante_desc,
            tc.um AS id_carburante_um,
            g.descrizione AS gestore_desc
        FROM an_automezzi_rifornimenti r
        LEFT JOIN an_automezzi_tipi_carburante tc ON r.id_carburante = tc.id
        LEFT JOIN an_automezzi_gestori g ON r.id_gestore = g.id
        WHERE r.idviaggio = '.prepare($viaggio['id']).'
        ORDER BY r.data ASC
    ');
    $rifornimenti_per_viaggio[$viaggio['id']] = $rifornimenti;
}
