<?php

// Modifica rapida nell'elenco: usa controller_after.php, previsto dal manager
// standard di OSM, senza sostituire la DataTable del core.
if (empty($table_id)) {
    return;
}

$can_inline = $structure->permission === 'rw';
$user = auth_osm()->getUser();
$group_id = (int) ($user->id_gruppo ?? 0);
$editable_names = $can_inline ? [
    'Data' => 'data',
    'Descrizione' => 'descrizione',
    'Controparte' => 'controparte',
    'Importo' => 'importo',
] : [];

$views = $dbo->fetchArray(
    'SELECT v.`name` FROM `zz_views` v '
    .'INNER JOIN `zz_group_view` gv ON gv.`id_vista` = v.`id` '
    .'WHERE v.`id_module` = '.prepare((int) $id_module).' '
    .'AND gv.`id_gruppo` = '.prepare($group_id).' '
    .'AND v.`visible` = 1 '
    .'ORDER BY v.`order` ASC'
);

// La prima colonna DataTables è il selettore, quindi gli indici delle viste
// visibili partono da 1.
$editable_columns = [];
$amount_column = null;
$column_index = 1;
foreach ($views as $view) {
    $name = (string) ($view['name'] ?? '');
    if ($name === 'Importo') {
        $amount_column = $column_index;
    }
    if (isset($editable_names[$name])) {
        $editable_columns[$column_index] = $editable_names[$name];
    }
    ++$column_index;
}

if (empty($editable_columns) && $amount_column === null) {
    return;
}

