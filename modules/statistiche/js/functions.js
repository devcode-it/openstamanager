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

var manager = manager ? manager : undefined;

function remove_calendar(button) {
    var name = $(button).parent().find("input").attr("id");

    if (manager.remove(name)) {
        $("#group-" + name).remove();
    } else {
        swal({
            title: globals.translations.singleCalendar,
            type: "info",
        });
    }
}

function add_calendar() {
    var last = $("#calendars").find("input").last().attr("id");
    var last_id = last ? last.split("-")[1] : 0;
    last_id = parseInt(last_id) + 1;

    var name = "calendar-" + last_id;

    $("#calendars").append(`<div class="col-md-6" id="group-` + name + `">
    <div class="input-group mb-3">
        <div class="input-group-prepend">
            <span class="input-group-text bg-info text-white">` + last_id + `</span>
        </div>
        <input class="form-control calendar-input text-center" type="text" name="` + name + `" id="` + name + `" placeholder="Seleziona periodo"/>
        <div class="input-group-append">
            <button class="btn btn-outline-danger" onclick="remove_calendar(this)" title="Rimuovi periodo">
                <i class="fa fa-trash"></i>
            </button>
        </div>
    </div>
</div>`);

    start_complete_calendar("#" + name, function (start, end) {
        var name = $(this.element).attr("id");
        var start = start.format("YYYY-MM-DD");
        var end = end.format("YYYY-MM-DD");

        manager.update(name, start, end);
    });

    // Inizializzazone calendario
    var calendar = manager.add(last_id, name);

    init_calendar(calendar);

    manager.init(name);
}

function get_months(start, end) {
    var months = [];
    while (start.isSameOrBefore(end, "month")) {
        string = start.format("MMMM YYYY");

        months.push(string.charAt(0).toUpperCase() + string.slice(1));

        start.add(1, "months");
    }

    return months;
}

