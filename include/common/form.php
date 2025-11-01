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

// Form di inserimento riga documento
echo '
<form action="'.base_path_osm().'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'" method="post" id="submit-form">
    <input type="hidden" name="id_plugin" value="'.$id_plugin.'">
    <input type="hidden" name="hash" value="tab_'.$id_plugin.'">
    <input type="hidden" name="backto" value="record-edit">

    <input type="hidden" name="op" value="'.$options['op'].'">
    <input type="hidden" name="idriga" value="'.$result['id'].'">
    <input type="hidden" name="dir" value="'.$options['dir'].'">';

echo '
    |response|';

$button = $options['action'] == 'add' ? tr('Aggiungi') : tr('Modifica');
$icon = $options['action'] == 'add' ? 'fa-plus' : 'fa-pencil';

echo '

    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="button" class="btn btn-primary pull-right" onclick="submitForm()"><i class="fa '.$icon.'"></i> '.$button.'</button>
		</div>
    </div>
</form>';

echo '
<script>$(document).ready(init)</script>';

echo '
<script>
    function submitForm() {
        var form = input("#submit-form");
        salvaForm("#submit-form", {
            id_module: "'.$id_module.'",
            id_record: "'.$id_record.'",
        }).then(function(response) {
            form.getElement().closest("div[id^=bs-popup").modal("hide");
            caricaRighe(null);
        });

        return false;
    }
</script>';
