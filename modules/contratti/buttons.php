<?php

include_once __DIR__.'/../../core.php';

$rs_documento = $dbo->fetchArray('SELECT * FROM co_righe_contratti WHERE idcontratto='.prepare($id_record));
/* permetto di fatturare il contratto solo se contiene righe e si trova in uno stato fatturabile */
echo "
<button type=\"button\" class=\"btn btn-info\" ".((($record['fatturabile']) AND (sizeof($rs_documento)>0)) ? '' : 'disabled')." onclick=\"if( confirm('Creare una fattura per questo contratto?') ){fattura_da_contratto();}\">
<i class=\"fa fa-magic\"></i> ".tr('Crea fattura').'...
</button>';


if ($record['rinnovabile']) {
    $rinnova = !empty($record['data_accettazione']) && !empty($record['data_conclusione']) && $record['data_accettazione'] != '0000-00-00' && $record['data_conclusione'] != '0000-00-00' &&  $record['pianificabile'];
	$stati_pianificabili = $dbo->fetchOne('SELECT GROUP_CONCAT(`descrizione` SEPARATOR ", ") AS stati_pianificabili FROM `co_staticontratti` WHERE `pianificabile` = 1')['stati_pianificabili'];
    echo '
<div class="tip" data-toggle="tooltip" title="'.tr('Il contratto è rinnovabile se sono definite le date di accettazione e conclusione e si trova in uno stato di questi stati: '.$stati_pianificabili).'" style="display:inline;">
    <button type="button" class="btn btn-warning ask '.($rinnova ? '' : 'disabled').'" data-backto="record-edit" data-op="renew" data-msg="'.tr('Rinnovare questo contratto?').'" data-button="Rinnova" data-class="btn btn-lg btn-warning" '.($rinnova ? '' : 'disabled').'>
        <i class="fa fa-refresh"></i> '.tr('Rinnova').'...
    </button>
</div>';
}
