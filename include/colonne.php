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

include_once __DIR__.'/../core.php';

// Compatibilità per controller ed editor
$structure = Modules::get($id_module);

echo '
<p>'.tr('Trascina le colonne per ordinare la struttura della tabella principale, seleziona e deseleziona le colonne per renderle visibili o meno').'.</p>
<div class="sortable">';

$fields = $dbo->fetchArray('SELECT * FROM zz_views WHERE id_module='.prepare($id_module).' ORDER BY `order` ASC');
foreach ($fields as $field) {
    echo '
    <div class="panel panel-default clickable col-md-4" data-id="'.$field['id'].'">
        <div class="panel-body">
            <input type="checkbox" name="visibile" '.($field['visible'] ? 'checked' : '').'>

            <span class="text-'.($field['visible'] ? 'success' : 'danger').'">'.$field['name'].'</span>

            <i class="fa fa-sort pull-right"></i>
        </div>
    </div>';
}

echo '
</div>
<div class="clearfix"></div>

<script>
    // Abilitazione dinamica delle colonne
    $("input[name=visibile]").change(function() {
        let panel = $(this).closest(".panel[data-id]");
        let id = panel.data("id");

        // Aggiornamento effettivo
        $.post(globals.rootdir + "/actions.php", {
            id_module: "'.$id_module.'",
            op: "toggle_colonna",
            id_vista: id,
            visible: $(this).is(":checked") ? 1 : 0,
        });

        // Aggiornamento grafico
        let text = panel.find("span");
        if ($(this).is(":checked")) {
            text.removeClass("text-danger")
                .addClass("text-success");
        } else {
            text.removeClass("text-success")
                .addClass("text-danger");
        }
    });

    // Ricaricamento della pagina alla chiusura
    $("#modals > div button.close").on("click", function() {
        location.reload();
    });

    // Ordinamento dinamico delle colonne
    $(document).ready(function() {
        $(".sortable").disableSelection();
        $(".sortable").each(function() {
            $(this).sortable({
                cursor: "move",
                dropOnEmpty: true,
                scroll: true,
                update: function(event, ui) {
                    let order = $(".panel[data-id]").toArray().map(a => $(a).data("id"))

                    $.post(globals.rootdir + "/actions.php", {
                        id: ui.item.data("id"),
                        id_module: "'.$id_module.'",
                        op: "ordina_colonne",
                        order: order.join(","),
                    });
                }
            });
        });
    });
</script>';
