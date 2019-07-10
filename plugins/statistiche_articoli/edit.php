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
            <button class="btn btn-warning btn-xs" onclick="add_period()">
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

<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title">'.tr('Prezzo medio acquisto').'</h3>
    </div>
    
    <div class="panel-body">
        <table class="table table-striped table-condensed table-bordered">
            <thead>
                <tr>
                    <th>'.tr('Perido').'</th>
                    <th>'.tr('Prezzo minimo').'</th>
                    <th>'.tr('Prezzio medio').'</th>
                    <th>'.tr('Prezzo massimo').'</th>
                    <th>'.tr('Oscillazione').'</th>
                    <th>'.tr('Oscillazione in %').'</th>
                    <th>'.tr('Andamento prezzo').'</th>
                </tr>
            </thead>
            <tbody id="prezzi_acquisto">
                
            </tbody>
        </table>
    </div>
</div>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title">'.tr('Prezzo medio vendita').'</h3>
    </div>
    
    <div class="panel-body">
        <table class="table table-striped table-condensed table-bordered">
            <thead>
                <tr>
                    <th>'.tr('Perido').'</th>
                    <th>'.tr('Prezzo minimo').'</th>
                    <th>'.tr('Prezzio medio').'</th>
                    <th>'.tr('Prezzo massimo').'</th>
                    <th>'.tr('Oscillazione').'</th>
                    <th>'.tr('Oscillazione in %').'</th>
                    <th>'.tr('Andamento prezzo').'</th>
                </tr>
            </thead>
            <tbody id="prezzi_vendita">
                
            </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function() {
    add_period();
});

function change_period(calendar, start, end) {
    add_prezzo("#prezzi_acquisto", calendar, "uscita", start, end);
    add_prezzo("#prezzi_vendita", calendar, "entrata", start, end);
}

function add_prezzo(id, calendar, direzione, start, end) {
    $.ajax({
        url: "'.$structure->fileurl('add_prezzo.php').'",
        type: "get",
        data: {
            id_module: globals.id_module,
            id_record: globals.id_record,
            calendar: calendar,
            dir: direzione,
            start: start,
            end: end,
        },
        success: function(data){
            var row = $(id).find("#row-" + calendar);

            if (!row.length) {
                $(id).append(data);
            } else {
                row.after(data);
                row.remove();
            }
            
            $("#row-" + calendar).effect("highlight", {}, 3000);
        }
	});
}

function add_period() {
    var last = $("#calendars").find("input").last().attr("id");
    var last_id = last ? last.split("-")[1] : 0;
    last_id = parseInt(last_id) + 1;

    var name = "calendar-" + last_id;
    
    $("#calendars").append(\'<div class="col-md-4"><input class="form-control" type="text" name="\' + name + \'" id="\' + name + \'"/><br></div>\');

    $("body").on("focus", "#" + name, function() {
        $(this).daterangepicker({
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
        });
        
        var id = $(this).attr("id").split("-")[1];
        change_period(id, globals.start_date, globals.end_date);
                
        $(this).on("apply.daterangepicker", function(ev, picker) {
            var id = $(this).attr("id").split("-")[1];
            var start = picker.startDate.format("YYYY-MM-DD");
            var end = picker.endDate.format("YYYY-MM-DD");

            console.log(id, start, end);
            change_period(id, start, end);
        });
    });
    
    $("#" + name).focus();
}
</script>';
