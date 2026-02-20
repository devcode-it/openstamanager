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

use Modules\Contratti\Contratto;

$id_documento = get('id_documento');
if (!empty($id_documento)) {
    $documento = Contratto::find($id_documento);

    // Calcolo delle ore residue per ogni tipo di attività
    $tipi_attivita = $dbo->fetchArray('SELECT
        `co_contratti_tipiintervento`.`idtipointervento`,
        `in_tipiintervento_lang`.`title` AS descrizione,
        COALESCE(SUM(`co_righe_contratti`.`qta`), 0) AS ore_totali,
        COALESCE(SUM(`in_interventi_tecnici`.`ore`), 0) AS ore_utilizzate
    FROM `co_contratti_tipiintervento`
    INNER JOIN `in_tipiintervento` ON `co_contratti_tipiintervento`.`idtipointervento` = `in_tipiintervento`.`id`
    LEFT JOIN `in_tipiintervento_lang` ON (`in_tipiintervento`.`id` = `in_tipiintervento_lang`.`id_record` AND `in_tipiintervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
    LEFT JOIN `co_righe_contratti` ON `co_righe_contratti`.`idcontratto` = `co_contratti_tipiintervento`.`idcontratto`
        AND `co_righe_contratti`.`id_tipointervento` = `co_contratti_tipiintervento`.`idtipointervento`
    LEFT JOIN `in_interventi` ON `in_interventi`.`id_contratto` = `co_contratti_tipiintervento`.`idcontratto`
    LEFT JOIN `in_interventi_tecnici` ON `in_interventi_tecnici`.`idintervento` = `in_interventi`.`id`
        AND `in_interventi_tecnici`.`idtipointervento` = `co_contratti_tipiintervento`.`idtipointervento`
    WHERE `co_contratti_tipiintervento`.`idcontratto` = '.prepare($id_documento).'
    GROUP BY `co_contratti_tipiintervento`.`idtipointervento`, `in_tipiintervento_lang`.`title`');

    $options = [
        'op' => 'add_documento',
        'type' => 'contratto',
        'button' => tr('Rinnova'),
        'documento' => $documento,
        'documento_finale' => $documento,
        'tipo_documento_finale' => Contratto::class,
        'dir' => 'entrata',
        'is_evasione' => true,
        'tipi_attivita' => $tipi_attivita,
        'is_renewal' => true,
    ];

    echo App::load('importa.php', [], $options, true);

    return;
}

// Se non è stato passato id_documento, mostro la select per scegliere il contratto precedente
$id_anagrafica = isset($record['idanagrafica']) ? $record['idanagrafica'] : null;
$id_record_current = isset($id_record) ? $id_record : null;

echo '
<div class="row">
    <div class="col-md-12">
        {[ "type": "select", "label": "'.tr('Contratto precedente').'", "name": "id_documento", "ajax-source": "contratti", "select-options": {"idanagrafica": '.$id_anagrafica.', "id_record": "'.$id_record_current.'"} ]}
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
        content.load("'.$structure->fileurl($file).'?id_module='.$id_module.'&id_record='.$id_record_current.'&id_documento=" + id, function() {
            loader.hide();
        });
    });
</script>';
