<?php

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
