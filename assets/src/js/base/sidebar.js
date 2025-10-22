
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
    // Disabilita completamente il comportamento predefinito di AdminLTE per i treeview
    // Rimuove tutti gli event listener di AdminLTE sui menu treeview
    $(document).off('click.lte.treeview', '[data-widget="treeview"]');

    // Disabilita anche eventuali gestori già attaccati
    $('.nav-sidebar [data-widget="treeview"]').off('click.lte.treeview');

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

    // Gestione generale per tutti i menu con sottomenu - previene l'espansione automatica
    // Usa event capturing per intercettare prima di AdminLTE
    document.addEventListener('click', function(e) {
        const target = e.target;
        const navLink = target.closest('.nav-sidebar .nav-link[data-widget="treeview"]');

        if (!navLink) return;

        // Se il click è sulla freccia, lascia che il gestore specifico gestisca il toggle
        if (target.classList.contains('fa-angle-left') || target.closest('.fa-angle-left')) {
            return;
        }

        // Previeni sempre l'espansione automatica del menu
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        // Se il menu ha un modulo associato (data-has-submenu="true"), naviga al modulo
        if (navLink.getAttribute('data-has-submenu') === 'true') {
            const href = navLink.getAttribute('href');
            if (href && href !== 'javascript:;' && href !== '#') {
                window.location.href = href;
            }
        }
        // Per i menu contenitori (senza modulo), non fare nulla - solo la freccia può espanderli
    }, true); // true = usa event capturing

    // Gestione click sull'icona freccia per toggle del menu
    $(document).on('click', '.nav-sidebar .nav-link[data-widget="treeview"] .fa-angle-left', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const $navItem = $(this).closest('.nav-item');
        const $navLink = $(this).closest('.nav-link');

        // Toggle del menu
        if ($navItem.hasClass('menu-open')) {
            // Compatta il menu
            $navItem.removeClass('menu-open');
            $navLink.attr('aria-expanded', 'false');
            $navItem.children('.nav-treeview').slideUp(300);
        } else {
            // Espande il menu
            $navItem.addClass('menu-open');
            $navLink.attr('aria-expanded', 'true');
            $navItem.children('.nav-treeview').slideDown(300);
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

    // Gestione cambio logo sidebar in base allo stato di collapse
    // Usa MutationObserver per rilevare quando cambia la classe sidebar-collapse sul body
    const sidebarLogoObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'class') {
                const $body = $('body');
                const $logo = $('#sidebar-logo');
                const imgPath = globals.img;

                if ($body.hasClass('sidebar-collapse')) {
                    // Sidebar chiusa: mostra logo piccolo
                    $logo.attr('src', imgPath + '/logo.png');
                } else {
                    // Sidebar aperta: mostra logo completo
                    $logo.attr('src', imgPath + '/logo_completo.png');
                }
            }
        });
    });

    // Osserva i cambiamenti della classe sul body
    const bodyElement = document.querySelector('body');
    if (bodyElement) {
        sidebarLogoObserver.observe(bodyElement, {
            attributes: true,
            attributeFilter: ['class']
        });
    }

    // Barra plugin laterale
    const pluginToggle = $(".control-sidebar-button");
    const largeScreen = screen.width > 1280;

    // Gestione click sul pulsante per il toggle (solo se il pulsante esiste)
    if (pluginToggle.length > 0) {
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

        if (!isMobilePortrait()) {
            $("aside.content-sidebar, section.content, .main-footer, .control-sidebar-button")
                .toggleClass("with-control-sidebar");
        }
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
    // Mobile portrait: aggiorna comportamento quando cambia risoluzione/orientamento
    $(window).on('resize orientationchange', function() {
        const sidebar = $(".control-sidebar");
        if (!sidebar.hasClass('control-sidebar-open')) return;

        const elements = $("aside.content-sidebar, section.content, .main-footer, .control-sidebar-button");

        if (isMobilePortrait()) {
            // Mobile portrait: rimuovi shift, overlay
            elements.removeClass('with-control-sidebar');
        } else {
            // Desktop/tablet: applica shift
            elements.addClass('with-control-sidebar');
        }
    });

    // Mobile portrait: chiudi al tap/click fuori dalla barra
    $(document).on('touchstart click', function(e) {
        if (!isMobilePortrait()) return;
        const $t = $(e.target);
        if ($t.closest('.control-sidebar').length || $t.closest('.control-sidebar-button').length) return;
        $(".control-sidebar").removeClass("control-sidebar-open");
        $(".control-sidebar-button i").removeClass('fa-chevron-left').addClass('fa-chevron-right');
    });
    // Disabilita definitivamente AdminLTE treeview dopo il caricamento
    setTimeout(function() {
        // Rimuove tutti i gestori di AdminLTE sui treeview
        $(document).off('click', '[data-widget="treeview"]');
        $('.nav-sidebar [data-widget="treeview"]').off('click');

        // Disabilita anche il widget treeview di AdminLTE se presente
        if (typeof $.fn.Treeview !== 'undefined') {
            $('.nav-sidebar').off('click.lte.treeview');
        }
    }, 100);
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

function isMobilePortrait() {
    return window.innerWidth <= 768 && window.innerHeight > window.innerWidth;
}
