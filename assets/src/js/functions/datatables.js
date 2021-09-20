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
        const $this = $(this);

        // Controlla che la tabella non sia già inizializzata
        if (!$.fn.DataTable.isDataTable('#' + $this.attr('id'))) {
            const id_module = $this.data('idmodule');
            const id_plugin = $this.data('idplugin');
            const id_parent = $this.data('idparent');

            // Parametri di ricerca da url o sessione
            const search = getTableSearch();

            const column_search = [];
            $this.find("th").each(function () {
                const id = $(this).attr('id').replace("th_", "");
                const single_value = search["search_" + id] ? search["search_" + id] : "";

                column_search.push({
                    "sSearch": single_value,
                });
            });

            $this.on('preInit.dt', function (ev, settings) {
                $('#mini-loader').show();
            });

            const table = $this.DataTable({
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
                buttons: getDatatablesButtons($this),
                scroller: {
                    loadingIndicator: true,
                    displayBuffer: globals.dataload_page_buffer,
                },
                ajax: {
                    url: "ajax_dataload.php?id_module=" + id_module + "&id_plugin=" + id_plugin + "&id_parent=" + id_parent,
                    type: 'GET',
                    dataSrc: "data",
                },
                initComplete: initComplete,
                drawCallback: drawCallback,
                footerCallback: footerCallback,
            });

            table.on('processing.dt', function (e, settings, processing) {
                if (processing) {
                    $('#mini-loader').show();
                } else {
                    $('#mini-loader').hide();
                }
            });
        }
    });
}

/**
 * Funzione per evitare il sorting al click della colonna.
 * Utilizzata per evitare il sorting nelle ricerche.
 * @param {*} e
 */
function stopTableSorting(e) {
    if (!e) var e = window.event;
    e.cancelBubble = true;
    if (e.stopPropagation) e.stopPropagation();
}

/**
 * Funzione per resettare il campo di ricerca in una specifica colonna.
 * @param {string} type
 */
function resetTableSearch(type) {
    if (type == null) $('[id^=th_] input').val('').trigger('keyup');
    else $('[id^=th_' + type + '] input').val('').trigger('keyup');
}

/**
 * Sostituisce i caratteri speciali per la ricerca attraverso le tabelle Datatables.
 *
 * @param {string} field
 * @return string
 */
function searchFieldName(field) {
    return field.replace(' ', '-').replace('.', '');
}

/**
 * Salva nella sessione la ricerca per le tabelle Datatables.
 *
 * @param {int} module_id
 * @param {string} field
 * @param {string} value
 */
function setTableSearch(module_id, field, value) {
    session_set('module_' + module_id + ',' + 'search_' + searchFieldName(field), value, 0);
}

/**
 * Restituisce i valori di ricerca impostati nell'URL della pagina.
 * @returns {{}}
 */
function getTableSearch() {
    // Parametri di ricerca da url o sessione
    const search = getUrlVars();

    globals.search.forEach(function (value, index, array) {
        if (search[array[index]] === undefined) {
            search[array[index]] = array[value];
        }
    });

    return search;
}

/**
 * Restituisce i pulsanti da generare per la tabella Datatables.
 * @returns
 */
