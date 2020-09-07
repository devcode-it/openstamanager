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

echo '
<div class="tip" data-toggle="tooltip" title="'.tr("Il ddt è fatturabile solo se non si trova negli stati _STATE_LIST_ e la relativa causale è abilitata all'importazione in altri documenti", [
        '_STATE_LIST_' => 'Evaso, Parzialmente evaso, Parzialmente fatturato',
    ]).'">
    <button class="btn btn-info '.($ddt->isImportabile() ? '' : 'disabled').'" data-href="'.$structure->fileurl('crea_documento.php').'?id_module='.$id_module.'&id_record='.$id_record.'&documento=fattura" data-toggle="modal" data-title="'.tr('Crea fattura').(($dir == 'entrata') ? ' di vendita' : ' di acquisto').'">
        <i class="fa fa-magic"></i> '.tr('Crea fattura').(($dir == 'entrata') ? ' di vendita' : ' di acquisto').'
    </button>
</div>';
