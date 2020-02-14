<?php

include_once __DIR__.'/../../core.php';

echo '
<div class="dropdown">
	<button class="btn btn-info dropdown-toggle '.(!in_array($record['stato'], ['Fatturato', 'Evaso', 'Bozza']) ? '' : 'disabled').'" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
		<i class="fa fa-magic"></i>&nbsp;'.tr('Crea').'...
		<span class="caret"></span>
	</button>
	<ul class="dropdown-menu dropdown-menu-right">';

echo '
        <li>
            <a data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&documento=ordine_fornitore" data-toggle="modal" data-title="'.tr('Crea ordine fornitore').'" class="'.(in_array($record['stato'], ['Accettato', 'Parzialmente evaso']) ? '' : 'disabled').'"><i class="fa fa-file-o"></i>&nbsp;'.tr('Ordine fornitore').'
            </a>
        </li>';

    echo '
        <li>
            <a data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&documento=ddt" data-toggle="modal" data-title="'.tr('Crea ddt').'" class="'.(in_array($record['stato'], ['Accettato', 'Parzialmente evaso']) ? '' : 'disabled').'"><i class="fa fa-truck"></i>&nbsp;'.tr('Ddt').'
            </a>
        </li>';

    echo '
        <li>
            <a data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&documento=fattura" data-toggle="modal" data-title="'.tr('Crea fattura').'" class="'.(in_array($record['stato'], ['Accettato', 'Parzialmente fatturato']) ? '' : 'disabled').'"><i class="fa fa-file"></i>&nbsp;'.tr('Fattura').'
            </a>
        </li>';

    echo '
    </ul>
</div>';
