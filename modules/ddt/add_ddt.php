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

use Modules\DDT\DDT;

$documento_finale = DDT::find($id_record);
$dir = $documento_finale->direzione;

$id_documento = get('id_documento');
if (!empty($id_documento)) {
    $documento = DDT::find($id_documento);

    $options = [
        'op' => 'add_documento',
        'button' => tr('Aggiungi'),
        'serials' => true,
        'documento' => $documento,
        'documento_finale' => $documento_finale,
        'tipo_documento_finale' => DDT::class,
        'superamento_soglia_qta' => setting('Permetti il superamento della soglia quantità dei documenti di origine'),
    ];

    echo App::load('importa.php', [], $options, true);

    return;
}

$id_anagrafica = $documento_finale->idanagrafica;

echo '
<div class="row">
    <div class="col-md-12">
        {[ "type": "select", "label": "'.tr('Ddt').'", "name": "id_documento", "values": "query=SELECT `dt_ddt`.`id`, CONCAT(IF(`numero_esterno` != \'\', `numero_esterno`, `numero`), \' del \', DATE_FORMAT(`data`, \'%d-%m-%Y\')) AS descrizione FROM `dt_ddt` INNER JOIN `dt_statiddt` ON `dt_ddt`.`idstatoddt` = `dt_statiddt`.`id` LEFT JOIN `dt_statiddt_lang` ON (`dt_statiddt`.`id` = `dt_statiddt_lang`.`id_record` AND `dt_statiddt_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') INNER JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt` = `dt_tipiddt`.`id`  INNER JOIN `dt_righe_ddt` ON `dt_ddt`.`id` = `dt_righe_ddt`.`idddt` WHERE `dt_statiddt_lang`.`title` IN(\'Evaso\', \'Parzialmente evaso\', \'Parzialmente fatturato\') AND `dt_tipiddt`.`dir`='.prepare($dir == 'entrata' ? 'uscita' : 'entrata').' AND (`dt_righe_ddt`.`qta` - `dt_righe_ddt`.`qta_evasa`) > 0 GROUP BY dt_ddt.id ORDER BY `data` DESC, `numero` DESC " ]}
    </div>
</div>

<div id="righe_documento">

</div>

<div class="alert alert-info" id="card-loading">
    <i class="fa fa-spinner fa-spin"></i> '.tr('Caricamento in corso').'...
</div>';

$file = basename(__FILE__);
echo '
<script>$(document).ready(init)</script>

<script>
    var content = $("#righe_documento");
    var loader = $("#card-loading");

    $(document).ready(function() {
        loader.hide();
    });

    $("#id_documento").on("change", function() {
        loader.show();

        var id = $(this).selectData() ? $(this).selectData().id  : "";

        content.html("");
        content.load("'.$structure->fileurl($file).'?id_module='.$id_module.'&id_record='.$id_record.'&id_documento=" + id, function() {
            loader.hide();
        });
    });
</script>';
