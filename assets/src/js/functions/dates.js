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

function start_datepickers() {
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
    };

    var date_format = dateFormatMoment(globals.date_format);
    var timestamp_format = dateFormatMoment(globals.timestamp_format);
    var time_format = dateFormatMoment(globals.time_format);

    $('.timestamp-picker').each(function () {
        $this = $(this);
        $this.datetimepicker({
            format: timestamp_format,
            locale: globals.locale,
            icons: icons,
            collapse: false,
            sideBySide: true,
            useCurrent: false,
            stepping: 5,
            widgetPositioning: {
                horizontal: 'left',
                vertical: 'auto'
            },
            minDate: moment($this.attr('min-date')).isValid() ? $this.attr('min-date') : false,
            maxDate: moment($this.attr('max-date')).isValid() ? $this.attr('max-date') : false,
        });
    });

    //fix per timestamp-picker non visibile con la classe table-responsive
    $('.timestamp-picker').each(function () {
        $this = $(this);
        $this.on("dp.show", function (e) {
            $('#tecnici > div').removeClass('table-responsive');
        });
        $this.on("dp.hide", function (e) {
            $('#tecnici > div').addClass('table-responsive');
        })
    });

    $('.datepicker').each(function () {
        $this = $(this);
        $this.datetimepicker({
            format: date_format,
            locale: globals.locale,
            icons: icons,
            useCurrent: false,
            minDate: moment($this.attr('min-date')).isValid() ? $this.attr('min-date') : false,
            maxDate: moment($this.attr('max-date')).isValid() ? $this.attr('max-date') : false,
        });
    });

    $('.timepicker').each(function () {
        $this = $(this);
        $this.datetimepicker({
            format: time_format,
            locale: globals.locale,
            icons: icons,
            useCurrent: false,
            stepping: 5,
            minDate: moment($this.attr('min-date')).isValid() ? $this.attr('min-date') : false,
            maxDate: moment($this.attr('max-date')).isValid() ? $this.attr('max-date') : false,
        });
    });
}

function start_complete_calendar(id, callback) {
    var ranges = {};
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

    var format = dateFormatMoment(globals.date_format);
    $(id).daterangepicker({
            locale: {
                format: format,
                customRangeLabel: globals.translations.custom,
                applyLabel: globals.translations.apply,
                cancelLabel: globals.translations.cancel,
                fromLabel: globals.translations.from,
                toLabel: globals.translations.to,
            },
            ranges: ranges,
            startDate: globals.start_date_formatted,
            endDate: globals.end_date_formatted,
            applyClass: 'btn btn-success btn-sm',
            cancelClass: 'btn btn-danger btn-sm',
            linkedCalendars: false
        },
        callback
    );
}
