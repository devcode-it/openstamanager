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
