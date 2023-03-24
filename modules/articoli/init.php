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

use Modules\Articoli\Articolo;

if (isset($id_record)) {
    $articolo = Articolo::withTrashed()->find($id_record);
    $articolo->nome_variante;

    $gestisciMagazzini = $dbo->fetchOne('SELECT * FROM zz_settings WHERE nome = "Gestisci soglia minima per magazzino"');

    if ($gestisciMagazzini['valore'] == '1') {
        $articoloSedeLegale = $dbo->fetchOne(
            'SELECT "0" as id_sede , CONCAT("Sede legale - ", citta) as nomesede, mgas.id_articolo, mgas.threshold_qta
            FROM an_anagrafiche ana
            LEFT JOIN mg_articoli_sedi mgas
            ON mgas.id_sede = " "  AND mgas.id_articolo = ' . prepare($id_record) . '
            WHERE ana.idanagrafica = 1'
        );

        $articoloSedi = $dbo->fetchArray(
            'SELECT ans.id as id_sede, ans.nomesede, mgas.id_articolo, mgas.threshold_qta
            FROM an_sedi ans
            LEFT JOIN mg_articoli_sedi mgas
            ON ans.id = mgas.id_sede AND mgas.id_articolo = ' . prepare($id_record) . '
            WHERE ans.idanagrafica = 1'
        );
    } else {
        $articoloSedeLegale = $dbo->fetchOne(
            'SELECT "0" as id_sede , CONCAT("Sede legale - ", citta) as nomesede, mga.id as id_articolo, mga.threshold_qta
            FROM an_anagrafiche ana
            LEFT JOIN mg_articoli mga
            ON ana.idanagrafica = 1
            WHERE mga.id = ' . prepare($id_record) . ''
        );

        $articoloSedi = [];
    }

    $record = $dbo->fetchOne('SELECT *, (SELECT COUNT(id) FROM mg_prodotti WHERE id_articolo = mg_articoli.id) AS serial FROM mg_articoli WHERE id='.prepare($id_record));
}
