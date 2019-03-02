<?php

echo '
<div class="dropdown">
	<button class="btn btn-info dropdown-toggle '.(!in_array($record['stato'], ['Fatturato', 'Evaso']) ? '' : 'disabled').'" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
		<i class="fa fa-magic"></i>&nbsp;'.tr('Crea').'...
		<span class="caret"></span>
	</button>
	<ul class="dropdown-menu dropdown-menu-right">';

    echo '
        <li>
            <a data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&documento=ddt" data-toggle="modal" data-title="'.tr('Crea ddt').'" class="'.(in_array($record['stato'], ['Bozza', 'Parzialmente evaso']) ? '' : 'disabled').'"><i class="fa fa-file-o"></i>&nbsp;'.tr('ddt').'
            </a>
        </li>';

    echo '
        <li>
            <a data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&documento=fattura" data-toggle="modal" data-title="'.tr('Crea fattura').'" class="'.(in_array($record['stato'], ['Bozza', 'Parzialmente fatturato']) ? '' : 'disabled').'"><i class="fa fa-file"></i>&nbsp;'.tr('fattura').'
            </a>
        </li>';

    echo '
    </ul>
</div>';
