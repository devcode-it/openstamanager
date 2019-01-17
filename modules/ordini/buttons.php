<?php

include_once __DIR__.'/../../core.php';

if (!in_array($record['stato'], ['Fatturato'])) {
    echo '
	<div class="dropdown">
	<button class="btn btn-info dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
		<i class="fa fa-magic"></i>&nbsp;'.tr('Crea').'...
		<span class="caret"></span>
	</button>
	<ul class="dropdown-menu dropdown-menu-right">';

    if (in_array($record['stato'], ['Bozza', 'Parzialmente evaso'])) {
        echo '
			<li>
				<a data-href="'.$rootdir.'/modules/fatture/crea_documento.php?id_module='.$id_module.'&id_record='.$id_record.'&documento=ddt" data-toggle="modal" data-title="'.tr('Crea ddt').'"><i class="fa fa-file-o"></i>&nbsp;'.tr('ddt').'
				</a>
			</li>';
    }

    if (in_array($record['stato'], ['Bozza', 'Evaso', 'Parzialmente evaso', 'Parzialmente fatturato'])) {
        echo '
			<li>
				<a data-href="'.$rootdir.'/modules/fatture/crea_documento.php?id_module='.$id_module.'&id_record='.$id_record.'&documento=fattura" data-toggle="modal" data-title="'.tr('Crea fattura').'"><i class="fa fa-file"></i>&nbsp;'.tr('fattura').'
				</a>
			</li>';
    }

    echo '
		</ul>
	</div>';
}
