<?php

include_once __DIR__.'/../../core.php';

$rs_documento = $dbo->fetchArray('SELECT * FROM co_righe_contratti WHERE idcontratto='.prepare($id_record));

$is_fatturabile = $record['is_fatturabile'] && !empty($rs_documento);

$stati_fatturabili = $dbo->fetchOne('SELECT GROUP_CONCAT(`descrizione` SEPARATOR ", ") AS stati_abilitati FROM `co_staticontratti` WHERE `is_fatturabile` = 1')['stati_abilitati'];

/* permetto di fatturare il contratto solo se contiene righe e si trova in uno stato fatturabile */
echo '
<div class="tip" data-toggle="tooltip" title="'.tr('Per creare un documento deve essere inserita almeno una riga e lo stato del contratto deve essere tra: _STATE_LIST_', [
        '_STATE_LIST_' => $stati_fatturabili,
    ]).'">
    <button type="button" class="btn btn-info '.($is_fatturabile ? '' : 'disabled').' " data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&documento=fattura" data-toggle="modal" data-title="'.tr('Crea fattura').'">
        <i class="fa fa-magic"></i> '.tr('Crea fattura').'
    </button>
</div>';

$rinnova = !empty($record['data_accettazione']) && !empty($record['data_conclusione']) && $record['data_accettazione'] != '0000-00-00' && $record['data_conclusione'] != '0000-00-00' && $record['is_pianificabile'] && $record['rinnovabile'];

$stati_pianificabili = $dbo->fetchOne('SELECT GROUP_CONCAT(`descrizione` SEPARATOR ", ") AS stati_pianificabili FROM `co_staticontratti` WHERE `is_pianificabile` = 1')['stati_pianificabili'];

echo '
<div class="tip" data-toggle="tooltip" title="'.tr('Il contratto è rinnovabile se sono definite le date di accettazione e conclusione e si trova in uno stato di questi stati: _STATE_LIST_', [
        '_STATE_LIST_' => $stati_pianificabili,
    ]).'">
    <button type="button" class="btn btn-warning ask '.($rinnova ? '' : 'disabled').'" data-backto="record-edit" data-op="renew" data-msg="'.tr('Rinnovare questo contratto?').'" data-button="'.tr('Rinnova').'" data-class="btn btn-lg btn-warning">
        <i class="fa fa-refresh"></i> '.tr('Rinnova').'...
    </button>
</div>';

// Duplica contratto
echo'
<button type="button" class="btn btn-primary" onclick="if( confirm(\''.tr('Duplicare questo contratto?').'\') ){ $(\'#copia-contratto\').submit(); }">
    <i class="fa fa-copy"></i> '.tr('Duplica contratto').'
</button>';

echo '
<form action="" method="post" id="copia-contratto">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="copy">
</form>';
