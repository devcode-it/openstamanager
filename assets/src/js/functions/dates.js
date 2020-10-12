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

function getCalendarIcons(){
   return {
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
}

function initDateInput(input) {
    let date_format = dateFormatMoment(globals.date_format);
    let calendar_icons = getCalendarIcons();

    $(input).datetimepicker({
        format: date_format,
        locale: globals.locale,
        icons: calendar_icons,
        useCurrent: false,
        minDate: moment($this.attr('min-date')).isValid() ? $this.attr('min-date') : false,
        maxDate: moment($this.attr('max-date')).isValid() ? $this.attr('max-date') : false,
    });

    return true;
}

function initTimestampInput(input) {
    let $this = $(input);
    let timestamp_format = dateFormatMoment(globals.timestamp_format);
    let calendar_icons = getCalendarIcons();

    $this.datetimepicker({
        format: timestamp_format,
        locale: globals.locale,
        icons: calendar_icons,
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

    // fix per timestamp-picker non visibile con la classe table-responsive
    $this.on("dp.show", function (e) {
        $('#tecnici > div').removeClass('table-responsive');
    });

    $this.on("dp.hide", function (e) {
        $('#tecnici > div').addClass('table-responsive');
    });

    return true;
}

function initTimeInput(input) {
    let time_format = dateFormatMoment(globals.time_format);
    let calendar_icons = getCalendarIcons();

    $(input).datetimepicker({
        format: time_format,
        locale: globals.locale,
        icons: calendar_icons,
        useCurrent: false,
        stepping: 5,
        minDate: moment($this.attr('min-date')).isValid() ? $this.attr('min-date') : false,
        maxDate: moment($this.attr('max-date')).isValid() ? $this.attr('max-date') : false,
    });

    return true;
}

/**
 * @deprecated
 */
function start_datepickers() {
    $('.timestamp-picker').each(function () {
        input(this);
    });

    $('.datepicker').each(function () {
        input(this);
    });

    $('.timepicker').each(function () {
        input(this);
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
