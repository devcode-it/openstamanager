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
