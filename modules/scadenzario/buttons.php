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

if (!empty($record['iddocumento'])) {
    $fattura_allegata = $dbo->selectOne('zz_files', 'id', ['id_module' => $id_module, 'id_record' => $id_record, 'original' => 'Fattura di vendita.pdf'])['id'];
    echo '
    <button type="button" class="btn btn-warning ask btn-warning '.(empty($fattura_allegata
    ) ? '' : 'disabled').'" id="allega-fattura" data-msg="'.tr('Allegare la stampa della fattura?').'"  data-op="allega_fattura" data-iddocumento="'.$record['iddocumento'].'" data-button="'.tr('Allega').'" data-class="btn btn-lg btn-warning" data-backto="record-edit" >
        <i class="fa fa-paperclip"></i> '.tr('Allega fattura').'
    </button>';
}
