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
use Modules\Fatture\Tipo;
use Modules\Fatture\Fattura;

$fattura = Fattura::find($id_record);

$id_module_fatture_vendita = Module::where('name', 'Fatture di vendita')->first()->id;

echo '
<form action="" method="post" id="crea-autofattura">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="autofattura">

    <div class="row">
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Tipo documento').'", "name": "idtipodocumento_autofattura", "required": 1, "values": "query=SELECT `co_tipidocumento`.`id`, CONCAT(`co_tipidocumento`.`codice_tipo_documento_fe`, \" - \", `co_tipidocumento_lang`.`title`) AS descrizione, `co_tipidocumento`.`id_segment`, `zz_segments_lang`.`title` as name_segment FROM `co_tipidocumento` LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento`.`id`=`co_tipidocumento_lang`.`id_record` AND `co_tipidocumento_lang`.`id_lang`= '.prepare(Models\Locale::getDefault()->id).') INNER JOIN `fe_tipi_documento` ON `co_tipidocumento`.`codice_tipo_documento_fe` = `fe_tipi_documento`.`codice`  INNER JOIN `zz_segments` ON `zz_segments`.`id` = `co_tipidocumento`.`id_segment` LEFT JOIN `zz_segments_lang` ON (`zz_segments`.`id` = `zz_segments_lang`.`id_record` AND `zz_segments_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `dir`=\"entrata\" AND `fe_tipi_documento`.`is_autofattura` = 1 ORDER BY `fe_tipi_documento`.`codice`", "value": "'.$idtipodocumento.'" ]}
        </div>
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment_autofattura", "required": 1, "ajax-source": "segmenti", "select-options": '.json_encode(['id_module' => $id_module_fatture_vendita, 'is_sezionale' => 1]).', "value": "'.Tipo::where('id', $idtipodocumento)->where('dir', 'entrata')->first()->id_segment.'" ]}
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

<script>
$(document).ready(function () {
    init();

    input("idtipodocumento_autofattura").change(function () {
            $("#id_segment_autofattura").selectSetNew($(this).selectData().id_segment, $(this).selectData().name_segment);
        });
    });
</script>';
