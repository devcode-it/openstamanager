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

use Models\Setting;

include_once __DIR__.'/../../core.php';

$sezione = filter('sezione');
$impostazioni = Setting::where('sezione', $sezione)
    ->get();

foreach ($impostazioni as $impostazione) {
    echo '
    <div class="col-md-6">
        '.Settings::input($impostazione['id']).'
    </div>

    <script>';

    if ($impostazione->tipo == 'time') {
        echo '
    input("setting['.$impostazione->id.']");
    $(document).on("blur", "#setting'.$impostazione->id.'", function (e) {
      salvaImpostazione('.$impostazione->id.', $("#setting'.$impostazione->id.'").val());
    });
    ';
    } else {
        echo '

    input("setting['.$impostazione->id.']").change(function (){
        salvaImpostazione('.$impostazione->id.', input(this).get());
    });';
    }

    echo '
    </script>';
}
