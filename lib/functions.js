var isMobile = {
    Android: function () {
        return navigator.userAgent.match(/Android/i);
    },
    BlackBerry: function () {
        return navigator.userAgent.match(/BlackBerry/i);
    },
    iOS: function () {
        return navigator.userAgent.match(/iPhone|iPad|iPod/i);
    },
    Opera: function () {
        return navigator.userAgent.match(/Opera Mini/i);
    },
    Windows: function () {
        return navigator.userAgent.match(/IEMobile/i);
    },
    any: function () {
        return (isMobile.Android() || isMobile.BlackBerry() || isMobile.iOS() || isMobile.Opera() || isMobile.Windows());
    }
};

// Aggiunta dell'ingranaggio all'unload della pagina
$(window).on("beforeunload", function () {
    $("#main_loading").show();
});

// Rimozione dell'ingranaggio al caricamento completo della pagina
$(window).on("load", function () {
    $("#main_loading").fadeOut();
});

$(document).ready(function () {
    // Imposta la lingua per la gestione automatica delle date dei diversi plugin
    moment.locale(globals.locale);
    globals.timestampFormat = moment.localeData().longDateFormat('L') + ' ' + moment.localeData().longDateFormat('LT');

    // Orologio
    clock();

    // Pulsante per il ritorno a inizio pagina
    backToTop();

    // Richiamo alla generazione di Datatables
    start_datatables();

    // Calendario principale
    ranges = {};
    ranges[globals.translations.today] = [moment(), moment()];
    ranges[globals.translations.yesterday] = [moment().subtract(1, 'days'), moment().subtract(1, 'days')];
    ranges[globals.translations.last7Days] = [moment().subtract(6, 'days'), moment()];
    ranges[globals.translations.last30Days] = [moment().subtract(29, 'days'), moment()];
    ranges[globals.translations.thisMonth] = [moment().startOf('month'), moment().endOf('month')];
    ranges[globals.translations.lastMonth] = [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')];
    ranges[globals.translations.thisYear] = [moment().startOf('year'), moment().endOf('year')];
    ranges[globals.translations.lastYear] = [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')];

    // Calendario principale
    $('#daterange').daterangepicker({
            locale: {
                customRangeLabel: globals.translations.custom,
                applyLabel: globals.translations.apply,
                cancelLabel: globals.translations.cancel,
                fromLabel: globals.translations.from,
                toLabel: globals.translations.to,
            },
            ranges: ranges,
            startDate: globals.start_date,
            endDate: globals.end_date,
            applyClass: 'btn btn-success btn-sm',
            cancelClass: 'btn btn-danger btn-sm',
            linkedCalendars: false
        },
        function (start, end) {
            // Esegue il submit del periodo selezionato e ricarica la pagina
            $.get(globals.rootdir + '/core.php?period_start=' + start.format('YYYY-MM-DD') + '&period_end=' + end.format('YYYY-MM-DD'), function () {
                location.href = location.href;
            });
        }
    );

    $(document).on('click', '.ask', function () {
        message(this);
    });

    // Pulsanti di Datatables
    $(".btn-csv").click(function (e) {
        var table = $(document).find("#" + $(this).parent().parent().parent().data("target")).DataTable();

        table.buttons(0).trigger();
    });

    $(".btn-copy").click(function (e) {
        var table = $(document).find("#" + $(this).parent().parent().parent().data("target")).DataTable();

        table.buttons(1).trigger();
    });

    $(".btn-print").click(function (e) {
        var table = $(document).find("#" + $(this).parent().parent().parent().data("target")).DataTable();

        table.buttons(2).trigger();
    });

    $(".btn-select-all").click(function () {
        var table = $(document).find("#" + $(this).parent().parent().parent().data("target")).DataTable();
        $("#main_loading").show();

        table.clear().draw();

        table.page.len(-1).draw();
    });

    $(".btn-select-none").click(function () {
        var table = $(document).find("#" + $(this).parent().parent().parent().data("target")).DataTable();

        table.rows().deselect();

        table.page.len(200);
    });

    $(".bulk-action").click(function () {
        var table = $(document).find("#" + $(this).parent().parent().parent().parent().data("target"));

        $(this).attr("data-id_records", table.data('selected'));

        if (table.data('selected')) {
            message(this);
        } else {
            swal(globals.translations.waiting, globals.translations.waiting_msg, "error");
        }
    });

    // Sidebar
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
            var order = $(this).sortable('toArray').toString();

            $.post(globals.rootdir + "/modules/aggiornamenti/actions.php?id_module=" + globals.aggiornamenti_id, {
                op: 'sortmodules',
                ids: order
            });
        }
    });

    // Tabs
    $('#tabs').tabs();

    // Entra nel tab indicato al caricamento della pagina
    var hash = window.location.hash ? window.location.hash : getUrlVars().hash;
    if (hash && hash != '#tab_0') {
        $('ul.nav a[href="' + hash + '"]').tab('show');

        $($.fn.dataTable.tables(true)).DataTable().columns.adjust();

        $($.fn.dataTable.tables(true)).DataTable().scroller.measure();
    }

    $('.nav-tabs a').click(function (e) {
        $(this).tab('show');

        var scrollmem = $('body').scrollTop() || $('html').scrollTop();

        window.location.hash = this.hash;

        $('html,body').scrollTop(scrollmem);
    });

    // Fix per la visualizzazione di Datatables all'interno dei tab Bootstrap
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        $($.fn.dataTable.tables(true)).DataTable().columns.adjust();

        $($.fn.dataTable.tables(true)).DataTable().scroller.measure();
    });


    // Aggiunta nell'URL del nome tab su cui tornare dopo il submit
    $(document).on('submit', 'form', function () {
        $(this).find('input:disabled, select:disabled').prop('disabled', false);

        var hash = window.location.hash;
        if (hash) {
            var input = $('<input/>', {
                type: 'hidden',
                name: 'hash',
                value: hash,
            });

            $(this).append(input);
        }
    });

    // Messaggio di avviso salvataggio a comparsa sulla destra solo nella versione a desktop intero
    if ($(window).width() > 1023) {
        var i = 0;

        $('.alert-success.push').each(function () {
            i++;
            tops = 60 * i + 95;

            $(this).css({
                'position': 'fixed',
                'z-index': 3,
                'top': -100,
                'right': 10,
                'opacity': 1
            }).delay(1000).animate({
                'top': tops,
                'opacity': 1
            }).delay(3000).animate({
                'top': -100,
                'opacity': 0
            });
        });
    }

    // Nascondo la notifica se passo sopra col mouse
    $('.alert-success.push').on('mouseover', function () {
        $(this).stop().animate({
            'top': -100,
            'opacity': 0
        });
    });

    $('.widget').mouseover(function (e) {
        e.preventDefault();
        start_widgets($("#widget-controller_top, #widget-controller_right"));
    });

    $('#supersearch').keyup(function () {
        $(document).ajaxStop();

        if ($(this).val() == '') {
            $(this).removeClass('wait');
        } else {
            $(this).addClass('wait');
        }
    });

    $.widget("custom.supersearch", $.ui.autocomplete, {
        _create: function () {
            this._super();
            this.widget().menu("option", "items", "> :not(.ui-autocomplete-category)");
        },
        _renderMenu: function (ul, items) {
            if (items[0].value == undefined) {
                $('#supersearch').removeClass('wait');
                ul.html('');
            } else {
                var that = this,
                    currentCategory = "";

                ul.addClass('ui-autocomplete-scrollable');
                ul.css('z-index', '999');

                $.each(items, function (index, item) {

                    if (item.category != currentCategory) {
                        ul.append("<li class='ui-autocomplete-category'>" + item.category + "</li>");
                        currentCategory = item.category;
                    }

                    that._renderItemData(ul, item);
                });
            }
        },
        _renderItem: function (ul, item) {
            return $("<li>")
                .append("<a href='" + item.link + "' title='Clicca per aprire'><b>" + item.value + "</b><br/>" + item.label + "</a>")
                .appendTo(ul);
        }
    });

    // Configurazione supersearch
    var $super = $('#supersearch').supersearch({
        minLength: 3,
        select: function (event, ui) {
            location.href = ui.item.link;
        },
        source: function (request, response) {
            $.ajax({
                url: globals.rootdir + '/ajax_autocomplete.php?op=supersearch&module=*',
                dataType: "json",
                data: {
                    term: request.term
                },

                complete: function (jqXHR) {
                    $('#supersearch').removeClass('wait');
                },

                success: function (data) {
                    if (data == null) {
                        response($.map(['a'], function (item) {
                            return false;
                        }));
                    } else {
                        response($.map(data, function (item) {
                            labels = (item.labels).toString();
                            labels = labels.replace('<br/>,', '<br/>');

                            return {
                                label: labels,
                                category: item.category,
                                link: item.link,
                                value: item.title
                            }
                        }));
                    }
                }
            });
        }
    });
});

