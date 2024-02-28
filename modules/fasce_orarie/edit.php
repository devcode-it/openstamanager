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

use Modules\Pagamenti\Pagamento;

// $block_edit = $record['is_predefined'];

?>

<form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

	<!-- DATI -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Dati'); ?></h3>
		</div>

		<div class="panel-body">
			<div class="row">
				<div class="col-md-6">
					{[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "nome", "required": 1, "value": "$name$" ]}
				</div>

                <div class="col-md-6">
					{[ "type": "select", "multiple":"1", "label": "<?php echo tr('Giorni'); ?>", "name": "giorni[]", "required": 0, "value": "$giorni$", "values": "list=\"1\":\"<?php echo tr('Lunedì'); ?>\", \"2\":\"<?php echo tr('Martedì'); ?>\", \"3\":\"<?php echo tr('Mercoledì'); ?>\", \"4\":\"<?php echo tr('Giovedì'); ?>\", \"5\":\"<?php echo tr('Venerdì'); ?>\", \"6\":\"<?php echo tr('Sabato'); ?>\", \"7\":\"<?php echo tr('Domenica'); ?>\"" ]}
				</div>
			</div>

            <div class="row">
				<div class="col-md-3">
					{[ "type": "time", "label": "<?php echo tr('Ora inizio'); ?>", "name": "ora_inizio", "required": 1, "value": "$ora_inizio$"  ]}
				</div>

                <div class="col-md-3">
					{[ "type": "time", "label": "<?php echo tr('Ora fine'); ?>", "name": "ora_fine", "required": 1, "value": "$ora_fine$"  ]}
				</div>

                <div class="col-md-3">
					{[ "type": "checkbox", "label": "<?php echo tr('Includi festività'); ?>", "name": "include_bank_holidays", "required": 0, "value": "$include_bank_holidays$" ]}
				</div>

                <div class="col-md-3">
					{[ "type": "checkbox", "label": "<?php echo tr('Predefinita'); ?>", "name": "is_predefined", "required": 0, "value": "$is_predefined$" ]}
				</div>
            </div>
		</div>
	</div>

    <!-- Date aggiuntive -->
    <div class="panel panel-primary hide">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Date aggiuntive'); ?></h3>
		</div>

		<div class="panel-body">
			<div id="elenco-date">

            <?php

                $results = (new Pagamento())->getByName(prepare($record['descrizione']))->id_record;
$numero_data = 1;
foreach ($results as $result) {
}

?>
            
            </div>
            <div class="pull-right">
				<button type="button" class="btn btn-info" onclick="aggiungiData()">
                    <i class="fa fa-plus"></i> <?php echo tr('Aggiungi'); ?>
                </button>

				<button type="submit" class="btn btn-success">
                    <i class="fa fa-check"></i> <?php echo tr('Salva'); ?>
                </button>
			</div>
		</div>
	</div>

</form>


<?php
echo '
<form class="hide" id="template">
    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">'.tr('Nuova data').'</h3>
        </div>
        <div class="box-body">
            <input type="hidden" value="" name="id[-id-]">

            <div class="row">
                <div class="col-md-6">
                    {[ "type": "text", "label": "'.tr('Nome').'",  "name": "nome[-id-]"]}
                </div>

                <div class="col-md-3">
                    {[ "type": "date", "label": "'.tr('Data').'", "name": "data[-id-]"  ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "checkbox", "label": "'.tr('Data ricorrente').'", "name": "data_ricorrente[-id-]", "value": "" ]}
                </div>
            </div>

        </div>
    </div>
</form>';

?>
<script>
var indice_data = "<?php echo $numero_data; ?>";
function aggiungiData() {
    aggiungiContenuto("#elenco-date", "#template", {"-id-": indice_data});
    indice_data++;
}

</script>

<?php

$elementi = $dbo->fetchArray('SELECT `in_tipiintervento`.`codice`, `in_tipiintervento`.`descrizione`, `in_tipiintervento`.`idtipointervento` FROM `in_tipiintervento` LEFT JOIN `in_fasceorarie_tipiintervento` ON `in_tipiintervento`.`idtipointervento`=`in_fasceorarie_tipiintervento`.`idtipointervento` WHERE `in_fasceorarie_tipiintervento`.`idfasciaoraria`='.prepare($id_record));

if (!empty($elementi)) {
    echo '
<div class="box box-warning collapsable collapsed-box">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-warning"></i> '.tr('Tipi interventi collegati: _NUM_', [
            '_NUM_' => count($elementi),
        ]).'</h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i></button>
        </div>
    </div>
    <div class="box-body">
        <ul>';

    foreach ($elementi as $elemento) {
        $descrizione = tr('_REF_  (_TIPO_INTERVENTO_)', [
        '_REF_' => $elemento['descrizione'],
        '_TIPO_INTERVENTO_' => $elemento['codice'],
    ]);

        $modulo = 'Tipi di intervento';
        $id = $elemento['idtipointervento'];

        echo '
            <li>'.Modules::link($modulo, $id, $descrizione).'</li>';
    }

    echo '
        </ul>
    </div>
</div>';
}

?>

<a class="btn btn-danger ask <?php echo intval($record['can_delete']) == 0 ? 'disabled' : ''; ?>" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>
