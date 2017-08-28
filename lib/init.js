$(document).ready(function () {
    $('[data-href]').not('.ask, .bound').click(function () {
        $(this).addClass('bound');
        launch_modal($(this).data('title'), $(this).data('href'), 1, $(this).data('target'));
    });

    $('.tip').not('.tooltipstered').tooltipster({
        animation: 'grow',
        contentAsHTML: true,
        hideOnClick: true,
        onlyOne: true,
        maxWidth: 350,
        touchDevices: true,
        trigger: 'hover',
        position: 'top'
    });

    autosize($('.autosize'));

    $(".bootstrap-switch").bootstrapSwitch();

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
            useCurrent: false,
            minDate: $this.attr('min-date') ? $this.attr('min-date') : false,
            maxDate: $this.attr('max-date') ? $this.attr('max-date') : false,
        });
    });

    $('.datepicker').each(function () {
        $this = $(this);
        $this.datetimepicker({
            locale: globals.locale,
            icons: icons,
            useCurrent: false,
            format: 'L',
            minDate: $this.attr('min-date') ? $this.attr('min-date') : false,
            maxDate: $this.attr('max-date') ? $this.attr('max-date') : false,
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
            minDate: $this.attr('min-date') ? $this.attr('min-date') : false,
            maxDate: $this.attr('max-date') ? $this.attr('max-date') : false,
        });
    });

    $("form").submit(function() {
        if ($(this).parsley().validate()) {
            $(this).submit(function() {
                return false;
            });

            $(this).find('[type=submit]').prop("disabled", true).addClass("disabled");

            return true;
        }

        return false;
    });

    start_superselect();
    start_inputmask();
});
