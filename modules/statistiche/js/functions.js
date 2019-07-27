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

    $("#calendars").append(`<div class="col-md-4" id="group-` + name + `">
    <div class="input-group">
        <span class="input-group-addon before">` + last_id + `</span>
        <input class="form-control calendar-input text-center" type="text" name="` + name + `" id="` + name + `"/>
        <span class="input-group-addon after clickable btn btn-danger" onclick="remove_calendar(this)">
            <i class="fa fa-trash-o"></i>
        </span>
    </div>
    <br>
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

