
function start_local_datatables(){
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
}

// Datatable
function start_datatables() {
    start_local_datatables();

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
                            },
                            format: {
                                body: function(data, row, column, node) {
                                    data = $('<p>' + data + '</p>').text();
                                    data_edit = data.replace('.', '');
                                    data_edit = data_edit.replace(',', '.');

                                    return data_edit.match(/^[0-9\.]+$/) ? data_edit : data;
                                }
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
                    loadingIndicator: true,
                    displayBuffer: globals.dataload_page_buffer,
                },
                ajax: {
                    url: "ajax_dataload.php?id_module=" + id_module + "&id_plugin=" + id_plugin + "&id_parent=" + id_parent,
                    type: 'GET',
                    dataSrc: function (data) {
                        sum = data;
                        return data.data;
                    }
                },
                initComplete: function (settings) {
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
                        resetTableSearch($(this).parent().attr("id").replace("th_", ""));

                        if (api.page.len() == -1){
                            api.page.len($(id).data('page-length'));
                        }
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
                        }
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

function resetTableSearch(type) {
    if (type == null) $('[id^=th_] input').val('').trigger('keyup');
    else $('[id^=th_' + type + '] input').val('').trigger('keyup');
}

function reset(type) {
    return resetTableSearch(type);
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
