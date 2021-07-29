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
    // Fix per il menu principale
    $('.sidebar-menu').tree({
        followLink: true,
    });

    $('.sidebar-menu > li.treeview i.fa-angle-left').click(function (e) {
        e.preventDefault();
        $(this).find('ul').stop().slideDown();
    });

    $('.sidebar-menu > li.treeview i.fa-angle-down').click(function (e) {
        e.preventDefault();
        $(this).find('ul').stop().slideUp();
    });

    const elenco_menu = $('.treeview-menu > li.active');
    for (i = 0; i < elenco_menu.length; i++) {
        const elemento = $(elenco_menu[i]);
        elemento.parent().show().parent().addClass('active');
        elemento.parent().parent().find('i.fa-angle-left').removeClass('fa-angle-left').addClass('fa-angle-down');
    }

    // Menu ordinabile
    if (!globals.is_mobile) {
        sortable(".sidebar-menu", {
            axis: "y",
            cursor: "move",
            dropOnEmpty: true,
            scroll: true,
        })[0].addEventListener("sortupdate", function (e) {
            let order = $(".sidebar-menu > .treeview[data-id]").toArray().map(a => $(a).data("id"))

            $.post(globals.rootdir + "/actions.php", {
                id_module: globals.order_manager_id,
                op: "sort_modules",
                order: order.join(","),
            });
        });
    }

    // Mostra/nasconde sidebar del menu principale
    $(".sidebar-toggle").on("click", function () {
        if ($("body").hasClass("sidebar-collapse")) {
            session_set("settings,sidebar-collapse", 0, 1, 0);
        } else {
            session_set("settings,sidebar-collapse", 1, 0, 0);
        }

        setTimeout(function () {
            window.dispatchEvent(new Event('resize'));
        }, 350);
    });

    // Barra plugin laterale
    const pluginToggle = $(".control-sidebar-toggle");
    const largeScreen = screen.width > 1200;

    // Gestione click sul pulsante per il toggle
    pluginToggle.on("click", function () {
        $("aside.content-wrapper, .main-footer").toggleClass("with-control-sidebar");

        toggleControlSidebar();
    });

    // Gestione click sulla sidebar per evitare chiusura
    $(".control-sidebar").on("click", function (e) {
        if (largeScreen && e.target.tagName === 'A' && $(".main-footer").hasClass("with-control-sidebar")) {
            toggleControlSidebar();
        }
    });

    // Barra plugin laterale disabilitata per schermi piccoli
    if (largeScreen && !globals.collapse_plugin_sidebar) {
        pluginToggle.click();
    }
});

/**
 * Funzione dedicata alla gestione del toggle della sidebar.
 */
function toggleControlSidebar() {
    const sidebar = $(".control-sidebar");

    sidebar.toggleClass("control-sidebar-open");

    if (sidebar.hasClass("control-sidebar-open")) {
        sidebar.show();
    }
}
