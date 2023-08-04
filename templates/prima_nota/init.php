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

use Util\Query;

include_once __DIR__.'/../../core.php';

$id_module = Modules::get('Prima nota')['id'];
$structure = Modules::get($id_module);

if (!empty($_SESSION['superselect']['mastrini'])) {
    $id_record = $_SESSION['superselect']['mastrini'];
    $where = 'co_movimenti.idmastrino IN ('.implode(',', $id_record).')';
    $id_record = json_decode($righe);
    unset($_SESSION['superselect']['mastrini']);
} else {
    $where = 'co_movimenti.idmastrino='.prepare($id_record);
}

// RISULTATI VISIBILI
Util\Query::setSegments(false);
$query = Query::getQuery($structure, $where, 0, []);
$query = Modules::replaceAdditionals($id_module, $query);
$query = str_replace('1=1', '1=1 AND '.$where, $query);

$records = Query::executeAndCount($query);

$campi = [];
foreach ($records['results'][0] as $key => $value) {
    $campi[] = $key;
}
unset($campi[0]);
