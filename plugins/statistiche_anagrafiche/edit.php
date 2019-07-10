<?php

include_once __DIR__.'/../../core.php';

echo '
<hr>
<div class="box box-warning">
    <div class="box-header">
        <h4 class="box-title">
            '.tr('Periodi temporali').'
        </h4>
        <div class="box-tools pull-right">
            <button class="btn btn-warning btn-xs" onclick="add_calendar()">
                <i class="fa fa-plus"></i> '.tr('Aggiungi periodo').'
            </button>
            <button type="button" class="btn btn-box-tool" data-widget="collapse">
                <i class="fa fa-minus"></i>
            </button>
        </div>
    </div>
    
    <div class="box-body collapse in" id="calendars">
        
    </div>
</div>

<div id="widgets">
        
</div>
    
<script src="'.$structure->fileurl('js/stat.js').'"></script>
<script src="'.$structure->fileurl('js/calendar.js').'"></script>
<script src="'.$structure->fileurl('js/widget.js').'"></script>

<script>
var calendars = {};
var info = {
    url: "'.str_replace('edit.php', '', $structure->fileurl('edit.php')).'",
    id_module: globals.id_module,
    id_record: globals.id_record,
};

$(document).ready(function() {
    add_calendar();
});

function remove_calendar(button) {
    if (Object.keys(calendars).length > 1){
        var name = $(button).parent().find("input").attr("id");
    
        calendars[name].remove();
        delete calendars[name];
        
        $("#group-" + name).remove();
    } else {
        swal({
            title: "'.tr("E' presente un solo calendario!").'",
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
    
    $("#" + name).daterangepicker({
        locale: {
            customRangeLabel: globals.translations.custom,
            applyLabel: globals.translations.apply,
            cancelLabel: globals.translations.cancel,
            fromLabel: globals.translations.from,
            toLabel: globals.translations.to,
        },
        startDate: globals.start_date,
        endDate: globals.end_date,
        applyClass: "btn btn-success btn-sm",
        cancelClass: "btn btn-danger btn-sm",
        linkedCalendars: false
    }, function (start, end) {
        var name = $(this.element).attr("id");
        var start = start.format("YYYY-MM-DD");
        var end = end.format("YYYY-MM-DD");

        calendars[name].update(start, end);
    });
    
    $("#" + name).on("apply.daterangepicker", function(ev, picker) {
        
    });
    
    // Inizializzazone calendario
    var calendar = new Calendar(info, last_id);
    calendars[name] = calendar;

    var widgets = new Widget(calendar, "#widgets");
    
    calendar.addElement(widgets);
    
    calendar.update(globals.start_date, globals.end_date);
}
</script>';