// Widgets ordinabili
function start_widgets($widgets) {
    cls = new Array();

    for (i = 0; i < $widgets.length; i++) {
        $widget = $($widgets[i]);

        list_name = ($widget.attr('id')).replace('widget-', '');

        // Salvo le classi del primo elemento di ogni lista
        cls[list_name] = $widget.find('li:first').attr('class');

        $widget.sortable({
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
                var new_class = "";

                var order = $(this).sortable('toArray').toString();
                $.post(globals.rootdir + "/modules/aggiornamenti/actions.php?id_module=" + globals.aggiornamenti_id, {
                    op: 'updatewidget',
                    location: dst_list,
                    id_module: globals.id_module,
                    class: new_class,
                    id: ui.item.attr('id')
                });

                $.post(globals.rootdir + "/modules/aggiornamenti/actions.php?id_module=" + globals.aggiornamenti_id, {
                    op: 'sortwidget',
                    location: dst_list,
                    ids: order,
                    id_module: globals.id_module,
                    class: new_class
                });
            }
        });
    }
}

// Modal
function launch_modal(title, href, init_modal, id) {
    if (id == null) {
        id = '#bs-popup';
    }

    if (init_modal == null) {
        init_modal = 1;
    }

    $('html').addClass('modal-open');

    $(id).on('hidden.bs.modal', function () {
        $('html').removeClass('modal-open');
        $(this).html('');
        $(this).data('modal', null);
    });

    // Lettura contenuto div
    if (href.substr(0, 1) == '#') {
        data = $(href).html();

        $(id).html(
            '<div class="modal-dialog modal-lg">' +
            '	<div class="modal-content">' +
            '		<div class="modal-header">' +
            '			<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">' + globals.translations.close + '</span></button>' +
            '			<h4 class="modal-title">' + title + '</h4>' +
            '		</div>' +
            '		<div class="modal-body">' + data + '</div>'
        );
        if (init_modal == 1) {
            $(id).modal('show');
        }
    } else {
        $.get(href, function (data, response) {
            if (response == 'success') {
                $(id).html(
                    '<div class="modal-dialog modal-lg">' +
                    '	<div class="modal-content">' +
                    '		<div class="modal-header">' +
                    '			<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">' + globals.translations.close + '</span></button>' +
                    '			<h4 class="modal-title">' + title + '</h4>' +
                    '		</div>' +
                    '		<div class="modal-body">' + data + '</div>'
                );
                if (init_modal == 1) {
                    $(id).modal('show');
                }
            }
        });
    }
}

