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

include_once __DIR__.'/core.php';

// Inclusione elementi fondamentali del modulo
include base_dir().'/actions.php';

// Controllo dei permessi
if (empty($id_plugin)) {
    Permissions::check('rw');
}

// Caricamento template
echo '
<div id="form_'.$id_module.'-'.$id_plugin.'">
';

include !empty(get('edit')) ? $structure->getEditFile() : $structure->getAddFile();

echo '
</div>';

// Campi personalizzati
echo '

<div class="hide" id="custom_fields_top-add">
    <input type="hidden" name="id_module" value="'.$id_module.'">
    <input type="hidden" name="id_plugin" value="'.$id_plugin.'">

    {( "name": "custom_fields", "id_module": "'.$id_module.'", "id_plugin": "'.$id_plugin.'", "position": "top", "place": "add" )}
</div>

<div class="hide" id="custom_fields_bottom-add">
    {( "name": "custom_fields", "id_module": "'.$id_module.'", "id_plugin": "'.$id_plugin.'", "position": "bottom", "place": "add" )}
</div>

<script>
$(document).ready(function(){
    let form = $("#custom_fields_top-add").parent().find("form").first();

    // Ultima sezione/campo del form
    let last = form.find(".panel").last();

    if (!last.length) {
        last = form.find(".box").last();
    }

    if (!last.length) {
        last = form.find(".row").eq(-2);
    }

    // Campi a inizio form
    aggiungiContenuto(form, "#custom_fields_top-add", {}, true);

    // Campi a fine form
    aggiungiContenuto(last, "#custom_fields_bottom-add", {});
});
</script>';

if (isAjaxRequest()) {
    echo '
<script>
$(document).ready(function(){
    $("#form_'.$id_module.'-'.$id_plugin.'").find("form").submit(function () {
        let $form = $(this);

        salvaForm(this, {
            id_module: "'.$id_module.'",
            id_plugin: "'.$id_plugin.'",
        }).then(function(response) {
            // Selezione automatica nuovo valore per il select
            let select = "#'.get('select').'";
            if ($(select).val() !== undefined) {
                $(select).selectSetNew(response.id, response.text, response.data);
                $(select).change();
            }

            $form.closest("div[id^=bs-popup").modal("hide");
        })

        return false;
    })
});
</script>';
}

echo '
<script>$(document).ready(init)</script>';