$separators = formatter()->getNumberSeparators();
?>
<style>
#<?php echo prepareToField($table_id); ?> .ns-inline-cell {
    position: relative;
    min-height: 24px;
    padding-right: 22px;
}
#<?php echo prepareToField($table_id); ?> .ns-inline-trigger {
    position: absolute;
    right: 1px;
    top: 50%;
    transform: translateY(-50%);
    padding: 1px 4px;
    border: 0;
    background: transparent;
    opacity: .25;
    color: #6c757d;
    cursor: pointer;
    line-height: 1;
    z-index: 2;
}
#<?php echo prepareToField($table_id); ?> td:hover .ns-inline-trigger,
#<?php echo prepareToField($table_id); ?> .ns-inline-trigger:focus {
    opacity: .95;
}
#<?php echo prepareToField($table_id); ?> .ns-inline-editor {
    width: 100%;
    min-width: 85px;
    height: 28px;
    padding: 2px 6px;
    font-size: inherit;
}
#<?php echo prepareToField($table_id); ?> .ns-inline-saving {
    opacity: .55;
}
</style>
<script>
$(document).ready(function () {
    const tableSelector = <?php echo json_encode('#'.$table_id); ?>;
    const editableColumns = <?php echo json_encode($editable_columns, JSON_UNESCAPED_UNICODE); ?>;
    const amountColumn = <?php echo $amount_column !== null ? (int) $amount_column : 'null'; ?>;
    const decimalSeparator = <?php echo json_encode((string) ($separators['decimals'] ?? ',')); ?>;
    const thousandsSeparator = <?php echo json_encode((string) ($separators['thousands'] ?? '.')); ?>;
    const idModule = <?php echo (int) $id_module; ?>;
    const editTitle = <?php echo json_encode(tr('Modifica rapidamente')); ?>;
    const invalidResponse = <?php echo json_encode(tr('Impossibile aggiornare la nota spesa.')); ?>;
    const outsidePeriodMessage = <?php echo json_encode(tr('La data è fuori dal periodo selezionato: la riga non sarà più visibile nell’elenco corrente.')); ?>;

    const $table = $(tableSelector);
    let footerObserver = null;

    function parseLocalizedNumber(value) {
        value = String(value ?? '').replace(/\u00a0/g, ' ').replace(/\s/g, '').replace(/€/g, '');
        if (thousandsSeparator) {
            value = value.split(thousandsSeparator).join('');
        }
        if (decimalSeparator && decimalSeparator !== '.') {
            value = value.split(decimalSeparator).join('.');
        }
        const number = Number.parseFloat(value);
        return Number.isFinite(number) ? number : null;
    }

    function formatTwoDecimals(value) {
        const number = parseLocalizedNumber(value);
        if (number === null) {
            return String(value ?? '');
        }

        const fixed = number.toFixed(2).split('.');
        let integer = fixed[0];
        const sign = integer.startsWith('-') ? '-' : '';
        if (sign) {
            integer = integer.substring(1);
        }
        if (thousandsSeparator) {
            integer = integer.replace(/\B(?=(\d{3})+(?!\d))/g, thousandsSeparator);
        }

        return sign + integer + (decimalSeparator || ',') + fixed[1];
    }

    function getApi() {
        return $.fn.DataTable.isDataTable(tableSelector) ? $table.DataTable() : null;
    }

    function formatAmountFooter() {
        if (amountColumn === null) {
            return;
        }
        const api = getApi();
        if (!api) {
            return;
        }

        const footer = api.column(amountColumn).footer();
        if (!footer) {
            return;
        }

        const $footer = $(footer);
        const current = $.trim($footer.text());
        if (current !== '') {
            const formatted = formatTwoDecimals(current);
            if (formatted !== current) {
                $footer.text(formatted);
            }
        }

        if (!footerObserver) {
            footerObserver = new MutationObserver(function () {
                const value = $.trim($footer.text());
                if (value === '') {
                    return;
                }
                const formatted = formatTwoDecimals(value);
                if (formatted !== value) {
                    $footer.text(formatted);
                }
            });
            footerObserver.observe(footer, {childList: true, characterData: true, subtree: true});
        }
    }

    function startEditor($box, field, rowId) {
        if ($box.find('.ns-inline-editor').length) {
            return;
        }

        const $display = $box.find('.ns-inline-display');
        const $trigger = $box.find('.ns-inline-trigger');
        const originalValue = $.trim($display.text());
        const $input = $('<input type="text" class="form-control form-control-sm ns-inline-editor">').val(originalValue);
        let completed = false;

        $display.hide();
        $trigger.hide();
        $box.append($input);
        $input.trigger('focus').select();

        function restore() {
            if (completed) {
                return;
            }
            completed = true;
            $input.remove();
            $display.show();
            $trigger.show();
        }

        function save() {
            if (completed) {
                return;
            }

            const newValue = $.trim($input.val());
            if (newValue === originalValue) {
                restore();
                return;
            }

            completed = true;
            $input.prop('disabled', true);
            $box.addClass('ns-inline-saving');

            $.ajax({
                url: globals.rootdir + '/actions.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    id_module: idModule,
                    id_record: rowId,
                    op: 'inline_update',
                    field: field,
                    value: newValue,
                }
            }).done(function (response) {
                if (!response || response.success !== true) {
                    toastr.error(response && response.message ? response.message : invalidResponse);
                    $box.removeClass('ns-inline-saving');
                    $input.remove();
                    $display.show();
                    $trigger.show();
                    return;
                }

                if (response.requires_review) {
                    toastr.warning(response.message);
                } else if (response.message) {
                    toastr.success(response.message);
                }

                if (response.outside_period) {
                    toastr.warning(outsidePeriodMessage);
                }

                const api = getApi();
                if (api) {
                    api.ajax.reload(null, false);
                }
            }).fail(function (xhr) {
                const response = xhr.responseJSON || {};
                toastr.error(response.message || invalidResponse);
                $box.removeClass('ns-inline-saving');
                $input.remove();
                $display.show();
                $trigger.show();
            });
        }

        $input.on('click mousedown', function (event) {
            event.stopPropagation();
        });
        $input.on('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                save();
            } else if (event.key === 'Escape') {
                event.preventDefault();
                restore();
            }
        });
        $input.on('blur', save);
    }

    function prepareCells() {
        const api = getApi();
        if (!api) {
            return;
        }

        $table.find('tbody tr').each(function () {
            const $row = $(this);
            const rowId = parseInt($row.attr('id'), 10);
            if (!rowId) {
                return;
            }

            // L'importo viene sempre mostrato con due decimali, anche per
            // gli utenti con permesso di sola lettura. Il valore resta numerico
            // nella DataTable, quindi ordinamento, filtri e somma rimangono nativi.
            if (amountColumn !== null) {
                const $amountCell = $row.children('td').eq(amountColumn);
                const $amountBox = $amountCell.children('div').first();
                if ($amountBox.length && !$amountBox.find('.ns-inline-editor').length) {
                    const $amountDisplay = $amountBox.find('.ns-inline-display');
                    if ($amountDisplay.length) {
                        $amountDisplay.text(formatTwoDecimals($.trim($amountDisplay.text())));
                    } else {
                        $amountBox.text(formatTwoDecimals($.trim($amountBox.text())));
                    }
                }
            }

            Object.keys(editableColumns).forEach(function (key) {
                const columnIndex = parseInt(key, 10);
                const field = editableColumns[key];
                const $cell = $row.children('td').eq(columnIndex);
                const $box = $cell.children('div').first();
                if (!$box.length || $box.hasClass('ns-inline-cell')) {
                    return;
                }

                const currentHtml = $box.html();
                $box.empty()
                    .addClass('ns-inline-cell')
                    .append($('<span class="ns-inline-display"></span>').html(currentHtml));

                const $trigger = $('<button type="button" class="ns-inline-trigger" aria-label=""></button>')
                    .attr('title', editTitle)
                    .attr('aria-label', editTitle)
                    .append('<i class="fa fa-pencil"></i>');

                // Handler diretto sul pulsante: viene eseguito prima del click
                // del TD impostato dal core per aprire la scheda.
                $trigger.on('click.noteSpese', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    startEditor($box, field, rowId);
                });

                $box.append($trigger);
            });
        });

        formatAmountFooter();
    }

    $table.on('init.dt.noteSpese draw.dt.noteSpese', function () {
        window.setTimeout(prepareCells, 0);
    });

    // Se la DataTable è già stata inizializzata prima di questo handler.
    window.setTimeout(prepareCells, 50);
});
</script>
