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
