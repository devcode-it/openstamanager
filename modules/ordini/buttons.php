<?php

include_once __DIR__.'/../../core.php';

$stati_abilitati = ['Fatturato', 'Evaso', 'Bozza'];

echo '
<div class="btn-group tip" data-toggle="tooltip" title="'.tr("Per creare un documento deve essere inserita almeno una riga e lo stato dell'ordine deve essere tra: _STATE_LIST_", [
        '_STATE_LIST_' => implode(', ', $stati_abilitati),
    ]).'">
	<button class="btn btn-info dropdown-toggle '.(!in_array($record['stato'], ['Fatturato', 'Evaso', 'Bozza']) ? '' : 'disabled').'" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
		<i class="fa fa-magic"></i> '.tr('Crea').'
		<span class="caret"></span>
	</button>
	<ul class="dropdown-menu dropdown-menu-right">
	    <li>
            <a data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&documento=intervento" data-toggle="modal" data-title="'.tr('Crea attività').'">
                <i class="fa fa-wrench"></i> '.tr('Attività').'
            </a>
        </li>

        <li>
            <a data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&documento=ordine_fornitore" data-toggle="modal" data-title="'.tr('Crea ordine fornitore').'">
                <i class="fa fa-file-o"></i> '.tr('Ordine fornitore').'
            </a>
        </li>

        <li>
            <a data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&documento=ddt" data-toggle="modal" data-title="'.tr('Crea ddt').'">
                <i class="fa fa-truck"></i> '.tr('Ddt').'
            </a>
        </li>

        <li>
            <a data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&documento=fattura" data-toggle="modal" data-title="'.tr('Crea fattura').'">
                <i class="fa fa-file"></i> '.tr('Fattura').'
            </a>
        </li>
    </ul>
</div>';
