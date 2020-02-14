<?php

include_once __DIR__.'/../../core.php';

echo '
<a class="btn btn-info '.(!in_array($record['stato'], ['Bozza', 'Fatturato']) ? '' : 'disabled').'" data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&documento=fattura" data-toggle="modal" data-title="'.tr('Crea fattura').'">
    <i class="fa fa-magic"></i> '.tr('Crea fattura').'
</a>';