function getDatatablesButtons(table) {
    return [
        // Pulsante di esportazione CSV
        {
            extend: 'csv',
            footer: true,
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
        // Pulsante di esportazione tramite copia
        {
            extend: 'copy',
            footer: true,
            exportOptions: {
                modifier: {
                    selected: true
                }
            }
        },
        // Pulsante di esportazione via stampa della tabella
        {
            extend: 'print',
            autoPrint: true,
            footer: false, // Non funzionante in Firefox, e saltuarmente in Chrome
            customize: function (win, config, datatable) {
                const footer = datatable.table().footer().children[0];

                const body = $(win.document.body);
                body.find('table')
                    .addClass('compact')
                    .css('font-size', 'inherit')
                    .append(footer.cloneNode(true));

                body.find('td:first-child, th:first-child')
                    .addClass('hide');

            },
            exportOptions: {
                modifier: {
                    selected: true
                }
            }
        },
        // Pulsante di esportazione in formato Excel
        {
            extend: 'excel',
            footer: true,
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
        // Pulsante di esportazione in formato PDF
        {
            extend: 'pdf',
            footer: true,
            exportOptions: {
                modifier: {
                    selected: true
                }
            }
        },
    ];
}

function initComplete(settings) {
    const api = this.api();
    const $this = $(this);
    const search = getTableSearch();

    api.columns('.search').every(function () {
        const column = this;

        // Valore predefinito della ricerca
        let tempo;
        const header = $(column.header());
        const name = header.attr('id').replace('th_', '');

        const value = search['search_' + name] ? search['search_' + name] : '';

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
                    setTableSearch(module_id, field, search_value);
                    column.search(search_value).draw();
                }

                // Impostazione delle sessioni per le ricerche del modulo e del campo specificati
                const module_id = $this.data('idmodule'); //+ "-" + $this.data('idplugin');
                const field = $(this).parent().attr('id').replace('th_', '');
                const value = $(this).val();
                if (e.keyCode == 13 || $(this).val() == '') {
                    start_search(module_id, field, value);
                } else {
                    const tempo_attesa_ricerche = (globals.tempo_attesa_ricerche * 1000);

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
}

function drawCallback(settings) {
    const table = getTable(settings.nTable);
    const datatable = table.datatable;

    $(".dataTables_sizing .deleteicon").addClass('hide');

    $("[data-background]").each(function () {
        $(this).parent().css("background", $(this).data("background"));
    });

    $("[data-color]").each(function () {
        $(this).parent().css("color", $(this).data("color"));
    });

    $("[data-link]").each(function () {
        const $link = $(this);
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
    const selected = table.getSelectedRows();
    datatable.rows().every(function (rowIdx) {
        if (selected.includes(this.id())) {
            datatable.row(':eq(' + rowIdx + ')', {
                page: 'current'
            }).select();
        }
    });
}

function footerCallback(row, data, start, end, display) {
    let i = -1;
    const json = this.api().ajax.json();

    this.api().columns().every(function () {
        if (json.summable[i] !== undefined) {
            $(this.footer()).addClass("text-right")
                .attr("id", "summable")
                .html(json.summable[i]);
        }

        i++;
    });
}

/**
 * Restituisce un oggetto che permette di gestire le tabelle DataTables.
 *
 * @param selector
 */
function getTable(selector) {
    const table = $(selector);

    const selected = new Map();
    const selected_ids = table.data('selected') ? table.data('selected').split(';') : [];
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
        getSelectControllerContainer: function () {
            return $('.row[data-target="' + table.attr('id') + '"]').find('.select-controller-container');
        },
        getExportContainer: function () {
            return $('.row[data-target="' + table.attr('id') + '"]').find('.export-container');
        },
        getActionsContainer: function () {
            return $('.row[data-target="' + table.attr('id') + '"]').find('.actions-container');
        },

        // Gestione delle righe selezionate
        selected: selected,
        getSelectedRows: function () {
            return Array.from(selected.keys());
        },
        saveSelectedRows: function () {
            const selected_rows = this.getSelectedRows();
            table.data('selected', selected_rows.join(';'));

            // Abilitazione dinamica di azioni di gruppo e esportazione
            const bulk_container = this.getActionsContainer();
            const export_buttons = this.getExportContainer().find('.table-btn');
            if (selected_rows.length > 0) {
                bulk_container.removeClass('disabled').attr('disabled', false);
                export_buttons.removeClass('disabled').attr('disabled', false);
            } else {
                bulk_container.addClass('disabled').attr('disabled', true);
                export_buttons.addClass('disabled').attr('disabled', true);
            }

            // Aggiornamento contatore delle selezioni
            this.getSelectControllerContainer()
                .find('.selected-count').html(selected_rows.length);

            // Aggiornamento del footer nel caso sia richiesto
            if (globals.restrict_summables_to_selected) {
                this.updateFooterForSelectedRows();
            }
        },
        addSelectedRows: function (row_ids) {
            row_ids = Array.isArray(row_ids) ? row_ids : [row_ids];
            row_ids.forEach(function (item, index) {
                selected.set(item.toString(), true);
            });

            this.saveSelectedRows();
        },
        removeSelectedRows: function (row_ids) {
            row_ids = Array.isArray(row_ids) ? row_ids : [row_ids];
            row_ids.forEach(function (item, index) {
                selected.delete(item.toString());
            });

            this.saveSelectedRows();
        },
        clearSelectedRows: function () {
            selected.clear();
            this.saveSelectedRows();
        },

        /**
         * Nuovi valori dei campi summable
         * @returns
         */
        getSelectedRowsFooter: function () {
            let ids = this.getSelectedRows();

            return $.ajax({
                url: globals.rootdir + "/ajax.php",
                type: "POST",
                dataType: "JSON",
                data: {
                    id_module: this.id_module,
                    id_plugin: this.id_plugin,
                    op: "summable-results",
                    ids: ids,
                }
            });
        },

        /**
         * Aggiornamento dei campi summable
         */
        updateFooterForSelectedRows: function () {
            let datatable = this.datatable;

            this.getSelectedRowsFooter().then(function (response) {
                for (let [column, value] of Object.entries(response)) {
                    let index = parseInt(column) + 1;
                    let sel = datatable.column(index).footer();
                    $(sel).addClass("text-right")
                        .attr("id", "summable")
                        .html(value);
                }
            });
        },
    };
}
