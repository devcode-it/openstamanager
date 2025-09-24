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
use Models\Module;

echo '
<hr>
<div class="card card-warning">
    <div class="card-header">
        <h4 class="card-title">
            '.tr('Periodi temporali').'
        </h4>
        <div class="card-tools pull-right">
            <button class="btn btn-warning btn-xs" onclick="add_calendar()">
                <i class="fa fa-plus"></i> '.tr('Aggiungi periodo').'
            </button>
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fa fa-plus"></i>
            </button>
        </div>
    </div>

    <div class="card-body collapse in" id="calendars">

    </div>
</div>

<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">
            <span class="tip" title="'.tr('La statistica considera le fatture di acquisto nel periodo temporale definito').'"><i class="fa fa-question-circle"></i></span>
            '.tr('Prezzo medio acquisto').'
        </h3>
    </div>

    <div class="card-body">
        <table class="table table-striped table-sm table-bordered">
            <thead>
                <tr>
                    <th class="text-center">#</th>
                    <th>'.tr('Periodo').'</th>
                    <th>'.tr('Prezzo minimo').'</th>
                    <th>'.tr('Prezzo medio').'</th>
                    <th>'.tr('Prezzo massimo').'</th>
                    <th>'.tr('Oscillazione').'</th>
                    <th>'.tr('Oscillazione in %').'</th>
                    <th>'.tr('Andamento prezzo').'</th>
                </tr>
            </thead>
            <tbody id="prezzi_acquisto">

            </tbody>
        </table>
    </div>
</div>

<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">
            <span class="tip" title="'.tr('La statistica considera le fatture di vendita nel periodo temporale definito').'"><i class="fa fa-question-circle"></i></span>
            '.tr('Prezzo medio vendita').'
        </h3>
    </div>

    <div class="card-body">
        <table class="table table-striped table-sm table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>'.tr('Periodo').'</th>
                    <th>'.tr('Prezzo minimo').'</th>
                    <th>'.tr('Prezzo medio').'</th>
                    <th>'.tr('Prezzo massimo').'</th>
                    <th>'.tr('Oscillazione').'</th>
                    <th>'.tr('Oscillazione in %').'</th>
                    <th>'.tr('Andamento prezzo').'</th>
                </tr>
            </thead>
            <tbody id="prezzi_vendita">

            </tbody>
        </table>
    </div>
</div>';

$statistiche = Module::where('name', 'Statistiche')->first();

if ($statistiche != null) {
    echo '
    <script src="'.$statistiche->fileurl('js/functions.js').'"></script>
    <script src="'.$statistiche->fileurl('js/manager.js').'"></script>
    <script src="'.$statistiche->fileurl('js/calendar.js').'"></script>
    <script src="'.$statistiche->fileurl('js/stat.js').'"></script>
    <script src="'.$statistiche->fileurl('js/stats/table.js').'"></script>';
}

echo '

<script src="'.$structure->fileurl('js/prezzo.js').'"></script>

<script>
var local_url = "'.str_replace('edit.php', '', $structure->fileurl('edit.php')).'";

function init_calendar(calendar) {
    var prezzo_acquisto = new Prezzo(calendar, "#prezzi_acquisto", "uscita");
    var prezzo_vendita = new Prezzo(calendar, "#prezzi_vendita", "entrata");

    calendar.addElement(prezzo_acquisto);
    calendar.addElement(prezzo_vendita);
}
</script>';

if ($statistiche != null) {
    echo '
    <script src="'.$statistiche->fileurl('js/init.js').'"></script>';
}
