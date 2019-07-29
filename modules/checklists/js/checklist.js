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
            }
        });
    }

    deleteCheck(id, user_id) {
        var check = this.findCheck(id);
        if (check.user_id != user_id && check.assigned_user_id != user_id) {
            return false;
        }

        check.icon.removeClass("fa-check-square-o").addClass("fa-refresh fa-spin bg-danger").show();

        this.request({
            op: "delete_check",
            check_id: id,
        });

        return true;
    }

    toggleCheck(id, user_id) {
        var check = this.findCheck(id);
        if (check.assigned_user_id != user_id) {
            return false;
        }

        check.icon.removeClass("fa-square-o fa-check-square-o ").addClass("fa-refresh fa-spin").show();

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
            icon: li.find(".check-icon"),
            date: li.find(".check-date"),
            text: li.find(".check-text"),
            children: li.find(".check-children"),
            user_id: li.data('user_id'),
            assigned_user_id: li.data('assigned_user_id'),
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
