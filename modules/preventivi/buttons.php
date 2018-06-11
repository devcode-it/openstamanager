<?php

include_once __DIR__.'/../../core.php';

if (!in_array($records[0]['stato'], ['Bozza','Rifiutato','In attesa di conferma'])) {

echo '
	<div class="dropdown">
	<button class="btn btn-info dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
		<i class="fa fa-magic"></i>&nbsp;'.tr('Crea').'...
		<span class="caret"></span>
	</button>
	<ul class="dropdown-menu">
			
			<li>
				<a data-href="'.$rootdir.'/modules/ordini/crea_documento.php?id_module='.$id_module.'&id_record='.$id_record.'&documento=ordine" data-toggle="modal" data-title="'.tr('Crea ordine').'" data-target="#bs-popup"><i class="fa fa-file-o"></i>&nbsp;'.tr('Ordine').'
				</a>
			</li>

			

		</ul>
	</div>';

	
}