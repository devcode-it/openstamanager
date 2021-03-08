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

use Models\Upload;

$id_allegato = filter('id_allegato');
$allegato = Upload::find($id_allegato);

// Form di inserimento riga documento
echo '
<form action="" method="post" id="modifica-allegato">
    <input type="hidden" name="id_allegato" value="'.$id_allegato.'">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="modifica-allegato">

   <div class="row">
		<div class="col-md-6">
            {[ "type": "text", "label": "'.tr('Nome').'", "name": "nome_allegato", "value": "'.$allegato->name.'" ]}
        </div>

        <div class="col-md-6">
            {[ "type": "text", "label": "'.tr('Categoria').'", "name": "categoria_allegato", "value": "'.$allegato->category.'", "disabled": "'.intval(in_array($allegato->category, ['Fattura Elettronica'])).'" ]}
        </div>
    </div>

    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary pull-right">
			    <i class="fa fa-edit"></i> '.tr('Modifica').'
			</button>
		</div>
    </div>
</form>';

// Elenco categoria disponibili per l'autocompletamento
$where = '`id_module` '.(!empty($allegato['id_module']) && empty($allegato['id_plugin']) ? '= '.prepare($allegato['id_module']) : 'IS NULL').' AND `id_plugin` '.(!empty($allegato['id_plugin']) ? '= '.prepare($allegato['id_plugin']) : 'IS NULL').'';
$categories = $dbo->fetchArray('SELECT DISTINCT(BINARY `category`) AS `category` FROM `zz_files` WHERE '.$where.' ORDER BY `category`');
$source = array_clean(array_column($categories, 'category'));

echo '
<script>
// Auto-completamento categoria
$("#modifica-allegato #categoria_allegato").autocomplete({
    source: '.json_encode($source).',
    minLength: 0
}).focus(function() {
    $(this).autocomplete("search", $(this).val())
});
</script>
<script>$(document).ready(init)</script>';
