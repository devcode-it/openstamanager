(function ($) {
    'use strict';

    var ROOT = '#osm-scadenzario-summary';
    var STATES = ['paid', 'agreed_overdue', 'agreed', 'overdue', 'due_soon', 'future'];

    function getRoot() {
        return $(ROOT);
    }

    function getSettings() {
        var $root = getRoot();
        var moduleId = String($root.attr('data-module-id') || '');
        var settings = ($.fn.dataTable && $.fn.dataTable.settings) || [];

        for (var i = 0; i < settings.length; i += 1) {
            var current = settings[i];
            var table = current && current.nTable;

            if (
                table
                && String($(table).attr('data-idmodule') || '') === moduleId
                && !$(table).attr('data-idplugin')
            ) {
                return current;
            }
        }

        return null;
    }

    function getStateColumnIndex(settings) {
        var expectedHeaderId = String(getRoot().attr('data-state-header-id') || '');
        var columns = (settings && settings.aoColumns) || [];

        for (var i = 0; i < columns.length; i += 1) {
            var header = columns[i] && columns[i].nTh;

            if (header && String(header.id || '') === expectedHeaderId) {
                return i;
            }
        }

        return -1;
    }

    function getContext() {
        var settings = getSettings();

        if (!settings) {
            return null;
        }

        var columnIndex = getStateColumnIndex(settings);

        if (columnIndex < 0) {
            return null;
        }

        return {
            settings: settings,
            api: new $.fn.dataTable.Api(settings),
            columnIndex: columnIndex
        };
    }

    function normalizeState(value) {
        value = String(value || '');
        return STATES.indexOf(value) !== -1 ? value : '';
    }

    function readActiveState(context) {
        return normalizeState(context.api.column(context.columnIndex).search());
    }

    function updateInterface(state) {
        var $root = getRoot();
        var $widgets = $root.find('.osm-scadenzario-widget');

        state = normalizeState(state);

        $root.attr('data-active-state', state);
        $widgets.removeClass('active').attr('aria-pressed', 'false');
        $root.find('.osm-scadenzario-reset-filter').toggleClass('hide', !state);

        if (state) {
            $widgets
                .filter('[data-state="' + state + '"]')
                .addClass('active')
                .attr('aria-pressed', 'true');
        }
    }

    function applyState(requestedState) {
        var $root = getRoot();

        if ($root.attr('data-filter-enabled') !== '1') {
            return;
        }

        var context = getContext();

        if (!context) {
            console.error('[Scadenzario widget] Tabella o colonna Stato scadenza non trovata.');
            return;
        }

        var currentState = readActiveState(context);
        var nextState = normalizeState(requestedState);

        if (nextState === currentState) {
            nextState = '';
        }

        updateInterface(nextState);

        context.api
            .column(context.columnIndex)
            .search(nextState)
            .draw();
    }

    function hideStateColumn(context) {
        var column = context.api.column(context.columnIndex);

        if (column.visible()) {
            column.visible(false, false);
            context.api.columns.adjust();
        }
    }

    function syncInterface() {
        var context = getContext();

        if (context) {
            hideStateColumn(context);
            updateInterface(readActiveState(context));
            getRoot().attr('data-state-column-index', context.columnIndex);
        }
    }

    $(document)
        .off('.osmScadenzarioFilter')
        .on(
            'click.osmScadenzarioFilter',
            ROOT + ' .osm-scadenzario-widget:not(:disabled)',
            function (event) {
                event.preventDefault();
                event.stopPropagation();
                applyState($(this).attr('data-state'));
            }
        )
        .on(
            'click.osmScadenzarioFilter',
            ROOT + ' .osm-scadenzario-reset-filter',
            function (event) {
                event.preventDefault();
                event.stopPropagation();
                applyState('');
            }
        )
        .on(
            'init.dt.osmScadenzarioFilter draw.dt.osmScadenzarioFilter',
            function () {
                window.setTimeout(syncInterface, 0);
            }
        );

    $(function () {
        window.setTimeout(syncInterface, 0);
    });
}(jQuery));
