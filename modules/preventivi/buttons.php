<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

include_once __DIR__.'/../../core.php';

echo'
<button type="button" class="btn btn-primary" onclick="if( confirm(\'Duplicare questo preventivo?\') ){ $(\'#copia-preventivo\').submit(); }">
    <i class="fa fa-copy"></i> '.tr('Duplica preventivo').'
</button>';

$stati_abilitati = $dbo->fetchOne('SELECT GROUP_CONCAT(`descrizione` SEPARATOR ", ") AS stati_abilitati FROM `co_statipreventivi` WHERE `is_revisionabile` = 1 ')['stati_abilitati'];

// Crea revisione
echo '
<div class="tip" data-toggle="tooltip" title="'.tr('Per creare una nuova revisione lo stato del preventivo deve essere tra: _STATE_LIST_', [
        '_STATE_LIST_' => $stati_abilitati,
    ]).'">
    <button type="button" class="btn btn-warning '.($record['is_revisionabile'] ? '' : 'disabled').'" onclick="openModal(\''.tr('Crea revisione').'\', \''.$module->fileurl('crea_revisione.php').'?id_module='.$id_module.'&id_record='.$id_record.'\')">
        <i class="fa fa-edit"></i> '.tr('Crea nuova revisione...').'
    </button>
</div>';

$rs_documento = $dbo->fetchArray('SELECT * FROM co_righe_preventivi WHERE idpreventivo='.prepare($id_record));

$disabled = ($record['is_fatturabile'] || $record['is_completato']) && !empty($rs_documento);

$stati_abilitati = $dbo->fetchOne('SELECT GROUP_CONCAT(`descrizione` SEPARATOR ", ") AS stati_abilitati FROM `co_statipreventivi` WHERE `is_fatturabile` = 1 OR `is_completato` = 1 ')['stati_abilitati'];

// Creazione altri documenti
echo '
<div class="btn-group tip" data-toggle="tooltip" title="'.tr('Per creare un documento deve essere inserita almeno una riga e lo stato del preventivo deve essere tra: _STATE_LIST_', [
        '_STATE_LIST_' => $stati_abilitati,
    ]).'">
    <button class="btn btn-info dropdown-toggle '.($disabled ? '' : 'disabled').'" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
        <i class="fa fa-magic"></i>&nbsp;'.tr('Crea').'...
        <span class="caret"></span>
    </button>
    <ul class="dropdown-menu dropdown-menu-right">
        <li>
		    <a class="'.($disabled ? '' : 'disabled').'" data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&documento=contratto" data-toggle="modal" data-title="'.tr('Crea contratto').'">
                <i class="fa fa-file-o"></i> '.tr('Contratto').'
            </a>
		</li>

		<li>
		    <a class="'.($disabled ? '' : 'disabled').'" data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&documento=ordine_cliente" data-toggle="modal" data-title="'.tr('Crea ordine cliente').'">
                <i class="fa fa-file-o"></i> '.tr('Ordine cliente').'
            </a>
        </li>
        
        <li>
            <a class="'.($disabled ? '' : 'disabled').'" data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&documento=ordine_fornitore" data-toggle="modal" data-title="'.tr('Crea ordine fornitore').'">
                <i class="fa fa-file-o"></i> '.tr('Ordine fornitore').'
            </a>
        </li>

		<li>
		    <a class="'.($disabled ? '' : 'disabled').'" data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&documento=ddt" data-toggle="modal" data-title="'.tr('Crea ordine cliente').'">
                <i class="fa fa-truck"></i> '.tr('DDT in uscita').'
            </a>
		</li>

		<li>
            <a class="'.($disabled ? '' : 'disabled').'" data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&documento=fattura" data-toggle="modal" data-title="'.tr('Crea fattura').'">
                <i class="fa fa-file"></i> '.tr('Fattura').'
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