// Datatable
function start_datatables() {
    $('.datatables').each(function () {
        if (!$.fn.DataTable.isDataTable($(this))) {
            $(this).DataTable({
                language: {
                    url: globals.js + "/i18n/datatables/" + globals.locale + ".min.json"
                },
                retrieve: true,
                ordering: true,
                searching: true,
                paging: false,
                lengthChange: false,
                scrollY: "50vh",
            });
        }
    });

    $('.main-records').each(function () {
        var $this = $(this);

        $this.data('selected', '');

        // Controlla che la tabella non sia già inizializzata
        if (!$.fn.DataTable.isDataTable('#' + $this.attr('id'))) {
            var id_module = $this.data('idmodule');
            var id_plugin = $this.data('idplugin');
            var id_parent = $this.data('idparent');

            // Parametri di ricerca da url o sessione
            var search = getUrlVars();

            globals.search.forEach(function (value, index, array) {
                if (search[array[index]] == undefined) {
                    search.push(array[index]);
                    search[array[index]] = array[value];
                }
            })

            var res = [];
            $this.find("th").each(function () {
                var id = $(this).attr('id').replace("th_", "");

                sear = search["search_" + id] ? search["search_" + id] : "";

                res.push({
                    "sSearch": sear
                });
            });

            var sum;
            var table = $this.DataTable({
                language: {
                    url: globals.js + '/i18n/datatables/' + globals.locale + '.min.json'
                },
                autoWidth: true,
                dom: "ti",
                serverSide: true,
                deferRender: true,
                ordering: true,
                searching: true,
                aaSorting: [],
                aoSearchCols: res,
                scrollY: "60vh",
                scrollX: '100%',
                retrieve: true,
                columnDefs: [{
                    searchable: false,
                    orderable: false,
                    width: '1%',
                    className: 'select-checkbox',
                    targets: 0
                }],
                select: {
                    style: 'multi',
                    selector: 'td:first-child'
                },
                buttons: [{
                        extend: 'csv',
                        fieldSeparator: ";",
                        exportOptions: {
                            modifier: {
                                selected: true
                            }
                        }
                    },
                    {
                        extend: 'copy',
                        exportOptions: {
                            modifier: {
                                selected: true
                            }
                        }
                    },
                    {
                        extend: 'print',
                        exportOptions: {
                            modifier: {
                                selected: true
                            }
                        }
                    },
                ],
                scroller: {
                    loadingIndicator: true
                },
                ajax: {
                    url: "ajax_dataload.php?id_module=" + id_module + "&id_plugin=" + id_plugin + "&id_parent=" + id_parent,
                    type: 'GET',
                    dataSrc: function (data) {
                        sum = data;
                        return data.data;
                    }
                },
                initComplete: function () {
                    var api = this.api();
                    api.columns('.search').every(function () {
                        var column = this;
                        $('<br><input type="text" style="width:100%" class="form-control" placeholder="' + globals.translations.filter + '..."><i class="deleteicon fa fa-times fa-2x hide"></i>')
                            .appendTo(column.header())
                            .on('keyup', function (e) {
                                if (e.which != 9) {
                                    if (!$(this).val()) {
                                        if ($(this).parent().data("slow") != undefined) $("#slow").remove();
                                        $(this).removeClass('input-searching');
                                        $(this).next('.deleteicon').addClass('hide');
                                    } else {
                                        if ($(this).parent().data("slow") != undefined && $("#slow").length == 0) {
                                            $("#" + $this.attr('id') + "_info").parent().append('<span class="text-danger" id="slow"><i class="fa fa-refresh fa-spin"></i> ' + globals.translations.long + '</span>');
                                        }
                                        $(this).addClass('input-searching');
                                        $(this).next('.deleteicon').removeClass('hide');
                                    }
                                }

                                idx1 = 'module_' + $this.data('idmodule'); //+ "-" + $this.data('idplugin');
                                idx2 = 'search_' + $(this).parent().attr('id').replace('th_', '');

                                // Imposto delle sessioni per le ricerche del modulo e del campo specificatsi
                                session_set(idx1 + ',' + idx2, $(this).val(), 0);

                                column.search(this.value).draw();
                            });
                    });

                    // Disabilito l'ordinamento al click sull'<input>
                    $("thead input, .deleteicon").click(function (e) {
                        stopTableSorting(e);
                    });

                    $('.deleteicon').on("click", function (e) {
                        console.log("afsfasfsa");
                        reset($(this).parent().attr("id").replace("th_", ""));
                        api.page.len(200).draw();
                    });

                    // Ricerca di base ereditata dalla  sessione
                    search.forEach(function (value, index, array) {
                        var exists = setInterval(function () {
                            input = $('#th_' + array[index].replace('search_', '') + ' input');
                            if (input.length || array[index] == 'id_module' || array[index] == 'id_record') {
                                clearInterval(exists);
                                if (input.val() == '') input.val(array[value]).trigger('keyup');
                            }
                        }, 100);
                    });
                },
                rowCallback: function (row, data, index) {
                    if ($(data[0]).data('id') && $.inArray($(data[0]).data('id'), $this.data('selected').split(',')) !== -1) {
                        table.row(index).select();
                    }
                },
                drawCallback: function (settings) {
                    var api = new $.fn.dataTable.Api(settings);

                    $(".dataTables_sizing .deleteicon").addClass('hide');

                    $("[data-background]").each(function () {
                        $(this).parent().css("background", $(this).data("background"));
                    });

                    $("[data-color]").each(function () {
                        $(this).parent().css("color", $(this).data("color"));
                    });

                    $("[data-link]").each(function () {
                        var $link = $(this);
                        $(this).parent().not('.bound').addClass('bound').click(function (event) {
                            if ($link.data('type') == 'dialog') {
                                launch_modal(globals.translations.details, $link.data('link'));
                            } else {
                                openLink(event, $link.data('link'))
                            }
                        });
                        $(this).parent().addClass("clickable");
                    });

                    var container = $(document).find('[data-target=' + $this.attr('id') + ']');

                    if (api.rows({
                            selected: true
                        }).count() > 0) {
                        container.find('.btn-csv').removeClass('disabled');
                        container.find('.btn-print').removeClass('disabled');
                        container.find('.btn-copy').removeClass('disabled');
                        container.find('.btn-csv').attr('disabled', false);
                        container.find('.btn-print').attr('disabled', false);
                        container.find('.btn-copy').attr('disabled', false);
                    } else {
                        container.find('.btn-csv').addClass('disabled');
                        container.find('.btn-print').addClass('disabled');
                        container.find('.btn-copy').addClass('disabled');
                        container.find('.btn-csv').attr('disabled', true);
                        container.find('.btn-print').attr('disabled', true);
                        container.find('.btn-copy').attr('disabled', true);
                    }

                    // Seleziona tutto
                    if (api.page.len() == -1) {
                        api.rows({
                            search: "applied"
                        }).select();

                        if (this.fnSettings().fnRecordsDisplay() == api.rows({
                                selected: true
                            }).count()) {
                            $("#main_loading").fadeOut();
                        }
                    }
                },
                footerCallback: function (row, data, start, end, display) {
                    var i = 0;
                    this.api().columns().every(function () {
                        if (sum.summable[i] != undefined) {
                            $(this.footer()).addClass("text-right");
                            $(this.footer()).html(sum.summable[i]);
                        } else $(this.footer()).html("&nbsp;");
                        i++;
                    });
                }
            });

            table.on('select deselect', function (e, dt, type, indexes) {
                if (type === 'row') {
                    var selected = $this.data('selected').split(',');

                    var data = table.rows(indexes).data();

                    data.each(function (item) {
                        var id = $(item[0]).data('id');

                        if (id) {
                            if (e.type == 'select') {
                                selected.push(id);
                            } else {
                                var index = selected.indexOf("" + id);
                                if (index > -1) {
                                    selected = selected.splice(index + 1, 1);
                                }
                            }
                        }
                    });

                    $this.data('selected', selected.join(','));

                    var container = $(document).find('[data-target=' + $this.attr('id') + ']');

                    if (selected.length > 1) {
                        container.find('.bulk-container').removeClass('disabled');
                        container.find('.bulk-container').attr('disabled', false);
                    } else {
                        container.find('.bulk-container').addClass('disabled');
                        container.find('.bulk-container').attr('disabled', true);
                    }

                    if (table.rows({
                            selected: true
                        }).count() > 0) {
                        container.find('.btn-csv').removeClass('disabled');
                        container.find('.btn-print').removeClass('disabled');
                        container.find('.btn-copy').removeClass('disabled');
                        container.find('.btn-csv').attr('disabled', false);
                        container.find('.btn-print').attr('disabled', false);
                        container.find('.btn-copy').attr('disabled', false);
                    } else {
                        container.find('.btn-csv').addClass('disabled');
                        container.find('.btn-print').addClass('disabled');
                        container.find('.btn-copy').addClass('disabled');
                        container.find('.btn-csv').attr('disabled', true);
                        container.find('.btn-print').attr('disabled', true);
                        container.find('.btn-copy').attr('disabled', true);
                    }
                }
            });

            table.on('processing.dt', function (e, settings, processing) {
                if (processing) {
                    $('#mini-loader').show();
                } else {
                    $('#mini-loader').hide();
                }
            })
        }
    });
}

