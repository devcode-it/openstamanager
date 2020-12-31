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
$modulo_viste = Modules::get('Viste');

echo '
<p>'.tr('Trascina le colonne per ordinare la struttura della tabella principale, seleziona e deseleziona le colonne per renderle visibili o meno').'.</p>
<div class="sortable">';

$fields = $dbo->fetchArray('SELECT *, (SELECT GROUP_CONCAT(zz_groups.nome) FROM zz_group_view INNER JOIN zz_groups ON zz_group_view.id_gruppo = zz_groups.id WHERE zz_group_view.id_vista = zz_views.id) AS gruppi_con_accesso FROM zz_views WHERE id_module='.prepare($id_module).' ORDER BY `order` ASC');
foreach ($fields as $field) {
    echo '
    <div class="panel panel-default clickable col-md-4" data-id="'.$field['id'].'">
        <div class="panel-body no-selection">
            <input type="checkbox" name="visibile" '.($field['visible'] ? 'checked' : '').'>

            <span class="text-'.($field['visible'] ? 'success' : 'danger').'">'.$field['name'].'<br><small>( '.$field['gruppi_con_accesso'].')</small></span>

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
            id_module: "'.$modulo_viste->id.'",
            id_record: "'.$id_module.'",
            op: "update_visible",
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
        sortable(".sortable", {
            axis: "y",
            cursor: "move",
            dropOnEmpty: true,
            scroll: true,
        })[0].addEventListener("sortupdate", function(e) {
            let order = $(".panel[data-id]").toArray().map(a => $(a).data("id"))
            console.log(order);

            $.post(globals.rootdir + "/actions.php", {
                id_module: globals.id_module,
                op: "ordina_colonne",
                order: order.join(","),
            });
        });
    });
</script>';
