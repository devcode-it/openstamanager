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

use Modules\AttributiCombinazioni\ValoreAttributo;

$id_valore = filter('id_valore');
$testo_valore = '';
if (!empty($id_valore)) {
    $valore = ValoreAttributo::find($id_valore);
    $testo_valore = $valore->nome;
}

echo '
<form action="" method="post" id="form-valore">
    <input type="hidden" name="op" value="gestione-valore">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="id_valore" value="'.$id_valore.'">

    <div class="row">
        <div class="col-md-12">
			{[ "type": "text", "label": "'.tr('Valore').'", "name": "nome", "value": "'.$testo_valore.'", "required": 1 ]}
		</div>
    </div>

    <!-- PULSANTI -->
    <div class="row">
        <div class="col-md-12 text-right">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> '.tr('Salva').'
            </button>
        </div>
    </div>
</form>';
