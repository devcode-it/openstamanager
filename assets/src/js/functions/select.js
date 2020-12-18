/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

/**
 * Select.
 *
 * @deprecated
 */
function start_superselect() {
    $('.superselect, .superselectajax').each(function () {
        input(this);
    });
}

/**
 * Gestisce le operazioni di rendering per una singola opzione del select.
 *
 * @param data
 * @param container
 * @returns {*}
 */
function optionRendering(data, container) {
    // Aggiunta degli attributi impostati staticamente
    selectOptionAttributes(data);

    // Impostazione del colore dell'opzione
    let bg;
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
}

/**
 * Gestisce le operazioni di rendering per le opzioni selezionate del select.
 *
 * @param data
 * @returns {*}
 */
function selectionRendering(data) {
    // Aggiunta degli attributi impostati staticamente
    selectOptionAttributes(data);

    return data.text;
}

/**
 * Gestisce le operazioni per l'impostazione dinamica degli attributi per una singola opzione del select.
 *
 * @param data
 * @returns {void}
 */
function selectOptionAttributes(data) {
    // Aggiunta degli attributi impostati staticamente
    let attributes = $(data.element).data("select-attributes");
    if (attributes) {
        for ([key, value] of Object.entries(attributes)) {
            data[key] = value;
        }
    }
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

    if (placeholder !== undefined) {
        this.next().find('.select2-selection__placeholder').text(placeholder);
        this.next().find('input.select2-search__field').attr('placeholder', placeholder);
    }

    return this;
};

/**
 * Aggiorna un <select> creato con select2 impostando un valore di default.
 * Da utilizzare per l'impostazione dei select basati su richieste AJAX.
 */
jQuery.fn.selectSetNew = function (value, label, data) {
    // Fix selezione per valori multipli
    let values = this.val();
    if (this.prop("multiple")) {
        values.push(value);
    } else {
        this.selectReset();
        values = value;
    }

    this.selectAdd([{
        'value': value,
        'text': label,
        'data': data,
    }]);

    this.selectSet(values);

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
    let $this = this;

    values.forEach(function (item) {
        if (item.data) {
            item['data-select-attributes'] = JSON.stringify(item.data);

            // Retrocompatibilità per l'uso del attributo data su selectData
            Object.keys(item.data).forEach(function (element) {
                item['data-' + element] = item.data[element];
            });
        }

        delete item.data;

        const option = $('<option/>', item);
        $this.append(option);
    });

    return this;
};

/**
 * Restituisce l'oggetto contenente gli attributi di una <option> generata da select2.
 */
jQuery.fn.selectData = function () {
    let selectData = this.select2('data');

    if (this.prop('multiple')) {
        return selectData;
    } else if (selectData.length !== 0 && selectData[0].id) {
        return selectData[0];
    }

    return undefined;
};

/**
 * Imposta il valore di un'opzione di un <select> creato con select2.
 */
jQuery.fn.setSelectOption = function (name, value) {
    this.data('select-options')[name] = value;

    return this;
};

/**
 * Restituisce il valore impostato per un'opzione di un <select> creato con select2.
 */
jQuery.fn.getSelectOption = function (name) {
    return this.data('select-options')[name];
};

/**
 * Imposta il valore di un opzioni per tutti i select attivi della pagina.
 *
 * @param name
 * @param value
 */
function updateSelectOption(name, value) {
    $(".superselectajax").each(function () {
        $(this).setSelectOption(name, value);
    })
}

/**
 * Funzione per l'inizializzazione automatica del select.
 *
 * @param input
 */
function initSelectInput(input) {
    let $input = $(input);

    if ($input.hasClass('superselect')) {
        initStaticSelectInput(input);
    } else {
        initDynamicSelectInput(input);
    }

    return $input.data('select2');
}

/**
 * Funzione per l'inizializzazione del select statico.
 *
 * @param input
 */
function initStaticSelectInput(input) {
    let $input = $(input);

    $input.select2({
        theme: "bootstrap",
        language: "it",
        width: '100%',
        maximumSelectionLength: $input.data('maximum') ? $input.data('maximum') : -1,
        minimumResultsForSearch: $input.hasClass('no-search') ? -1 : 0,
        allowClear: !$input.hasClass('no-search'),
        escapeMarkup: function (text) {
            return text;
        },
        templateResult: optionRendering,
        templateSelection: selectionRendering,
    });
}

/**
 * Funzione per l'inizializzazione del select dinamico.
 *
 * @param input
 */
function initDynamicSelectInput(input) {
    let $input = $(input);

    $input.select2({
        theme: "bootstrap",
        language: "it",
        maximumSelectionLength: $input.data('maximum') ? $input.data('maximum') : -1,
        minimumInputLength: $input.data('heavy') ? 3 : 0,
        allowClear: true,
        escapeMarkup: function (text) {
            return text;
        },
        templateResult: optionRendering,
        templateSelection: selectionRendering,
        ajax: {
            url: globals.rootdir + "/ajax_select.php?op=" + $input.data('source'),
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term,
                    page: params.page || 0,
                    length: params.length || 100,
                    options: this.data('select-options'), // Dati aggiuntivi
                };
            },
            processResults: function (data, params) {
                params.page = params.page || 0;
                params.length = params.length || 100;

                let results = data.results;

                // Interpretazione forzata per campi optgroup
                if (results && results[0] && results[0]['optgroup']) {
                    let groups = results.reduce(function (r, a) {
                        r[a.optgroup] = r[a.optgroup] || [];
                        r[a.optgroup].push(a);
                        return r;
                    }, {});

                    let results_groups = [];
                    for ([key, results] of Object.entries(groups)) {
                        results_groups.push({
                            text: key,
                            children: groups[key],
                        });
                    }
                    results = results_groups;
                }

                return {
                    results: results,
                    pagination: {
                        more: (params.page + 1) * params.length < data.recordsFiltered,
                    }
                };
            },
            cache: false
        },
        width: '100%'
    });

    // Rimozione delle option presenti nell'HTML per permettere l'aggiornamento dei dati via AJAX
    // Rimozione per select multipli
    if ($input.prop("multiple")) {
        $input.on('select2:unselecting', function (e) {
            let data = e.params ? e.params.data : null;
            if (data) {
                let option = $input.find('option[value="' + data.id + '"]');
                option.remove();
            }
        });
    }
    // Rimozione per select singoli
    else {
        $input.on('select2:selecting', function (e) {
            let data = $input.selectData();
            if (data) {
                let option = $input.find('option[value="' + data.id + '"]');
                option.remove();
            }
        });
    }
}