function stopTableSorting(e) {
    if (!e) var e = window.event
    e.cancelBubble = true;
    if (e.stopPropagation) e.stopPropagation();
}

function reset(type) {
    if (type == null) $('[id^=th_] input').val('').trigger('keyup');
    else $('[id^=th_' + type + '] input').val('').trigger('keyup');
}

function openLink(event, link) {
    if (event.ctrlKey) {
        window.open(link);
    } else {
        location.href = link;
    }
}

// Select
function start_superselect() {
    // Statico
    $('.superselect').each(function () {
        $this = $(this);
        $(this).select2({
            theme: "bootstrap",
            language: "it",
            width: '100%',
            maximumSelectionLength: $this.data('maximum') ? $this.data('maximum') : -1,
            minimumResultsForSearch: $this.hasClass('no-search') ? -1 : 0,
            allowClear: $this.hasClass('no-search') ? false : true,
            templateResult: function (data, container) {
                var bg; // templateSelection

                if (data._bgcolor_) {
                    bg = data._bgcolor_;
                } else if ($(data.element).attr("_bgcolor_")) {
                    bg = $(data.element).attr("_bgcolor_");
                } else if ($(data.element).data("_bgcolor_")) {
                    bg = $(data.element).data("_bgcolor_");
                }

                if (bg) {
                    $(container).css("background-color", bg);
                    $(container).css("color", setContrast(bg));
                }

                return data.text;
            },
            escapeMarkup: function (text) {
                return text;
            }
        });
    });

    // Dinamico (AJAX, per tabelle con molti record)
    $('.superselectajax').each(function () {
        $this = $(this);

        $(this).select2({
            theme: "bootstrap",
            language: "it",
            maximumSelectionLength: $this.data('maximum') ? $this.data('maximum') : -1,
            minimumInputLength: $this.data('heavy') ? 3 : 0,
            allowClear: true,
            escapeMarkup: function (text) {
                return text;
            },
            templateResult: function (data, container) {
                var bg; // templateSelection

                if (data._bgcolor_) {
                    bg = data._bgcolor_;
                } else if ($(data.element).attr("_bgcolor_")) {
                    bg = $(data.element).attr("_bgcolor_");
                } else if ($(data.element).data("_bgcolor_")) {
                    bg = $(data.element).data("_bgcolor_");
                }

                if (bg && !$("head").find('#' + data._resultId + '_style').length) {
                    $(container).css("background-color", bg);
                    $(container).css("color", setContrast(bg));
                }

                return data.text;
            },
            ajax: {
                url: globals.rootdir + "/ajax_select.php?op=" + $this.data('source'),
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term // search term
                    }
                },
                processResults: function (data) {
                    return {
                        results: data
                    }
                },
                cache: false
            },
            width: '100%'
        });
    });
}

