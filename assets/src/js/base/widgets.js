$(document).ready(function () {
    $("#widget-top, #widget-right").sortable({
        items: 'li',
        cursor: 'move',
        dropOnEmpty: true,
        connectWith: '.widget',
        scroll: true,
        helper: 'clone',
        start: function (event, ui) {
            // Salvo la lista da cui proviene il drag
            src_list = ($(this).attr('id')).replace('widget-', '');

            // Evidenzio le aree dei widget
            $('.widget').addClass('bordered').sortable('refreshPositions');
        },
        stop: function (event, ui) {
            // Rimuovo l'evidenziazione dell'area widget
            $('.widget').removeClass('bordered');

            // Salvo la lista su cui ho eseguito il drop
            dst_list = (ui.item.parent().attr('id')).replace('widget-', '');

            var order = $(this).sortable('toArray').toString();
            $.post(globals.rootdir + "/actions.php?id_module=" + globals.order_manager_id, {
                op: 'sort_widgets',
                location: dst_list,
                ids: order,
                id_module_widget: globals.id_module,
                id_record: globals.id_record,
            });
        }
    });
});
