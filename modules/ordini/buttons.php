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

// Duplica ordine
echo '
<button type="button" class="btn btn-primary " onclick="duplicaOrdine()">
    <i class="fa fa-copy"></i> '.tr('Duplica ordine').'
</button>

<script>
function duplicaOrdine() {
    openModal("'.tr('Duplica ordine').'", "'.$module->fileurl('modals/duplicazione.php').'?id_module='.$id_module.'&id_record='.$id_record.'");
}
</script>';

$stati = $dbo->fetchArray('SELECT `title` FROM `or_statiordine` LEFT JOIN `or_statiordine_lang` ON (`or_statiordine`.`id`=`or_statiordine_lang`.`id_record` AND `or_statiordine_lang`.`id_lang`= '.prepare(Models\Locale::getDefault()->id).') WHERE `is_fatturabile` = 1');
foreach ($stati as $stato) {
    $stati_importabili[] = $stato['title'];
}

echo '
<div class="btn-group tip" data-widget="tooltip" title="'.tr("Per creare un documento deve essere inserita almeno una riga e lo stato dell'ordine deve essere tra: _STATE_LIST_", [
    '_STATE_LIST_' => implode(', ', $stati_importabili),
]).'">
	<button class="btn btn-info dropdown-toggle '.(in_array($record['stato'], $stati_importabili) ? '' : 'disabled').'" type="button" data-widget="dropdown" aria-haspopup="true" aria-expanded="true">
		<i class="fa fa-magic"></i> '.tr('Crea').'
		<span class="caret"></span>
	</button>
	<ul class="dropdown-menu dropdown-menu-right">

        <a class="dropdown-item" data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&documento=intervento" data-widget="modal" data-title="'.tr('Crea attività').'">
            <i class="fa fa-wrench"></i> '.tr('Attività').'
        </a>
        ';

if ($dir == 'entrata') {
    echo '

        <a class="dropdown-item" data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&documento=ordine_fornitore" data-widget="modal" data-title="'.tr('Crea ordine fornitore').'">
            <i class="fa fa-file-o"></i> '.tr('Ordine fornitore').'
        </a>
        ';
} 

echo '  

        <a class="dropdown-item" data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&documento=ddt" data-widget="modal" data-title="'.tr('Crea ddt').'">
            <i class="fa fa-truck"></i> '.tr('Ddt').'
        </a>

        <a class="dropdown-item" data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&documento=fattura" data-widget="modal" data-title="'.tr('Crea fattura').'">
            <i class="fa fa-file"></i> '.tr('Fattura').'
        </a>

    </ul>
</div>';