/**
 * Resetta i contenuti di un <select> creato con select2.
 */
jQuery.fn.selectReset = function () {
    this.val('').trigger("change");
    this.empty();

    return this;
};

/**
 * Aggiorna un <select> creato con select2 impostando un valore di default
 */
jQuery.fn.selectSetNew = function (value, label) {
    this.selectReset();

    this.selectAdd([{
        'value': value,
        'text': label,
    }]);

    this.selectSet(value);

    return this;
};

/**
 * Aggiorna un <select> creato con select2 impostando un valore di default
 */
jQuery.fn.selectSet = function (value) {
    this.val(value).trigger("change");

    return this;
};

/**
 * Aggiorna un <select> creato con select2 impostando un valore di default
 */
jQuery.fn.selectAdd = function (values) {
    $this = this;

    values.forEach(function (item, index, array) {
        var option = $('<option/>', item);

        $this.append(option);
    });

    return this;
};

/**
 * Restituisce l'oggetto contenente gli attributi di una <option> generata da select2.
 */
jQuery.fn.selectData = function () {
    var obj = $(this[0]);

    $select_obj = obj.select2('data');

    if ($select_obj[0] == undefined) {
        return undefined;
    } else {
        if ($select_obj[0].selected == false) {
            return $select_obj[0];
        } else {
            return $select_obj[0].element.dataset;
        }
    }
};

