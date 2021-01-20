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
    // Tabs
    $('.nav-tabs').tabs();

    // Entra nel tab indicato al caricamento della pagina
    var hash = location.hash ? location.hash : getUrlVars().hash;
    if (hash && hash != '#tab_0') {
        $('ul.nav-tabs a[href="' + hash + '"]').tab('show').trigger('shown.bs.tab');
    } else {
        removeHash();
    }

    $(window).bind('beforeunload', function () {
        if (location.hash == '#tab_0') {
            removeHash();
        }
    });

    // Nel caso la navigazione sia da mobile, disabilito il ritorno al punto precedente
    if (!globals.is_mobile) {
        // Salvo lo scroll per riportare qui l'utente al reload
        $(window).on('scroll', function () {
            if (sessionStorage != undefined) {
                sessionStorage.setItem('scrollTop_' + globals.id_module + '_' + globals.id_record, $(document).scrollTop());
            }
        });

        // Riporto l'utente allo scroll precedente
        if (sessionStorage['scrollTop_' + globals.id_module + '_' + globals.id_record] != undefined) {
            setTimeout(function () {
                scrollToOffset(sessionStorage['scrollTop_' + globals.id_module + '_' + globals.id_record]);
            }, 1);
        }
    }

    $('.nav-tabs a').click(function (e) {
        $(this).tab('show');

        let scroll = $('body').scrollTop() || $('html').scrollTop();
        window.location.hash = this.hash;

        $('html,body').scrollTop(scroll);
    });

    // Fix per la visualizzazione di Datatables all'interno dei tab Bootstrap
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        $($.fn.dataTable.tables(true)).DataTable().columns.adjust();
        $($.fn.dataTable.tables(true)).DataTable().scroller.measure();
    });
});
