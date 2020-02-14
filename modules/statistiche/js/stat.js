class Stat {
    constructor(calendar, file, data = {}){
        this.calendar = calendar;

        this.file = file;
        this.data = data;

        this.data.id_module = this.calendar.info.id_module;
        this.data.id_record = this.calendar.info.id_record;
        this.data.calendar_id = this.calendar.id;
    }

    getCalendarID(){
        return this.calendar.id;
    }

    getData(start, end, callback) {
        var data = JSON.parse(JSON.stringify(this.data));

        data.start = start;
        data.end = end;

        $.ajax({
            url: this.calendar.info.url + "/" + this.file,
            type: "get",
            data: data,
            success: function(data){
                callback(data)
            }
        });
    }

    add(start, end){}
    update(start, end){}
    remove(){}
}
