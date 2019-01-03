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
        return (/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|ipad|iris|kindle|Android|Silk|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i.test(navigator.userAgent) ||
            /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(navigator.userAgent.substr(0, 4)));
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

// Fix multi-modal
$(document).on('hidden.bs.modal', '.modal', function () {
    $('.modal:visible').length && $(document.body).addClass('modal-open');
});

$(document).ready(function () {
    // Imposta la lingua per la gestione automatica delle date dei diversi plugin
    moment.locale(globals.locale);
    globals.timestampFormat = moment.localeData().longDateFormat('L') + ' ' + moment.localeData().longDateFormat('LT');

    // Imposta lo standard per la conversione dei numeri
    numeral.register('locale', 'it', {
        delimiters: {
            thousands: globals.thousands,
            decimal: globals.decimals,
        },
        abbreviations: {
            thousand: 'k',
            million: 'm',
            billion: 'b',
            trillion: 't'
        },
        currency: {
            symbol: '€'
        }
    });
    numeral.locale('it');
    numeral.defaultFormat('0,0.' + ('0').repeat(globals.cifre_decimali));

    // Orologio
    clock();

    // Richiamo alla generazione di Datatables
    start_datatables();

    // Calendario principale
    ranges = {};
    ranges[globals.translations.today] = [moment(), moment()];
    ranges[globals.translations.firstThreemester] = [moment("01", "MM"), moment("03", "MM").endOf('month')];
    ranges[globals.translations.secondThreemester] = [moment("04", "MM"), moment("06", "MM").endOf('month')];
    ranges[globals.translations.thirdThreemester] = [moment("07", "MM"), moment("09", "MM").endOf('month')];
    ranges[globals.translations.fourthThreemester] = [moment("10", "MM"), moment("12", "MM").endOf('month')];
    ranges[globals.translations.firstSemester] = [moment("01", "MM"), moment("06", "MM").endOf('month')];
    ranges[globals.translations.secondSemester] = [moment("06", "MM"), moment("12", "MM").endOf('month')];
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
            $.get(globals.rootdir + '/core.php?period_start=' + start.format('YYYY-MM-DD') + '&period_end=' + end.format('YYYY-MM-DD'), function (data) {
                location.reload();
            });
        }
    );
	
	// Pulsante per visualizzare/ nascondere la password
	$(".input-group-addon").on('click', function() {
		if ($(this).parent().find("i").hasClass('fa-eye')) {
		  $("#password").attr("type", "text");
		  $(this).parent().find("i").removeClass('fa-eye').addClass('fa-eye-slash');
		  $(this).parent().find("i").attr('title', 'Nascondi password');
		} else if ($(this).parent().find("i").hasClass('fa-eye-slash')) {
		  $("#password").attr("type", "password");
		  $(this).parent().find("i").removeClass('fa-eye-slash').addClass('fa-eye');
		  $(this).parent().find("i").attr('title', 'Visualizza password');
		}
	});

    // Messaggi automatici di eliminazione
    $(document).on('click', '.ask', function () {
        message(this);
    });

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

        if (table.data('selected')) {
            $(this).attr("data-id_records", table.data('selected'));
            $(this).data("id_records", table.data('selected'));

            message(this);

            $(this).attr("data-id_records", "");
            $(this).data("id_records", "");
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

            $.post(globals.rootdir + "/actions.php?id_module=" + globals.aggiornamenti_id, {
                op: 'sortmodules',
                ids: order
            });
        }
    });

    if (isMobile.any()) {
        $(".sidebar-menu").sortable("disable");
    }

    // Tabs
    $('.nav-tabs').tabs();

    // Entra nel tab indicato al caricamento della pagina
    var hash = window.location.hash ? window.location.hash : getUrlVars().hash;
    if (hash && hash != '#tab_0') {
        $('ul.nav-tabs a[href="' + hash + '"]').tab('show');
    }

    // Nel caso la navigazione sia da mobile, disabilito il ritorno al punto precedente
    if (!isMobile.any()) {
        // Salvo lo scroll per riportare qui l'utente al reload
        $(window).on('scroll', function () {
            if (sessionStorage != undefined) {
                sessionStorage.setItem('scrollTop_' + globals.id_module + '_' + globals.id_record, $(document).scrollTop());
            }
        });

        // Riporto l'utente allo scroll precedente
        if (sessionStorage['scrollTop_' + globals.id_module + '_' + globals.id_record] != undefined) {
            setTimeout(function () {
                scrollToAndFocus(sessionStorage['scrollTop_' + globals.id_module + '_' + globals.id_record]);
            }, 1);
        }
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

    // Messaggio di avviso salvataggio a comparsa sulla destra solo nella versione a desktop intero
    if ($(window).width() > 1023) {
        var i = 0;

        $('.alert-success.push').each(function () {
            i++;
            tops = 60 * i + 95;

            $(this).css({
                'position': 'fixed',
                'z-index': 3,
                'right': '10px',
                'top': -100,
            }).delay(1000).animate({
                'top': tops,
            }).delay(3000).animate({
                'top': -100,
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
        start_widgets($("#widget-top, #widget-right"));
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
                url: globals.rootdir + '/ajax_search.php',
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
    cls = [];

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
                $.post(globals.rootdir + "/actions.php?id_module=" + globals.aggiornamenti_id, {
                    op: 'updatewidget',
                    location: dst_list,
                    id_module: globals.id_module,
                    id_record: globals.id_record,
                    class: new_class,
                    id: ui.item.attr('id')
                });

                $.post(globals.rootdir + "/actions.php?id_module=" + globals.aggiornamenti_id, {
                    op: 'sortwidget',
                    location: dst_list,
                    ids: order,
                    id_module: globals.id_module,
                    id_record: globals.id_record,
                    class: new_class
                });
            }
        });
    }
}

// Modal
function launch_modal(title, href, init_modal, id) {
    // Fix - Select2 does not function properly when I use it inside a Bootstrap modal.
    $.fn.modal.Constructor.prototype.enforceFocus = function () {};

    if (id == null) {
        id = '#bs-popup';

        // Generazione dinamica modal
        /*
        id = 'bs-popup-' + Math.floor(Math.random() * 100);
        $('#modals').append('<div class="modal fade" id="' + id + '" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="true"></div>');

        id = '#' + id;
        */
    }

    if (init_modal == null) {
        init_modal = 1;
    }

    $(id).on('hidden.bs.modal', function () {
        if ($('.modal-backdrop').length < 1) {
            $(this).html('');
            $(this).data('modal', null);
        }
    });

    // Lettura contenuto div
    if (href.substr(0, 1) == '#') {
        data = $(href).html();

        $(id).html(
            '<div class="modal-dialog modal-lg">' +
            '	<div class="modal-content">' +
            '		<div class="modal-header bg-light-blue">' +
            '			<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">' + globals.translations.close + '</span></button>' +
            '			<h4 class="modal-title"><i class="fa fa-pencil"></i> ' + title + '</h4>' +
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
                    '		<div class="modal-header bg-light-blue">' +
                    '			<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">' + globals.translations.close + '</span></button>' +
                    '			<h4 class="modal-title"><i class="fa fa-pencil"></i> ' + title + '</h4>' +
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
                order: [],
                lengthChange: false,
                scrollY: "70vh",
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
            });

            // Fix per l'URL encoding
            search.forEach(function (value, index, array) {
                search[array[index]] = decodeURIComponent(array[value]);
            });

            var res = [];
            $this.find("th").each(function () {
                var id = $(this).attr('id').replace("th_", "");

                sear = search["search_" + id] ? search["search_" + id] : "";

                res.push({
                    "sSearch": sear
                });
            });

            var sum;
            var tempo;
            var tempo_attesa_ricerche = (globals.tempo_attesa_ricerche * 1000);

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
                stateSave: true,
                stateSaveCallback: function (settings, data) {
                    sessionStorage.setItem('DataTables_' + id_module + '-' + id_plugin + '-' + id_parent, JSON.stringify(data));
                },
                stateLoadCallback: function (settings) {
                    return JSON.parse(sessionStorage.getItem('DataTables_' + id_module + '-' + id_plugin + '-' + id_parent));
                },
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
                        autoPrint: true,
                        customize: function (win) {
                            $(win.document.body)
                                .css('font-size', '10pt')
                                .append(
                                    '<table class="main-records table table-condensed table-bordered dataTable"><tfoot><tr><td></td><td class="pull-right">' + $('#summable').text() + '</td><td></td></tr></tfoot></table>'
                                );
                            $(win.document.body).find('table')
                                .addClass('compact')
                                .css('font-size', 'inherit');
                            $(win.document.body).find('td:first-child')
                                .addClass('hide');
                            $(win.document.body).find('th:first-child')
                                .addClass('hide');
                        },
                        exportOptions: {
                            modifier: {
                                selected: true
                            }
                        }
                    },
                    {
                        extend: 'excel',
                        exportOptions: {
                            modifier: {
                                selected: true
                            }
                        }
                    },
                    {
                        extend: 'pdf',
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

                                clearInterval(tempo);

                                // Fix del pulsante di pulizia ricerca e del messaggio sulla ricerca lenta
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

                                function start_search(module_id, field, search_value) {
                                    searchTable(module_id, field, search_value);
                                    column.search(search_value).draw();
                                }

                                // Impostazione delle sessioni per le ricerche del modulo e del campo specificati
                                var module_id = $this.data('idmodule'); //+ "-" + $this.data('idplugin');
                                var field = $(this).parent().attr('id').replace('th_', '');
                                var value = $(this).val();
                                if (e.keyCode == 13 || $(this).val() == '') {
                                    start_search(module_id, field, value);
                                } else {
                                    tempo = window.setTimeout(start_search, tempo_attesa_ricerche, module_id, field, value);
                                }
                            });
                    });

                    // Disabilito l'ordinamento alla pressione del tasto invio sull'<input>
                    $("thead input, .search").on('keypress', function (e) {
                        stopTableSorting(e);
                    });

                    // Disabilito l'ordinamento al click sull'<input>
                    $("thead input, .deleteicon").click(function (e) {
                        stopTableSorting(e);
                    });

                    $('.deleteicon').on("click", function (e) {
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
                    if ($(data[0]).data('id') && $.inArray($(data[0]).data('id'), $this.data('selected').split(';')) !== -1) {
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
                        container.find('.table-btn').removeClass('disabled').attr('disabled', false);
                    } else {
                        container.find('.table-btn').addClass('disabled').attr('disabled', true);
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
                    var i = -1;
                    this.api().columns().every(function () {
                        if (sum.summable[i] != undefined) {
                            $(this.footer()).addClass("text-right");
                            $(this.footer()).attr("id", "summable");
                            $(this.footer()).html(sum.summable[i]);
                        } else $(this.footer()).html("&nbsp;");
                        i++;
                    });
                }
            });

            table.on('select deselect', function (e, dt, type, indexes) {
                if (type === 'row') {
                    var selected = $this.data('selected').split(';');

                    selected = selected.filter(function (value, index, self) {
                        return value != '' && self.indexOf(value) === index;
                    });

                    var data = table.rows(indexes).data();

                    data.each(function (item) {
                        var id = $(item[0]).data('id');

                        if (id) {
                            if (e.type == 'select') {
                                selected.push(id);
                            } else {
                                var index = selected.indexOf("" + id);
                                if (index > -1) {
                                    delete selected[index];
                                }
                            }
                        }
                    });

                    selected = selected.filter(function (value, index, self) {
                        return value != '' && self.indexOf(value) === index;
                    });

                    $this.data('selected', selected.join(';'));

                    var container = $(document).find('[data-target=' + $this.attr('id') + ']');

                    if (selected.length > 0) {
                        container.find('.bulk-container').removeClass('disabled');
                        container.find('.bulk-container').attr('disabled', false);
                    } else {
                        container.find('.bulk-container').addClass('disabled');
                        container.find('.bulk-container').attr('disabled', true);
                    }

                    if (table.rows({
                            selected: true
                        }).count() > 0) {
                        container.find('.table-btn').removeClass('disabled').attr('disabled', false);
                    } else {
                        container.find('.table-btn').addClass('disabled').attr('disabled', true);
                    }
                }
            });

            table.on('processing.dt', function (e, settings, processing) {
                if (processing) {
                    $('#mini-loader').show();
                } else {
                    $('#mini-loader').hide();

                    //Reimposto il flag sulle righe ricaricate selezionate in precedenza
                    var selected = $this.data('selected').split(';');

                    table.rows().every(function (rowIdx, tableLoop, rowLoop) {
                        var object_span = $.parseHTML(this.data()[0])[0];
                        var id = $(object_span).data('id');

                        for (i = 0; i < selected.length; i++) {
                            var value = selected[i];
                            if (value == id) {
                                table.row(':eq(' + rowIdx + ')', {
                                    page: 'current'
                                }).select();
                            }
                        }
                    });
                }
            })
        }
    });
}

