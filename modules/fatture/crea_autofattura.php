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
use Models\Module;
use Modules\Segmenti\Segmento;

$id_module_fatture_vendita = Module::where('name', 'Fatture di vendita')->first()->id;
$id_segment = Segmento::where('name', 'Autofatture')->where('id_module', $id_module_fatture_vendita)->first()->id;

echo '
<form action="" method="post" id="crea-autofattura">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="autofattura">

    <div class="row">
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Tipo documento').'", "name": "idtipodocumento", "required": 1, "values": "query=SELECT `co_tipidocumento`.`id`, CONCAT_WS(\" - \",`codice_tipo_documento_fe`, `title`) AS descrizione FROM `co_tipidocumento` LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento`.`id`=`co_tipidocumento_lang`.`id_record` AND `co_tipidocumento_lang`.`id_lang`= '.prepare(Models\Locale::getDefault()->id).') WHERE `dir`=\"entrata\" AND `codice_tipo_documento_fe` IN(\"TD16\", \"TD17\", \"TD18\", \"TD19\", \"TD20\", \"TD21\", \"TD28\") ORDER BY `codice_tipo_documento_fe`" ]}
        </div>

        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "ajax-source": "segmenti", "select-options": '.json_encode(['id_module' => $id_module_fatture_vendita, 'is_sezionale' => 1]).', "value": "'.$id_segment.'" ]}
        </div>
    </div>

    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary">
                <i class="fa fa-magic"></i> '.tr('Crea autofattura').'
            </button>
		</div>
	</div>
</form>

<script>$(document).ready(init)</script>';
