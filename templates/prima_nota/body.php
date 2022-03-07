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

include_once __DIR__.'/../../core.php';


echo '
<div class="row">
    <div class="col-md-12 text-center">
        <h3><b>'.tr('Prima nota').'</b></h3>
    </div>
</div>';

echo '
<table class="table table-bordered table-striped">
    <tr>
        <th>#</th>';
    
    foreach($campi as $campo){
        echo '
        <th>'.$campo.'</th>';
    }

echo '
    </tr>';
    $index = 0;

    foreach($records['results'] as $record){
        $record['Data'] = Translator::dateToLocale($record['Data']);
        $record['Dare'] = Translator::numberToLocale($record['Dare'],'qta');
        $record['Avere'] = Translator::numberToLocale($record['Avere'],'qta');
        ++$index;
        
        echo '
        <tr>
            <td>'.$index.'</td>';

        foreach($campi as $campo){
            echo '
            <td>'.$record[$campo].'</td>';
        }
    
        echo '
        </tr>';
    }

echo '
</table>';