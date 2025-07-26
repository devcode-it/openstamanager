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
    const widgets = sortable("#widget-top, #widget-right", {
        forcePlaceholderSize: true,
        items: 'li',
        cursor: 'move',
        dropOnEmpty: true,
        acceptFrom: '.widget',
        scroll: true,
    });

    for (const sorting of widgets) {
        sorting.addEventListener("sortupdate", function (e) {
            // Rimuovo l'evidenziazione dell'area widget
            $('.widget').removeClass('bordered');

            // Salvo la lista su cui ho eseguito il drop
            const location = $(e.detail.destination.container).attr('id').replace('widget-', '');

            let order = $(".widget li[data-id]").toArray().map(a => $(a).data("id"))
            $.post(globals.rootdir + "/actions.php", {
                id_module: globals.order_manager_id,
                id_module_widget: globals.id_module,
                op: 'sort_widgets',
                location: location,
                order: order.join(','),
            });
        });

        sorting.addEventListener("sortstart", function (e) {
            // Evidenzio le aree dei widget
            $('.widget').addClass('bordered');
        });
    }
});
