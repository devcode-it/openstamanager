$(document).ready(function () {
// Pulsanti di Datatables
    $(".btn-csv").click(function (e) {
        var table = $(document).find("#" + $(this).closest("[data-target]").data("target")).DataTable();

        table.buttons(0).trigger();
    });

    $(".btn-excel").click(function (e) {
        var table = $(document).find("#" + $(this).closest("[data-target]").data("target")).DataTable();

        table.buttons(3).trigger();
    });

    $(".btn-pdf").click(function (e) {
        var table = $(document).find("#" + $(this).closest("[data-target]").data("target")).DataTable();

        table.buttons(4).trigger();
    });

    $(".btn-copy").click(function (e) {
        var table = $(document).find("#" + $(this).closest("[data-target]").data("target")).DataTable();

        table.buttons(1).trigger();
    });

    $(".btn-print").click(function (e) {
        var table = $(document).find("#" + $(this).closest("[data-target]").data("target")).DataTable();

        table.buttons(2).trigger();
    });

    $(".btn-select-all").click(function () {
        var id = $(document).find("#" + $(this).parent().parent().parent().data("target"));
        var table = id.DataTable();

        $("#main_loading").show();
        table.clear().draw();

        $(id).data('page-length', table.page.len());

        table.page.len(-1).draw();
    });

    $(".btn-select-none").click(function () {
        var id = $(document).find("#" + $(this).parent().parent().parent().data("target"));
        var table = id.DataTable();

        table.rows().deselect();

        table.page.len($(id).data('page-length'));
    });

    $(".bulk-action").click(function () {
        var table = $(document).find("#" + $(this).parent().parent().parent().parent().data("target"));

        if (table.data('selected')) {
            $(this).attr("data-id_records", table.data('selected'));
            $(this).data("id_records", table.data('selected'));

            if ($(this).data("type") == "modal") {
                var data = JSON.parse(JSON.stringify($(this).data()));
                var href = data.url;

                delete data.url;
                delete data.title;
                delete data.op;
                delete data.backto;
                delete data.blank;

                var values = [];
                for (var name in data) {
                    values.push(name + '=' + data[name]);
                }

                var link = href + (href.indexOf('?') !== -1 ? '&' : '?') + values.join('&');

                launch_modal($(this).data("title"), link);
            } else {
                message(this);
            }

            $(this).attr("data-id_records", "");
            $(this).data("id_records", "");
        } else {
            swal(globals.translations.waiting, globals.translations.waiting_msg, "error");
        }
    });
});
