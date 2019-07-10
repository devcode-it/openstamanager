class Widget extends Stat {
    constructor(calendar, id){
        super(calendar, id);
    }

    getData(start, end, callback) {
        $.ajax({
            url: this.calendar.info.url + "/info.php",
            type: "get",
            data: {
                id_module: this.calendar.info.id_module,
                id_record: this.calendar.info.id_record,
                calendar_id: this.calendar.id,
                dir: this.direzione,
                start: start,
                end: end,
            },
            success: function(data){
                callback(data)
            }
        });
    }

    add(start, end) {
        var id = this.id;
        var calendar_id = this.calendar.id;

        this.getData(start, end, function(data) {
            var row = $(id).find("#row-" + calendar_id);

            if (!row.length) {
                $(id).append(data);
            } else {
                row.after(data);
                row.remove();
            }

            $(id).find("#row-" + calendar_id).effect("highlight", {}, 3000);
        });
    }

    update(start, end) {
        this.add(start, end)
    }

    remove(start, end) {
        $(this.id).find("#row-" + this.calendar.id).remove();
    }
}
