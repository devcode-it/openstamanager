<?php

include_once __DIR__.'/../../core.php';

if (!in_array($records[0]['stato'], ['Bozza', 'Fatturato'])) {
   
	echo '
	<a class="btn btn-info" data-href="'.$rootdir.'/modules/fatture/crea_documento.php?id_module='.$id_module.'&id_record='.$id_record.'&documento=fattura" data-toggle="modal" data-title="Crea fattura" data-target="#bs-popup">
	    <i class="fa fa-magic"></i>.'.tr('Crea fattura').'</i>
	</a>';

}
		