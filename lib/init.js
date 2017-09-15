$(document).ready(function () {
    // Modal di default
    $('[data-href]').not('.ask, .bound').click(function () {
        launch_modal($(this).data('title'), $(this).data('href'), 1, $(this).data('target'));
    });
    $('[data-href]').not('.ask, .bound').addClass('bound');

    // Tooltip
    $('.tip').not('.tooltipstered').each(function () {
        $this = $(this);
        $this.tooltipster({
            animation: 'grow',
            contentAsHTML: true,
            hideOnClick: true,
            onlyOne: true,
            maxWidth: 350,
            touchDevices: true,
            trigger: 'hover',
            position: $this.data('position') ? $this.data('position') : 'top',
        });
    });

    // Autosize per le textarea
    autosize($('.autosize'));

    if ($('form').length) {
        $('form').not('.no-check').parsley();
    }

    window.Parsley.on('field:success', function () {
        this.$element.removeClass('parsley-success');
    });

    var icons = {
        time: 'fa fa-clock-o',
        date: 'fa fa-calendar',
        up: 'fa fa-chevron-up',
        down: 'fa fa-chevron-down',
        previous: 'fa fa-chevron-left',
        next: 'fa fa-chevron-right',
        today: 'fa fa-street-view',
        clear: 'fa fa-trash',
        close: 'fa fa-times'
    }

    $('.timestamp-picker').each(function () {
        $this = $(this);
        $this.datetimepicker({
            locale: globals.locale,
            icons: icons,
            collapse: false,
            sideBySide: true,
            useCurrent: false,
            stepping: 5,
            minDate: moment($this.attr('min-date')).isValid() ? $this.attr('min-date') : false,
            maxDate: moment($this.attr('max-date')).isValid() ? $this.attr('max-date') : false,
        });
    });

    $('.datepicker').each(function () {
        $this = $(this);
        $this.datetimepicker({
            locale: globals.locale,
            icons: icons,
            useCurrent: false,
            format: 'L',
            minDate: moment($this.attr('min-date')).isValid() ? $this.attr('min-date') : false,
            maxDate: moment($this.attr('max-date')).isValid() ? $this.attr('max-date') : false,
        });
    });

    $('.timepicker').each(function () {
        $this = $(this);
        $this.datetimepicker({
            locale: globals.locale,
            icons: icons,
            useCurrent: false,
            format: 'LT',
            stepping: 5,
            minDate: moment($this.attr('min-date')).isValid() ? $this.attr('min-date') : false,
            maxDate: moment($this.attr('max-date')).isValid() ? $this.attr('max-date') : false,
        });
    });

    // Aggiunta nell'URL del nome tab su cui tornare dopo il submit
    // Blocco del pulsante di submit dopo il primo submit
    $("form").submit(function () {
        if ($(this).parsley().validate()) {
            $(this).submit(function () {
                return false;
            });

            $(this).find('[type=submit]').prop("disabled", true).addClass("disabled");

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

            return true;
        }

        return false;
    });

    start_superselect();
    start_inputmask();
});
