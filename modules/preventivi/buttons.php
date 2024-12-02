<?php

/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
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
use Modules\Preventivi\Stato;

$stati_abilitati = Stato::where('is_revisionabile', '=', '1')->get();
$stati = [];

foreach ($stati_abilitati as $stato) {
    $stati[] = $stato->getTranslation('title');
}

// Crea revisione
echo '
<div class="tip" data-widget="tooltip" title="'.tr('Per creare una nuova revisione lo stato del preventivo deve essere tra: _STATE_LIST_', [
    '_STATE_LIST_' => implode(', ', $stati),
]).'">
    <button type="button" class="btn btn-warning '.($record['is_revisionabile'] ? '' : 'disabled').'" onclick="openModal(\''.tr('Crea revisione').'\', \''.$module->fileurl('crea_revisione.php').'?id_module='.$id_module.'&id_record='.$id_record.'\')">
        <i class="fa fa-edit"></i> '.tr('Crea nuova revisione...').'
    </button>
</div>';

$rs_documento = $dbo->fetchArray('SELECT * FROM co_righe_preventivi WHERE idpreventivo='.prepare($id_record));

$disabled = ($record['is_fatturabile'] || $record['is_completato']) && !empty($rs_documento);

$stati_abilitati = Stato::where('is_fatturabile', '=', '1')->orWhere('is_completato', '=', '1')->get();
$stati = [];

foreach ($stati_abilitati as $stato) {
    $stati[] = $stato->getTranslation('title');
}

// Creazione altri documenti
echo '
<div class="btn-group tip" data-widget="tooltip" title="'.tr('Per creare un documento deve essere inserita almeno una riga e lo stato del preventivo deve essere tra: _STATE_LIST_', [
    '_STATE_LIST_' => implode(', ', $stati),
]).'">
    <button class="btn btn-info dropdown-toggle '.($disabled ? '' : 'disabled').'" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
        <i class="fa fa-magic"></i>&nbsp;'.tr('Crea').'...
        <span class="caret"></span>
    </button>
    <div class="dropdown-menu dropdown-menu-right">
        <a class="'.($disabled ? '' : 'disabled').' dropdown-item" data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&documento=contratto" data-widget="modal" data-title="'.tr('Crea contratto').'">
            <i class="fa fa-file-o"></i> '.tr('Contratto').'
        </a>

        <a class="'.($disabled ? '' : 'disabled').' dropdown-item" data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&documento=ordine_cliente" data-widget="modal" data-title="'.tr('Crea ordine cliente').'">
            <i class="fa fa-file-o"></i> '.tr('Ordine cliente').'
        </a>

        <a class="'.($disabled ? '' : 'disabled').' dropdown-item" data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&documento=ordine_fornitore" data-widget="modal" data-title="'.tr('Crea ordine fornitore').'">
            <i class="fa fa-file-o"></i> '.tr('Ordine fornitore').'
        </a>

        <a class="'.($disabled ? '' : 'disabled').' dropdown-item" data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&documento=intervento" data-widget="modal" data-title="'.tr('Crea attività').'">
            <i class="fa fa-file-o"></i> '.tr('Attività').'
        </a>

        <a class="'.($disabled ? '' : 'disabled').' dropdown-item" data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&documento=ddt" data-widget="modal" data-title="'.tr('Crea ordine cliente').'">
            <i class="fa fa-truck"></i> '.tr('DDT in uscita').'
        </a>

        <a class="'.($disabled ? '' : 'disabled').' dropdown-item" data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&documento=fattura" data-widget="modal" data-title="'.tr('Crea fattura').'">
            <i class="fa fa-file"></i> '.tr('Fattura').'
        </a>
	</div>
</div>';

// Duplica preventivo
echo '
<button type="button" class="btn ask btn-primary" data-title="'.tr('Duplicare questo preventivo?').'" data-msg="'.tr('Clicca su tasto duplica per procedere.').'" data-op="copy" data-button="'.tr('Duplica').'" data-class="btn btn-lg btn-primary" data-backto="record-edit" >
    <i class="fa fa-copy"></i> '.tr('Duplica preventivo').'
</button>';
