<?php

include_once __DIR__.'/../../core.php';

if (!in_array($records[0]['stato'], ['Evaso', 'Fatturato', 'Parzialmente fatturato'])) {

echo '
	<div class="dropdown">
	<button class="btn btn-info dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
		<i class="fa fa-magic"></i>&nbsp;'.tr('Crea').'...
		<span class="caret"></span>
	</button>
	<ul class="dropdown-menu">
			
			<li>
				<a data-href="'.$rootdir.'/modules/fatture/crea_documento.php?id_module='.$id_module.'&id_record='.$id_record.'&documento=ddt" data-toggle="modal" data-title="'.tr('Crea ddt').'" data-target="#bs-popup"><i class="fa fa-file-o"></i>&nbsp;'.tr('ddt').'
				</a>
			</li>

			<li>
				<a data-href="'.$rootdir.'/modules/fatture/crea_documento.php?id_module='.$id_module.'&id_record='.$id_record.'&documento=fattura" data-toggle="modal" data-title="'.tr('Crea fattura').'" data-target="#bs-popup"><i class="fa fa-file"></i>&nbsp;'.tr('fattura').'
				</a>
			</li>

		</ul>
	</div>';

	
}