<?php

include_once __DIR__.'/../../core.php';

$rs_documento = $dbo->fetchArray('SELECT * FROM co_righe_contratti WHERE idcontratto='.prepare($id_record));

/* permetto di fatturare il contratto solo se contiene righe e si trova in uno stato fatturabile */
echo '
<button type="button" class="btn btn-info '.(($record['is_fatturabile'] && !empty($rs_documento)) ? '' : 'disabled').'" data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&documento=fattura" data-toggle="modal" data-title="'.tr('Crea fattura').'">
    <i class="fa fa-magic"></i> '.tr('Crea fattura').'
</button>';

if ($record['rinnovabile']) {
    $rinnova = !empty($record['data_accettazione']) && !empty($record['data_conclusione']) && $record['data_accettazione'] != '0000-00-00' && $record['data_conclusione'] != '0000-00-00' && $record['is_pianificabile'];

    $stati_pianificabili = $dbo->fetchOne('SELECT GROUP_CONCAT(`descrizione` SEPARATOR ", ") AS stati_pianificabili FROM `co_staticontratti` WHERE `is_pianificabile` = 1')['stati_pianificabili'];

    echo '
<div class="tip" data-toggle="tooltip" title="'.tr('Il contratto è rinnovabile se sono definite le date di accettazione e conclusione e si trova in uno stato di questi stati: '.$stati_pianificabili).'" style="display:inline;">
    <button type="button" class="btn btn-warning ask '.($rinnova ? '' : 'disabled').'" data-backto="record-edit" data-op="renew" data-msg="'.tr('Rinnovare questo contratto?').'" data-button="Rinnova" data-class="btn btn-lg btn-warning" '.($rinnova ? '' : 'disabled').'>
        <i class="fa fa-refresh"></i> '.tr('Rinnova').'...
    </button>
</div>';
}
