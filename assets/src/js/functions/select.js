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
                        search: params.term,
                        page: params.page || 0,
                        length: params.length || 100,
                        options: this.data('select-options'), // Dati aggiuntivi
                    };
                },
                processResults: function (data, params) {
                    params.page = params.page || 0;
                    params.length = params.length || 100;

                    return {
                        results: data.results,
                        pagination: {
                            more: (params.page + 1) * params.length < data.recordsFiltered,
                        }
                    };
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
    var values = this.val();
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
    $this = this;

    values.forEach(function (item) {
        if (item.data) {
            Object.keys(item.data).forEach(function (element) {
                item['data-' + element] = item.data[element];
            });
        }

        delete item.data;

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

    if ($select_obj[0] === undefined) {
        return undefined;
    } else {
        if ($select_obj[0].selected === false) {
            return $select_obj[0];
        } else {
            return $select_obj[0].element.dataset;
        }
    }
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
function updateSelectOption(name, value){
    $(".superselectajax").each(function (){
        $(this).setSelectOption(name, value);
    })
}
