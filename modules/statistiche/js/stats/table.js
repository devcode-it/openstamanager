/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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
