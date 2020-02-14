class Table extends Stat {
    constructor(calendar, file, data = {}, id){
        super(calendar, file, data);

        this.id = id;
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

    remove() {
        $(this.id).find("#row-" + this.calendar.id).remove();
    }
}
