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