// Inputmask
function start_inputmask() {
    $(".date-mask").inputmask(moment.localeData().longDateFormat('L').toLowerCase(), {
        "placeholder": moment.localeData().longDateFormat('L').toLowerCase()
    });

    $('.email-mask').inputmask('Regex', {
        regex: "^[a-zA-Z0-9_!#$%&'*+/=?`{|}~^-]+(?:\\.[a-zA-Z0-9_!#$%&'*+/=?`{|}~^-]+)*@[a-zA-Z0-9-]+(?:\\.[a-zA-Z0-9-]+)*$",
    });

    if (isMobile.any()) {
        $('.inputmask-decimal').each(function () {
            val = $(this).val().toEnglish();
            $(this).attr('type', 'tel').val(val);
        });
    } else {
        $('.inputmask-decimal').each(function () {
            var $this = $(this);
            $this.inputmask("decimal", {
                min: $this.attr('min-value') ? $this.attr('min-value') : undefined,
                allowMinus: $this.attr('min-value') >= 0 ? true : false,
                digits: $this.attr('decimals') ? $this.attr('decimals') : globals.cifre_decimali,
                digitsOptional: false,
                enforceDigitsOnBlur: true,
                rightAlign: true,
                autoGroup: true,
                radixPoint: globals.decimals,
                groupSeparator: globals.thousands,
                onUnMask: function (maskedValue, unmaskedValue) {
                    return maskedValue.toEnglish();
                }
            });

            $this.on('keyup', function () {
                if ($(this).attr('min-value') && $(this).val().toEnglish() < $(this).attr('min-value')) {
                    $(this).val($(this).attr('min-value'));
                }
            });
        });
    }
}

