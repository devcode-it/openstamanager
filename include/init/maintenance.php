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

if ($config['maintenance_ip'] != $_SERVER['REMOTE_ADDR']) {
    include_once App::filepath('include|custom|', 'top.php');
    $img = App::getPaths()['img'];

    echo '
    <div class="box box-center-large box-danger">
        <div class="box-header with-border text-center">
            <img src="'.$img.'/logo_completo.png" width="300" alt="'.tr('OSM Logo').'">
        </div>

        <div class="box-body">
            <div class="box box-center box-danger box-solid text-center">
                <div class="box-header with-border">
                    <h3 class="box-title">'.tr('Manutenzione in corso!').'</h3>
                </div>
                <div class="box-body">
                    <p>'.tr('Il software si trova attualmente in modalità manutenzione, siete pregati di attendere sino alla conclusione dell\'intervento').'.</p>
                </div>
            </div>
        </div>
    </div>';

    include_once App::filepath('include|custom|', 'bottom.php');

    exit;
}
