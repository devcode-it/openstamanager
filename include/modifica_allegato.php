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

use Models\Module;
use Models\Upload;

$id_allegati = (array) json_decode(filter('id_allegati'));

// Form di inserimento riga documento
echo '
<form action="" method="post" id="modifica-allegato">
    <input type="hidden" name="id_allegati" value="'.implode(';', $id_allegati).'">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="modifica-allegato">

    <div class="row">';
if (sizeof($id_allegati) == 1) {
    $allegato = Upload::find($id_allegati[0]);
    echo '
		<div class="col-md-6">
            {[ "type": "text", "label": "'.tr('Nome').'", "name": "nome_allegato", "value": "'.$allegato->name.'" ]}
        </div>
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Categoria').'", "name": "categoria_allegato", "ajax-source": "categorie-files", "value": "'.$allegato->id_category.'", "disabled": "'.intval(in_array($allegato->categoria->name, ['Fattura elettronica'])).'", "icon-after": "add|'.Module::where('name', 'Categorie file')->first()->id.'" ]}
        </div>';
} else {
    $allegato = Upload::find($id_allegati[0]);
    echo '
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Categoria').'", "name": "categoria_allegato", "ajax-source": "categorie-files", "value": "", "icon-after": "add|'.Module::where('name', 'Categorie file')->first()->id.'" ]}
        </div>';
}
echo '
    </div>

    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary pull-right">
			    <i class="fa fa-edit"></i> '.tr('Modifica').'
			</button>
		</div>
    </div>
</form>

<script>$(document).ready(init)</script>';