/*
/* Funzione per far scrollare la pagina fino a un id + focus e offset
/* es: scrollToAndFocus ('id',0,'','Attenzione');
*/
function scrollToAndFocus(id, offset, focus, messaggio) {
    $('html,body').animate({
        scrollTop: $('#' + id).offset().top + offset
    }, 'slow', function () {
        if (messaggio != '') {
            alert(messaggio);
            messaggio = '';
        };
        if (focus != '') {
            $('#' + focus).focus();
        }
    });
}

/**
 * Ritorna un array associativo con i parametri passati via GET
 */
function getUrlVars(url) {
    var vars = [],
        hash;
    if (url == null)
        var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    else
        var hashes = url.slice(url.indexOf('?') + 1).split('&');
    for (var i = 0; i < hashes.length; i++) {
        hash = hashes[i].split('=');
        vars.push(hash[0]);
        vars[hash[0]] = hash[1];
    }

    return vars;
}

// Data e ora (orologio)
function clock() {
    $('#datetime').html(moment().format(globals.timestampFormat));
    setTimeout('clock()', 1000);
}

/**
 * Funzione per impostare un valore ad un array in $_SESSION
 */
function session_set_array(session_array, value, inversed) {
    if (inversed == undefined) {
        inversed = 1;
    }

    return $.get(globals.rootdir + "/ajax.php?op=session_set_array&session=" + session_array + "&value=" + value + "&inversed=" + inversed);
}

/**
 * Funzione per impostare un valore ad una sessione
 */
function session_set(session_array, value, clear) {
    if (clear == undefined) {
        clear = 1;
    }

    return $.get(globals.rootdir + "/ajax.php?op=session_set&session=" + session_array + "&value=" + value + "&clear=" + clear);
}

function session_keep_alive() {
    $.get(globals.rootdir + '/core.php');
};

/**
 * Funzione per gestire i contatori testuali nel formato x/total.
 * Viene dato un id del campo da verificare come input, viene letto il testo nella forma [0-9]/[0-9] e viene fatto
 * il replate del primo numero in base a quanti elementi sono stati trovati (valore passato per parametro)
 */
function update_counter(id, new_value) {
    new_text = $('#' + id).html();

    // Estraggo parte numerica (formato x/total)
    pattern = /([^0-9]+)([0-9]+)\/([0-9]+)([^0-9]+)/;
    new_text = new_text.replace(pattern, "$1" + new_value + "/$3$4");

    // Estraggo totale (parte numerica dopo lo slash /)
    matches = pattern.exec(new_text);
    total = matches[3];

    $('#' + id).html(new_text);

    if (new_value == total) {
        $('#' + id).removeClass('btn-warning').removeClass('btn-danger').addClass('btn-success');
    } else if (new_value == 0) {
        $('#' + id).removeClass('btn-warning').removeClass('btn-success').addClass('btn-danger');
    } else {
        $('#' + id).removeClass('btn-success').removeClass('btn-danger').addClass('btn-warning');
    }
}

function backToTop() {
    // browser window scroll (in pixels) after which the "back to top" link is shown
    var offset = 10,

        // browser window scroll (in pixels) after which the "back to top" link opacity is reduced
        offset_opacity = 100,

        // duration of the top scrolling animation (in ms)
        scroll_top_duration = 700,

        // grab the "back to top" link
        back_to_top = $('#back-to-top');

    // hide or show the "back to top" link
    $('.wrapper').scroll(function () {
        if ($(this).scrollTop() > offset) {
            back_to_top.addClass('cd-is-visible');
            if ($(this).scrollTop() > offset_opacity) {
                back_to_top.addClass('cd-fade-out');
            }
        } else {
            back_to_top.removeClass('cd-is-visible cd-fade-out');
        }
    });

    // smooth scroll to top
    back_to_top.on('click', function (event) {
        event.preventDefault();
        $('.wrapper').animate({
            scrollTop: 0,
        }, scroll_top_duration);
    });
}

Number.prototype.formatMoney = function (c, d, t) {
    var n = this,
        c = isNaN(c = Math.abs(c)) ? 2 : c,
        d = d == undefined ? "." : d,
        t = t == undefined ? "," : t,
        s = n < 0 ? "-" : "",
        i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "",
        j = (j = i.length) > 3 ? j % 3 : 0;

    return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
}

