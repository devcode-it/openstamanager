<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

switch ($resource) {
    case 'get_conti':
        $idmastrino = get('idmastrino');
        $conti = [];
        $rs_conti = $dbo->fetchArray('SELECT *, (SELECT CONCAT ((SELECT numero FROM co_pianodeiconti2 WHERE id=co_pianodeiconti3.idpianodeiconti2), ".", numero, " ", descrizione) FROM co_pianodeiconti3 WHERE id=co_movimenti_modelli.idconto) AS descrizione_conto FROM co_movimenti_modelli WHERE idmastrino='.prepare($idmastrino).' GROUP BY id ORDER BY id');

        for ($i = 0; $i < sizeof($rs_conti); ++$i) {
            $conti[$i] = $rs_conti[$i]['idconto'].';'.$rs_conti[$i]['descrizione_conto'];
        }

        echo implode(',', $conti);

        break;
}
