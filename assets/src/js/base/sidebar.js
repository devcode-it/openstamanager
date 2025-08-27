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
    // Menu ordinabile
    if (!globals.is_mobile) {
        const menu = sortable(".nav-sidebar", {
            axis: "y",
            cursor: "move",
            dropOnEmpty: true,
            scroll: true,
        })[0];

        if (menu) {
            menu.addEventListener("sortupdate", function (e) {
                let order = $(".nav-sidebar > .nav-item[data-id]").toArray().map(a => $(a).data("id"))

                $.post(globals.rootdir + "/actions.php", {
                    id_module: globals.order_manager_id,
                    op: "sort_modules",
                    order: order.join(","),
                });
            });
        }
    }

    // Gestione click specifico sul testo del menu per navigare al modulo
    $(document).on('click', '.nav-sidebar .menu-text', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const $link = $(this).closest('.nav-link[data-has-submenu="true"]');
        const href = $link.attr('href');

        if (href && href !== 'javascript:;' && href !== '#') {
            window.location.href = href;
        }
    });

    // Gestione click sull'icona freccia per compattare quando il menu è espanso
    $(document).on('click', '.nav-sidebar .nav-item.menu-open > .nav-link[data-widget="treeview"] .fa-angle-left', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const $navItem = $(this).closest('.nav-item');

        // Compatta il menu solo se è attualmente espanso
        if ($navItem.hasClass('menu-open')) {
            $navItem.removeClass('menu-open');
            $(this).closest('.nav-link').attr('aria-expanded', 'false');
            $navItem.find('.nav-treeview').slideUp(300);
        }
    });



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
    const pluginToggle = $(".control-sidebar-button");
    const largeScreen = screen.width > 1280;

    // Gestione click sul pulsante per il toggle
    pluginToggle.on("click", function () {
        // Add a subtle animation to the button
        $(this).css({
            "transform": "scale(0.95)",
            "opacity": "0.9"
        });

        setTimeout(function() {
            pluginToggle.css({
                "transform": "scale(1)",
                "opacity": "1"
            });
        }, 150);

        $("aside.content-sidebar, section.content, .main-footer, .control-sidebar-button").toggleClass("with-control-sidebar");
        $(".control-sidebar-button i").toggleClass("fa-chevron-right").toggleClass("fa-chevron-left");

        toggleControlSidebar();

        // Trigger resize event after animation completes
        setTimeout(function() {
            $(window).resize();
        }, 300);
    });

    // Barra plugin laterale disabilitata per schermi piccoli
    if (largeScreen && !globals.collapse_plugin_sidebar) {
        pluginToggle.click();
    }

    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        window.dispatchEvent(new Event('resize'));

        // Reinizializza readmore per il contenuto del tab
        if (typeof initTextShortener === 'function') {
            setTimeout(function() {
                initTextShortener();
            }, 100);
        }
    });
});

/**
 * Funzione dedicata alla gestione del toggle della sidebar.
 */
function toggleControlSidebar() {
    const sidebar = $(".control-sidebar");
    const button = $(".control-sidebar-button");

    // Add smooth animation
    sidebar.toggleClass("control-sidebar-open");

    // Add visual feedback to the button
    if (sidebar.hasClass("control-sidebar-open")) {
        button.css("background-color", "#f8f9fa");
    } else {
        button.css("background-color", "#fff");
    }
}