function stopTableSorting(e) {
    if (!e) var e = window.event;
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
 * Reimposta i contenuti di un <select> creato con select2.
 */
jQuery.fn.selectClear = function () {
    this.val([]).trigger("change");

    return this;
};

/**
 * Resetta i contenuti di un <select> creato con select2.
 */
jQuery.fn.selectReset = function (placeholder) {
    this.selectClear();
    this.empty();

    if (placeholder != undefined) {
        this.next().find('.select2-selection__placeholder').text(placeholder);
        this.next().find('input.select2-search__field').attr('placeholder', placeholder);
    }

    return this;
};

/**
 * Aggiorna un <select> creato con select2 impostando un valore di default.
 * Da utilizzare per l'impostazione dei select basati su richieste AJAX.
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
 * Aggiorna un <select> creato con select2 impostando un valore di default.
 * Da utilizzare per l'impostazione dei select statici.
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
function start_inputmask(element) {
    if (element == undefined) {
        element = '';
    } else {
        element = element + ' ';
    }

    var date = moment.localeData().longDateFormat('L').toLowerCase();

    $(element + ".date-mask").inputmask(date, {
        "placeholder": date
    });

    $(element + '.email-mask').inputmask('Regex', {
        regex: "^[a-zA-Z0-9_!#$%&'*+/=?`{|}~^-]+(?:\\.[a-zA-Z0-9_!#$%&'*+/=?`{|}~^-]+)*@[a-zA-Z0-9-]+(?:\\.[a-zA-Z0-9-]+)*$",
    });

    $(element + '.alphanumeric-mask').inputmask('Regex', {
        regex: "[A-Za-z0-9#_|\/\\-.]*",
    });

    if (isMobile.any()) {
        $(element + '.inputmask-decimal, ' + element + '.date-mask, ' + element + '.timestamp-mask').each(function () {
            $(this).attr('type', 'tel');
        });
    } else {
        $(element + '.inputmask-decimal').each(function () {
            var $this = $(this);

            var min = $this.attr('min-value');
            if (min == 'undefined') {
                min = false;
            }

            var max = $this.attr('max-value');
            if (max == 'undefined') {
                max = false;
            }

            $this.inputmask("decimal", {
                min: min ? min : undefined,
                allowMinus: !min || min < 0 ? true : false,
                max: max ? max : undefined,
                allowPlus: !max || max < 0 ? true : false,
                digits: $this.attr('decimals') ? $this.attr('decimals') : globals.cifre_decimali,
                digitsOptional: true, // Necessario per un problema di inputmask con i numeri negativi durante l'init
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
                if (min && $(this).val().toEnglish() < min) {
                    $(this).val(min);
                }
            });
        });
    }
}

/**
 * Funzione per far scrollare la pagina fino a un id + focus e offset
 * @param integer offset
 * @param string id
 */
function scrollToAndFocus(offset, id) {
    if (id) {
        offset += $('#' + id).offset().top;
    }

    $('html,body').animate({
        scrollTop: offset
    }, 'slow');
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
function session_set(session_array, value, clear, reload) {
    if (clear == undefined) {
        clear = 1;
    }

    if (reload == undefined) {
        reload = 0;
    }

    return $.get(globals.rootdir + "/ajax.php?op=session_set&session=" + session_array + "&value=" + value + "&clear=" + clear, function (data, status) {

        if (reload == 1)
            location.reload();

    });
}

function session_keep_alive() {
    $.get(globals.rootdir + '/core.php');
}
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

Number.prototype.formatMoney = function (c, d, t) {
    var n = this,
        c = isNaN(c = Math.abs(c)) ? 2 : c,
        d = d == undefined ? "." : d,
        t = t == undefined ? "," : t,
        s = n < 0 ? "-" : "",
        i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "",
        j = (j = i.length) > 3 ? j % 3 : 0;

    return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
};

String.prototype.toEnglish = function () {
    return numeral(this.toString()).value();
};

Number.prototype.toLocale = function () {
    return numeral(this).format();
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
    data = $.extend({}, $(element).data());

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
        html: '<div id="swal-form" data-parsley-validate>' + msg + '</div>',
        type: "warning",
        showCancelButton: true,
        confirmButtonText: button,
        confirmButtonClass: btn_class,
        onOpen: function () {
            start_superselect();
            start_inputmask();
        },
        preConfirm: function () {
            $form = $('#swal-form');
            $form.find(':input').each(function () {
                data[$(this).attr('name')] = $(this).val();
            });

            if ($form.parsley().validate()) {
                return new Promise(function (resolve) {
                    resolve();
                });
            } else {
                $('.swal2-buttonswrapper button').each(function () {
                    $(this).prop('disabled', false);
                });
            }
        }
    }).then(
        function () {
            if (data["op"] == undefined) data["op"] = "delete";

            var href = window.location.href.split("#")[0];
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

            blank = data.blank != undefined && data.blank;
            delete data.blank;

            if (data.callback) {
                $.ajax({
                    type: method,
                    crossDomain: true,
                    url: href,
                    data: data,
                    success: function (response) {
                        var callback = window[data.callback];

                        if (typeof callback === 'function') {
                            callback(response);
                        }
                    },
                    error: function (xhr, ajaxOptions, error) {
                        swal({
                            title: globals.translations.errorTitle,
                            html: globals.translations.errorMessage,
                            type: "error",
                        })
                    },
                });
            } else {
                redirect(href, data, method, blank);
            }
        },
        function (dismiss) {}
    );
}