function force_decimal(n) {
    n = n.replace(":", ".");
    n = n.replace(",", ".");
    return parseFloat(n);
}

function equalHeight(selector) {
    $(selector).css("min-height", 0);

    var maxHeight = 0;
    $(selector).each(function () {
        var thisH = $(this).outerHeight();
        if (thisH > maxHeight) {
            maxHeight = thisH;
        }
    });

    $(selector).css("min-height", maxHeight);

    $(window).on("resize", function () {
        equalHeight(selector);
    });
}

Number.prototype.toFixedLocale = function (decimals) {
    decimals = decimals || globals.cifre_decimali
    return this.toFixed(decimals).toLocale();
};

String.prototype.toEnglish = function () {
    var x = this.split(globals.decimals);

    if (globals.thousands) {
        x[0] = x[0].replace(globals.thousands, '');
    }

    return parseFloat(x[0] + '.' + x[1]);
};

String.prototype.toLocale = function () {
    var x = this.split('.');

    if (globals.thousands) {
        x[0] = x[0].split("").reverse().join("");
        x[0] = x[0].replace(/(.{3})/g,"$1" + globals.thousands);
        x[0] = x[0].split("").reverse().join("");
    }

    return x[0] + globals.decimals + x[1];
};

function setContrast(backgroundcolor) {
    var rgb = [];
    var bg = String(backgroundcolor);

    // ex. backgroundcolor = #ffc400
    rgb[0] = bg.substr(1, 2);
    rgb[1] = bg.substr(2, 2);
    rgb[2] = bg.substr(5, 2);

    var R1 = parseInt(rgb[0], 16);
    var G1 = parseInt(rgb[1], 16);
    var B1 = parseInt(rgb[2], 16);

    var R2 = 255;
    var G2 = 255;
    var B2 = 255;

    var L1 = 0.2126 * Math.pow(R1 / 255, 2.2) + 0.7152 * Math.pow(G1 / 255, 2.2) + 0.0722 * Math.pow(B1 / 255, 2.2);
    var L2 = 0.2126 * Math.pow(R2 / 255, 2.2) + 0.7152 * Math.pow(G2 / 255, 2.2) + 0.0722 * Math.pow(B2 / 255, 2.2);

    if (L1 > L2) {
        var lum = (L1 + 0.05) / (L2 + 0.05);
    } else {
        var lum = (L2 + 0.05) / (L1 + 0.05);
    }

    if (lum >= 9) {
        return "#ffffff";
    } else {
        return "#000000";
    }
}

function message(element) {
    data = $(element).data();

    var title = globals.translations.deleteTitle;
    if (data["title"] != undefined) title = data["title"];

    var msg = globals.translations.deleteMessage;
    if (data["msg"] != undefined) msg = data["msg"];

    var button = globals.translations.delete;
    if (data["button"] != undefined) button = data["button"];

    var btn_class = "btn btn-lg btn-danger";
    if (data["class"] != undefined) btn_class = data["class"];

    swal({
        title: title,
        text: msg,
        type: "warning",
        showCancelButton: true,
        confirmButtonText: button,
        confirmButtonClass: btn_class,
    }).then(
        function (result) {
            if (data["op"] == undefined) data["op"] = "delete";

            var href = window.location.href.split("#")[0]
            if (data["href"] != undefined) {
                href = data["href"];
                delete data.href;
            }

            var hash = window.location.href.split("#")[1];
            if (hash) {
                data["hash"] = hash;
            }

            method = "post";
            if (data["method"] != undefined) {
                if (data["method"] == "post" || data["method"] == "get") {
                    method = data["method"];
                }
                delete data.method;
            }

            redirect(href, data, method);
        },
        function (dismiss) {}
    );
}

function redirect(href, data, method = "get") {
    var text = (method == "post") ? '<form action="' + href + window.location.hash + '" method="post">' : [];

    for (var name in data) {
        if (method == "post") {
            text += '<input type="text" name="' + name + '" value="' + data[name] + '"/>';
        } else {
            text.push(name + '=' + data[name]);
        }
    }

    if (method == "post") {
        text += '</form>';
    }

    if (method == "post") {
        var form = $(text);
        $('body').append(form);

        form.submit();
    } else {
        location.href = href + (href.indexOf('?') !== -1 ? '&' : '?') + text.join('&') + window.location.hash;
    }
}
