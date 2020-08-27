<?php

include_once __DIR__.'/../../core.php';

if (empty($_SESSION['giacenze_sedi']['idsede'])) {
    $_SESSION['giacenze_sedi']['idsede'] = 0;
}
$id_sede = $_SESSION['giacenze_sedi']['idsede'];

echo '
<div class="row">
    <div class="col-md-offset-8 col-md-4">
        {["type":"select", "label":"'.tr('Sede').'", "name": "id_sede", "ajax-source": "sedi_azienda", "value":"'.$id_sede.'" ]}
    </div>
</div>

<script>
    $("#id_sede").change(function(){
        session_set("giacenze_sedi,idsede", $(this).val(), 0);
        setTimeout(function(){
            location.reload();
        }, 500);
    });
</script>';
