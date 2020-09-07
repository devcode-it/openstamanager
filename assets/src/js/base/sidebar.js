/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

    $menulist = $('.treeview-menu > li.active');
    for (i = 0; i < $menulist.length; i++) {
        $list = $($menulist[i]);
        $list.parent().show().parent().addClass('active');
        $list.parent().parent().find('i.fa-angle-left').removeClass('fa-angle-left').addClass('fa-angle-down');
    }

    // Menu ordinabile
    $(".sidebar-menu").sortable({
        cursor: 'move',

        stop: function (event, ui) {
            let order = $(this).sortable('toArray').toString();

            $.post(globals.rootdir + "/actions.php?id_module=" + globals.order_manager_id, {
                op: 'sort_modules',
                ids: order
            });
        }
    });

    if (globals.is_mobile) {
        $(".sidebar-menu").sortable("disable");
    }

    $(".sidebar-toggle").click(function () {
        setTimeout(function () {
            window.dispatchEvent(new Event('resize'));
        }, 350);
    });

    // Mostra/nasconde sidebar sx
    $(".sidebar-toggle").on("click", function(){
        if ($( "body" ).hasClass( "sidebar-collapse" )){
            session_set("settings,sidebar-collapse",0,1,0);
        }else{
            session_set("settings,sidebar-collapse",1,0,0);
        }
    });

});
