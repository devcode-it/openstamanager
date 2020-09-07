<?php
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

echo '
<button type="button" class="btn btn-primary" onclick="testAccount(this)">
    <i class="fa fa-id-card-o"></i> '.tr('Salva e controlla credenziali').'
</button>

<script>
function testAccount(btn){
    submitAjax("#edit-form", {}, function(data) {
        var restore = buttonLoading(btn);

        $.ajax({
            url: globals.rootdir + "/actions.php",
            cache: false,
            type: "POST",
            data: {
                id_module: globals.id_module,
                id_record: globals.id_record,
                op: "test",
            },
            success: function(data) {
                buttonRestore(btn, restore);

                data = JSON.parse(data);
                if(data.test){
                    swal("'.tr('Connessione SMTP riuscita').'", "'.tr("Connessione all'account SMTP completata con successo").'", "success");
                } else {
                    swal("'.tr('Connessione SMTP fallita').'", "'.tr("Impossibile connettersi all'account SMTP").'", "error");
                }
            },
            error: function(data) {
                swal("'.tr('Errore').'", "'.tr('Errore durante il test').'", "error");

                buttonRestore(btn, restore);
            }
        });
    })
}
</script>';
