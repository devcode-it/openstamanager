class Manager {
    constructor(info) {
        this.info = info;

        this.calendars = {};
    }

    remove(name) {
        if (Object.keys(this.calendars).length > 1) {
            this.calendars[name].remove();
            delete this.calendars[name];

            return true;
        }

        return false;
    }

    add(id, name) {
        var calendar = new Calendar(this.info, id);
        this.calendars[name] = calendar;

        return calendar;
    }

    init(name) {
        var calendar = this.calendars[name];

        var start = this.info.start_date;
        var end = this.info.end_date;

        calendar.update(start, end);
    }

    update(name, start, end){
        this.calendars[name].update(start, end);
    }
}
