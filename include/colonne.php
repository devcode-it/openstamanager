<?php

include_once __DIR__.'/../core.php';

// Compatibilità per controller ed editor
$structure = Modules::get($id_module);
$modulo_viste = Modules::get('Viste');

echo '
<p>'.tr('Trascina le colonne per riordinare la struttura della tabella principale').'.</p>
<div class="sortable">';

$fields = $dbo->fetchArray('SELECT * FROM zz_views WHERE id_module='.prepare($id_module).' ORDER BY `order` ASC');
foreach ($fields as $field) {
    echo '
    <div class="panel panel-default clickable col-md-4" data-id="'.$field['id'].'">
        <div class="panel-body">';

    if ($field['visible']) {
        echo '
            <span class="text-success">'.$field['name'].'</span>';
    } else {
        echo '
            <span class="text-danger">'.$field['name'].'</span>';
    }

    echo '
            <i class="fa fa-sort pull-right"></i>
        </div>
    </div>';
}

echo '
</div>
<div class="clearfix"></div>

<script>
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
                        id_module: '.$modulo_viste->id.',
                        id_record: '.$id_module.',
                        op: "update_position",
                        order: order.join(","),
                    });
                }
            });
        });
    });
</script>';
