<?php

include_once __DIR__.'/../../core.php';

echo'
<button type="button" class="btn btn-primary" onclick="if( confirm(\'Duplicare questo preventivo?\') ){ $(\'#form-copy\').submit(); }"> <i class="fa fa-copy"></i> '.tr('Duplica preventivo').'</button>';

if (!in_array($record['stato'], ['Bozza', 'Rifiutato', 'In attesa di conferma'])) {
    echo '
	<div class="dropdown">
	<button class="btn btn-info dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
		<i class="fa fa-magic"></i>&nbsp;'.tr('Crea').'...
		<span class="caret"></span>
	</button>
	<ul class="dropdown-menu dropdown-menu-right">

			<li>
				<a data-href="'.$rootdir.'/modules/ordini/crea_documento.php?id_module='.$id_module.'&id_record='.$id_record.'&documento=ordine" data-toggle="modal" data-title="'.tr('Crea ordine').'"><i class="fa fa-file-o"></i>&nbsp;'.tr('Ordine').'
				</a>
			</li>

		</ul>
	</div>';
} else {
    echo '
	<button type="button" class="btn btn-warning" onclick="if(confirm(\'Vuoi creare un nuova revisione?\')){$(\'#form_crearevisione\').submit();}"><i class="fa fa-edit"></i> '.tr('Crea nuova revisione...').'</button>';
}

//duplica preventivo
echo '
<form action="" method="post" id="form-copy">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="copy">
</form>';

//crea revisione
echo '
<form action="" method="post" id="form_crearevisione">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="add_revision">
	<input type="hidden" name="id_record" value="'.$id_record.'">
</form>';
