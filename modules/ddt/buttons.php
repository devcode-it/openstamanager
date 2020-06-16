<?php

include_once __DIR__.'/../../core.php';
$stati_fatturabili = ['Bozza', 'Fatturato'];

echo '
<button '.(!in_array($record['stato'], $stati_fatturabili) ? '' : 'disabled').' class="btn btn-info '.(!in_array($record['stato'], $stati_fatturabili) ? '' : 'disabled tip').'" title="'.((!in_array($record['stato'], $stati_fatturabili)) ? '' : tr('Il ddt è fatturabile solo se non si trova nello stato di: ').implode(', ', $stati_fatturabili)).'" data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&documento=fattura" data-toggle="modal" data-title="'.tr('Crea fattura').(($dir=='entrata') ? ' di vendita' : ' di acquisto').'">
    <i class="fa fa-magic"></i> '.tr('Crea fattura').(($dir=='entrata') ? ' di vendita' : ' di acquisto').'
</button>';
