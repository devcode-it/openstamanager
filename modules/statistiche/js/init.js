// Inzializzazione manager standard
var info = {
    url: local_url,
    id_module: globals.id_module,
    id_record: globals.id_record,
    start_date: globals.start_date,
    end_date: globals.end_date,
};

var manager = new Manager(info);
add_calendar();
