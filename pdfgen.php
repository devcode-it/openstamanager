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

include_once __DIR__.'/core.php';

$directory = !empty($directory) ? $directory : null;
$id_print = get('id_print');

// Retrocompatibilitaà
$ptype = get('ptype');
if (!empty($ptype)) {
    $print = $dbo->fetchArray('SELECT id, previous FROM zz_prints WHERE directory = '.prepare($ptype).' ORDER BY predefined DESC LIMIT 1');
    $id_print = $print[0]['id'];

    $id_record = !empty($id_record) ? $id_record : get($print[0]['previous']);
}

$result = Prints::render($id_print, $id_record, $directory);

if (empty($result)) {
    echo '
        <div class="text-center">
    		<h3 class="text-muted">
    		    <i class="fa fa-question-circle"></i> '.tr('Record non trovato').'
                <br><br>
                <small class="help-block">'.tr('Stai cercando di accedere ad un record eliminato o non presente').'.</small>
            </h3>
            <br>

            <a class="btn btn-default" href="'.base_path().'/index.php">
                <i class="fa fa-chevron-left"></i> '.tr('Indietro').'
            </a>
        </div>';
}
