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

if (!$is_anagrafica_deleted) {
    $is_fatturabile = $record['is_fatturabile'];
    $stati_fatturabili = $dbo->fetchOne('SELECT GROUP_CONCAT(`title` SEPARATOR ", ") AS stati_abilitati FROM `co_staticontratti` LEFT JOIN `co_staticontratti_lang` ON (`co_staticontratti`.`id` = `co_staticontratti_lang`.`id_record` AND `co_staticontratti_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `is_fatturabile` = 1')['stati_abilitati'];

    // Permetto di fatturare il contratto solo se contiene righe e si trova in uno stato fatturabile
    echo '
    <div class="tip" data-widget="tooltip" title="'.tr('Per creare un documento lo stato del contratto deve essere tra: _STATE_LIST_', [
        '_STATE_LIST_' => $stati_fatturabili,
    ]).'">
        <button type="button" class="btn btn-info '.($is_fatturabile ? '' : 'disabled').' " data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&documento=fattura" data-widget="modal" data-title="'.tr('Crea fattura').'">
            <i class="fa fa-magic"></i> '.tr('Crea fattura').'
        </button>
    </div>';

    $rinnova = !empty($record['data_accettazione']) && !empty($record['data_conclusione']) && $record['data_accettazione'] != '0000-00-00' && $record['data_conclusione'] != '0000-00-00' && $record['is_completato'] && $record['rinnovabile'];

    $stati_completati = $dbo->fetchOne('SELECT GROUP_CONCAT(`title` SEPARATOR ", ") AS stati_completati FROM `co_staticontratti` LEFT JOIN `co_staticontratti_lang` ON (`co_staticontratti`.`id` = `co_staticontratti_lang`.`id_record` AND `co_staticontratti_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `is_completato` = 1')['stati_completati'];

    echo '
    <div class="tip" data-widget="tooltip" title="'.tr('Il contratto è rinnovabile se sono definite le date di accettazione e conclusione e si trova in uno di questi stati: _STATE_LIST_', [
        '_STATE_LIST_' => $stati_completati,
    ]).'" id="rinnova">
        <button type="button" class="btn btn-warning ask '.($rinnova ? '' : 'disabled').'" data-backto="record-edit" data-op="renew" data-msg="'.tr('Rinnovare questo contratto?').'" data-button="'.tr('Rinnova').'" data-class="btn btn-lg btn-warning">
            <i class="fa fa-refresh"></i> '.tr('Rinnova').'...
        </button>
    </div>';
}

// Duplica contratto
echo '
<button type="button" class="btn btn-primary ask" data-title="'.tr('Duplicare questo contratto?').'" data-msg="'.tr('Clicca su tasto duplica per procedere.').'" data-op="copy" data-button="'.tr('Duplica').'" data-class="btn btn-lg btn-primary" data-backto="record-edit">
    <i class="fa fa-copy"></i> '.tr('Duplica contratto').'
</button>';
