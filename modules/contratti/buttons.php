<?php

include_once __DIR__.'/../../core.php';

$rs_documento = $dbo->fetchArray('SELECT * FROM co_righe_documenti WHERE idcontratto='.prepare($id_record));
if (sizeof($rs_documento) > 0) {
    echo '
    <button type="button" class="btn btn-info" disabled>
    <i class="fa fa-magic"></i> '.tr('Crea fattura').'...
    </button>';
} else {
    echo "
    <button type=\"button\" class=\"btn btn-info\" onclick=\"if( confirm('Creare una fattura per questo contratto?') ){fattura_da_contratto();}\">
    <i class=\"fa fa-magic\"></i> ".tr('Crea fattura').'...
    </button>';
}

if ($record['rinnovabile']) {
    echo "
<button type=\"button\" class=\"btn btn-warning\" onclick=\"if( confirm('Rinnovare questo contratto?') ){ location.href='".$rootdir.'/editor.php?op=renew&id_module='.$id_module.'&id_record='.$id_record."'; }\">
    <i class=\"fa fa-refresh\"></i> ".tr('Rinnova').'...
</button>';
}
