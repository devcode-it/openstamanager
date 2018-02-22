<?php

include_once __DIR__.'/../../core.php';

if ($records[0]['rinnovabile']) {
    echo "
<button type=\"button\" class=\"btn btn-warning\" onclick=\"if( confirm('Rinnovare questo contratto?') ){ location.href='".$rootdir.'/editor.php?op=renew&id_module='.$id_module.'&id_record='.$id_record."'; }\">
    <i class=\"fa fa-refresh\"></i> ".tr('Rinnova').'...
</button>';
}