function redirect(href, data, method, blank) {
    method = method ? method : "get";
    blank = blank ? blank : false;

    if (method == "post") {
        var text = '<form action="' + href + window.location.hash + '" method="post"' + (blank ? ' target="_blank"' : '') + '>';

        for (var name in data) {
            text += '<input type="hidden" name="' + name + '" value="' + data[name] + '"/>';
        }

        text += '</form>';

        var form = $(text);
        $('body').append(form);

        form.submit();
    } else {
        var values = [];

        for (var name in data) {
            values.push(name + '=' + data[name]);
        }

        var link = href + (href.indexOf('?') !== -1 ? '&' : '?') + values.join('&') + window.location.hash;

        if (!blank) {
            location.href = link;
        } else {
            window.open(link);
        }
    }
}

function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    var expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function getCookie(cname) {
    var name = cname + "=";
    var decodedCookie = decodeURIComponent(document.cookie);
    var ca = decodedCookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}

/**
 * Funzione per controllare se un file esiste
 */
function UrlExists(url) {
    var http = new XMLHttpRequest();
    http.open('HEAD', url, false);
    http.send();
    return http.status != 404;
}

function buttonLoading(button) {
    var $this = $(button);

    var result = [
        $this.html(),
        $this.attr("class")
    ];

    $this.html('<i class="fa fa-spinner fa-pulse fa-fw"></i> Attendere...');
    $this.addClass("btn-warning");
    $this.prop("disabled", true);

    return result;
}

function buttonRestore(button, loadingResult) {
    var $this = $(button);

    $this.html(loadingResult[0]);

    $this.attr("class", "");
    $this.addClass(loadingResult[1]);
    $this.prop("disabled", false);
}

/**
 * Sostituisce i caratteri speciali per la ricerca attraverso le tabelle Datatables.
 *
 * @param string field
 *
 * @return string
 */
function searchFieldName(field) {
    return field.replace(' ', '-').replace('.', '');
}

/**
 * Salva nella sessione la ricerca per le tabelle Datatables.
 *
 * @param int module_id
 * @param string field
 * @param mixed value
 */
function searchTable(module_id, field, value) {
    session_set('module_' + module_id + ',' + 'search_' + searchFieldName(field), value, 0);
}
