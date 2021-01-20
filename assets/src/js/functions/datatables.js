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

function start_local_datatables() {
    $('.datatables').each(function () {
        if (!$.fn.DataTable.isDataTable($(this))) {
            $(this).DataTable({
                language: globals.translations.datatables,
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

        // Controlla che la tabella non sia già inizializzata
        if (!$.fn.DataTable.isDataTable('#' + $this.attr('id'))) {
            var id_module = $this.data('idmodule');
            var id_plugin = $this.data('idplugin');
            var id_parent = $this.data('idparent');

            // Parametri di ricerca da url o sessione
            var search = getTableSearch();

            var column_search = [];
            $this.find("th").each(function () {
                var id = $(this).attr('id').replace("th_", "");
                var single_value = search["search_" + id] ? search["search_" + id] : "";

                column_search.push({
                    "sSearch": single_value,
                });
            });

            var tempo_attesa_ricerche = (globals.tempo_attesa_ricerche * 1000);

            $this.on('preInit.dt', function (ev, settings) {
                $('#mini-loader').show();
            });

            var table = $this.DataTable({
                language: globals.translations.datatables,
                autoWidth: true,
                dom: "ti",
                serverSide: true,
                deferRender: true,
                ordering: true,
                searching: true,
                aaSorting: [],
                aoSearchCols: column_search,
                scrollY: "60vh",
                scrollX: '100%',
                retrieve: true,
                stateSave: true,
                rowId: 'id',
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
                buttons: [
                    {
                        extend: 'csv',
                        fieldSeparator: ";",
                        exportOptions: {
                            modifier: {
                                selected: true
                            },
                            format: {
                                body: function (data, row, column, node) {
                                    data = $('<p>' + data + '</p>').text();
                                    data_edit = data.replace('.', ''); // Rimozione punto delle migliaia

                                    return data_edit.match(/^[0-9,]+$/) ? data_edit : data;
                                }
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
                                body: function (data, row, column, node) {
                                    data = $('<p>' + data + '</p>').text();
                                    data_edit = data.replace('.', ''); // Fix specifico per i numeri italiani
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
                    dataSrc: "data",
                },
                initComplete: function (settings) {
                    var api = this.api();
                    var search = getTableSearch();

                    api.columns('.search').every(function () {
                        var column = this;

                        // Valore predefinito della ricerca
                        var tempo;
                        var header = $(column.header());
                        var name = header.attr('id').replace('th_', '');

                        var value = search['search_' + name] ? search['search_' + name] : '';

                        $('<br><input type="text" style="width:100%" class="form-control' + (value ? ' input-searching' : '') + '" placeholder="' + globals.translations.filter + '..." value="' + value.replace(/"/g, '&quot;') + '"><i class="deleteicon fa fa-times fa-2x' + (value ? '' : ' hide') + '"></i>')
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
                    });
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
                            if ($link.data('type') === 'dialog') {
                                launch_modal(globals.translations.details, $link.data('link'));
                            } else {
                                openLink(event, $link.data('link'))
                            }
                        });
                        $(this).parent().addClass("clickable");
                    });

                    // Reimposto il flag sulle righe ricaricate selezionate in precedenza
                    var selected = $this.data('selected') ? $this.data('selected').split(';') : [];
                    table.rows().every(function (rowIdx) {
                        if ($.inArray(this.id().toString(), selected) !== -1) {
                            table.row(':eq(' + rowIdx + ')', {
                                page: 'current'
                            }).select();
                        }
                    });
                },
                footerCallback: function (row, data, start, end, display) {
                    var i = -1;
                    var json = this.api().ajax.json();

                    this.api().columns().every(function () {
                        if (json.summable[i] !== undefined) {
                            $(this.footer()).addClass("text-right")
                                .attr("id", "summable")
                                .html(json.summable[i]);
                        }

                        i++;
                    });
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

function getTableSearch() {
    // Parametri di ricerca da url o sessione
    var search = getUrlVars();

    globals.search.forEach(function (value, index, array) {
        if (search[array[index]] === undefined) {
            search[array[index]] = array[value];
        }
    });

    return search;
}

/**
 * Restituisce un oggetto che permette di gestire le tabelle DataTables.
 *
 * @param selector
 */
function getTable(selector) {
    var table = $(selector);

    var selected = new Map();
    var selected_ids = table.data('selected') ? table.data('selected').split(';') : [];
    selected_ids.forEach(function (item, index) {
        selected.set(item, true);
    });

    return {
        table: table,

        id_module: table.data('idmodule'),
        id_plugin: table.data('idplugin'),

        initDatatable: function () {
            if (table.hasClass('datatables')) {
                start_local_datatables();
            } else {
                start_datatables();
            }
        },
        datatable: table.DataTable(),

        // Funzioni per i contenitori relativi alla tabella
        getButtonsContainer: function () {
            return $('.row[data-target="' + table.attr('id') + '"]').find('.table-btn');
        },
        getActionsContainer: function () {
            return $('.row[data-target="' + table.attr('id') + '"]').find('.bulk-container');
        },

        // Gestione delle righe selezionate
        selected: selected,
        getSelectedRows: function () {
            return Array.from(selected.keys());
        },
        saveSelectedRows: function () {
            var selected_rows = this.getSelectedRows();
            table.data('selected', selected_rows.join(';'));

            var bulk_container = this.getActionsContainer();
            var btn_container = this.getButtonsContainer();
            if (selected_rows.length > 0) {
                bulk_container.removeClass('disabled').attr('disabled', false);
                btn_container.removeClass('disabled').attr('disabled', false);
            } else {
                bulk_container.addClass('disabled').attr('disabled', true);
                btn_container.addClass('disabled').attr('disabled', true);
            }

            // Aggiornamento del footer nel caso sia richiesto
            if (globals.restrict_summables_to_selected) {
                this.updateSelectedFooter();
            }
        },
        addSelectedRows: function (row_ids) {
            row_ids = Array.isArray(row_ids) ? row_ids : [row_ids];
            row_ids.forEach(function (item, index) {
                selected.set(item, true);
            });

            this.saveSelectedRows();
        },
        removeSelectedRows: function (row_ids) {
            row_ids = Array.isArray(row_ids) ? row_ids : [row_ids];
            row_ids.forEach(function (item, index) {
                selected.delete(item);
            });

            this.saveSelectedRows();
        },
        clearSelectedRows: function () {
            selected.clear();
            this.saveSelectedRows();
        },

        // Aggiornamento dei campi summable
        updateSelectedFooter: function () {
            let datatable = this.datatable;
            let ids = this.getSelectedRows();

            $.ajax({
                url: globals.rootdir + "/ajax.php",
                type: "POST",
                dataType: "json",
                data: {
                    id_module: this.id_module,
                    id_plugin: this.id_plugin,
                    op: "summable-results",
                    ids: ids,
                },
                success: function (response) {
                    for (let [column, value] of Object.entries(response)) {
                        let index = parseInt(column) + 1;
                        let sel = datatable.column(index).footer();
                        $(sel).addClass("text-right")
                            .attr("id", "summable")
                            .html(value);
                    }
                }
            });
        },
    };
}
