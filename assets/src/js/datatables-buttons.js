$(document).ready(function () {
    // Pulsanti di Datatables
    $(".btn-csv").off("click").on("click", function (e) {
        var table = $(document).find("#" + $(this).closest("[data-target]").data("target")).DataTable();

        table.buttons(0).trigger();
    });

    $(".btn-excel").off("click").on("click", function (e) {
        var table = $(document).find("#" + $(this).closest("[data-target]").data("target")).DataTable();

        table.buttons(3).trigger();
    });

    $(".btn-pdf").off("click").on("click", function (e) {
        var table = $(document).find("#" + $(this).closest("[data-target]").data("target")).DataTable();

        table.buttons(4).trigger();
    });

    $(".btn-copy").off("click").on("click", function (e) {
        var table = $(document).find("#" + $(this).closest("[data-target]").data("target")).DataTable();

        table.buttons(1).trigger();
    });

    $(".btn-print").off("click").on("click", function (e) {
        var table = $(document).find("#" + $(this).closest("[data-target]").data("target")).DataTable();

        table.buttons(2).trigger();
    });

    $(".btn-select-all").click(function () {
        var table_selector = "#" + $(this).closest("[data-target]").data("target");
        var wrapper = getTable(table_selector);
        var table = wrapper.datatable;

        // Visualizzazione del caricamento
        $("#main_loading").show();

        // Parametri della richiesta
        var params = table.ajax.params();
        params.length = -1;

        $.ajax({
            url: table.ajax.url(),
            data: params,
            type: 'GET',
            dataType: "json",
            success: function (response) {
                var row_ids = response.data.map(function(a) {return a.id;});

                // Chiamata di selezione completa
                wrapper.addSelectedRows(row_ids);
                table.clear().draw();

                $("#main_loading").hide();
            }
        })
    });

    $(".btn-select-none").click(function () {
        var table_selector = "#" + $(this).closest("[data-target]").data("target");
        var wrapper = getTable(table_selector);
        var table = wrapper.datatable;

        // Chiamata di deselezione completa
        var row_ids = wrapper.getSelectedRows();
        wrapper.removeSelectedRows(row_ids);
        table.clear().draw();
    });

    $(document).on("click", ".select-checkbox", function () {
        var row = $(this).parent();
        var row_id = row.attr("id");

        var table_selector = $(this).closest(".dataTable");
        var wrapper = getTable(table_selector);

        if (row.hasClass("selected")) {
            //table.datatable.rows("#" + row_id).select();
            wrapper.addSelectedRows(row_id);
        } else {
            //table.datatable.rows("#" + row_id).deselect();
            wrapper.removeSelectedRows(row_id);
        }
    });

    $(".bulk-action").click(function () {
        var table = $(document).find("#" + $(this).parent().parent().parent().parent().data("target"));

        if (table.data('selected')) {
            $(this).attr("data-id_records", table.data('selected'));
            $(this).data("id_records", table.data('selected'));

            if ($(this).data("type") === "modal") {
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
