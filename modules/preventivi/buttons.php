<?php

include_once __DIR__.'/../../core.php';

echo'
<button type="button" class="btn btn-primary" onclick="if( confirm(\'Duplicare questo preventivo?\') ){ $(\'#copia-preventivo\').submit(); }"> <i class="fa fa-copy"></i> '.tr('Duplica preventivo').'</button>';

$stati_abilitati = $dbo->fetchOne('SELECT GROUP_CONCAT(`descrizione` SEPARATOR ", ") AS stati_abilitati FROM `co_statipreventivi` WHERE `is_revisionabile` = 1 ')['stati_abilitati'];

// Crea revisione
echo '
<button type="button" class="btn btn-warning '.($record['is_revisionabile'] ? '' : 'disabled tip').'" onclick="if(confirm(\'Vuoi creare un nuova revisione?\')){$(\'#crea-revisione\').submit();}" '.($record['is_revisionabile'] ? '' : 'disabled').' title="'.($record['is_revisionabile'] ? '' : tr('Per creare una nuova revisione lo stato del preventivo deve essere tra: ').$stati_abilitati).'" ><i class="fa fa-edit"></i> '.tr('Crea nuova revisione...').'</button>';

$rs_documento = $dbo->fetchArray('SELECT * FROM co_righe_preventivi WHERE idpreventivo='.prepare($id_record));

$disabled = ($record['is_fatturabile'] || $record['is_completato']) && !empty($rs_documento);

$stati_abilitati = $dbo->fetchOne('SELECT GROUP_CONCAT(`descrizione` SEPARATOR ", ") AS stati_abilitati FROM `co_statipreventivi` WHERE `is_fatturabile` = 1 OR `is_completato` = 1 ')['stati_abilitati'];

// Creazione altri documenti
echo '
<div style="margin-left:4px;" class="dropdown pull-right">
    <button class="btn btn-info dropdown-toggle '.($disabled ? '' : 'disabled tip').'" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true" '.($disabled ? '' : 'disabled').'  title="'.($disabled ? '' : tr('Per creare un documento deve essere inserita almeno una riga e lo stato del preventivo deve essere tra: ').$stati_abilitati).'" >
        <i class="fa fa-magic"></i>&nbsp;'.tr('Crea').'...
        <span class="caret"></span>
    </button>
    <ul class="dropdown-menu dropdown-menu-right">
		<li>
		    <a class="'.($disabled ? '' : 'disabled').'" data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&documento=ddt" data-toggle="modal" data-title="'.tr('Crea ordine cliente').'">
                <i class="fa fa-truck"></i>&nbsp;'.tr('DDT in uscita').'
            </a>
		</li>

		<li>
		    <a class="'.($disabled ? '' : 'disabled').'" data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&documento=ordine" data-toggle="modal" data-title="'.tr('Crea ordine cliente').'">
                <i class="fa fa-file-o"></i>&nbsp;'.tr('Ordine cliente').'
            </a>
		</li>

		<li>
            <a class="'.($disabled ? '' : 'disabled').'" data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&documento=fattura" data-toggle="modal" data-title="'.tr('Crea fattura').'">
                <i class="fa fa-file"></i>&nbsp;'.tr('Fattura').'
            </a>
        </li>

        <li>
		    <a class="'.($disabled ? '' : 'disabled').'" data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&documento=contratto" data-toggle="modal" data-title="'.tr('Crea contratto').'">
                <i class="fa fa-file-o"></i>&nbsp;'.tr('Contratto').'
            </a>
		</li>
	</ul>
</div>';

// Duplica preventivo
echo '
<form action="" method="post" id="copia-preventivo">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="copy">
</form>';

// Crea revisione
echo '
<form action="" method="post" id="crea-revisione">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="add_revision">
	<input type="hidden" name="id_record" value="'.$id_record.'">
</form>';
