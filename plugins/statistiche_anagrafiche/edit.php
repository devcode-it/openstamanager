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

echo '
<hr>
<div class="box box-warning">
    <div class="box-header">
        <h4 class="box-title">
            '.tr('Periodi temporali').'
        </h4>
        <div class="box-tools pull-right">
            <button class="btn btn-warning btn-xs" onclick="add_calendar()">
                <i class="fa fa-plus"></i> '.tr('Aggiungi periodo').'
            </button>
            <button type="button" class="btn btn-box-tool" data-widget="collapse">
                <i class="fa fa-minus"></i>
            </button>
        </div>
    </div>

    <div class="box-body collapse in" id="calendars">

    </div>
</div>

<div id="widgets">

</div>';

$statistiche = Modules::get('Statistiche');

if ($statistiche != null) {
    echo '
    <script src="'.$statistiche->fileurl('js/functions.js').'"></script>
    <script src="'.$statistiche->fileurl('js/manager.js').'"></script>
    <script src="'.$statistiche->fileurl('js/calendar.js').'"></script>
    <script src="'.$statistiche->fileurl('js/stat.js').'"></script>
    <script src="'.$statistiche->fileurl('js/stats/table.js').'"></script>
    <script src="'.$statistiche->fileurl('js/stats/widget.js').'"></script>';
}

echo '
<script>
var local_url = "'.str_replace('edit.php', '', $structure->fileurl('edit.php')).'";

function init_calendar(calendar) {
    var widgets = new Widget(calendar, "info.php", {}, "#widgets");

    calendar.addElement(widgets);
}
</script>';

if ($statistiche != null) {
    echo '
    <script src="'.$statistiche->fileurl('js/init.js').'"></script>';
}
