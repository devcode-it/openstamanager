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

class Checklist {
    constructor(info, id){
        this.info = info;
        this.id = id;
    }

    cloneChecklist(data){
        data.op = "clone_checklist";

        this.request(data);
    }

    addCheck(data){
        data.op = "add_check";

        this.request(data);
    }

    request(info){
        var $this = this;
        $this.showLoader();

        var data = {
            ...this.info,
            ...info,
        };

        $.ajax({
            url: globals.rootdir + "/actions.php",
            cache: false,
            type: "POST",
            data: data,
            success: function() {
                $this.reload();

                renderMessages();
            }
        });
    }

    deleteCheck(id) {
        this.request({
            op: "delete_check",
            check_id: id,
        });

        return true;
    }

    toggleCheck(id) {
        this.request({
            op: "toggle_check",
            check_id: id,
        });

        return true;
    }

    findCheck(id) {
        var li = $("#check_" + id);

        return {
            item: li,
            input: li.find("input"),
            info: li.find(".badge"),
            text: li.find(".text"),
            children: li.find("ul"),
        };
    }

    showLoader() {
        $("#loading_" + this.id).removeClass("hide");
    }

    reload() {
        var $this = this;
        $("#" + $this.id).load(globals.rootdir + "/ajax.php?op=checklists&id_module=" + $this.info.id_module + "&id_record=" + $this.info.id_record + "&id_plugin=" + $this.info.id_plugin, function() {
            $("#loading_" + $this.id).addClass("hide");
        });
    }
}

export default Checklist;
