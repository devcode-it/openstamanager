<?php

include_once __DIR__.'/../core.php';

// Compatibilità per controller ed editor
$structure = Modules::get($id_module);
$modulo_viste = Modules::get('Viste');

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
                        id_module: "'.$modulo_viste->id.'",
                        id_record: "'.$id_module.'",
                        op: "update_position",
                        order: order.join(","),
                    });
                }
            });
        });
    });
</script>';
