<?php

include_once __DIR__.'/../../core.php';

echo '
<button type="button" class="btn btn-primary" onclick="duplicaArticolo()">
    <i class="fa fa-copy"></i> '.tr('Duplica articolo').'
</button>

<script>
function duplicaArticolo() {
    openModal("'.tr('Duplica articolo').'", "'.$module->fileurl('modals/duplicazione.php').'?id_module='.$id_module.'&id_record='.$id_record.'");
}
</script>';
