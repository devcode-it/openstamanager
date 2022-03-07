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

echo '
<form action="" method="post" role="form" id="form_sedi">
    <input type="hidden" name="id_plugin" value="'.$id_plugin.'">
    <input type="hidden" name="id_parent" value="'.$id_parent.'">
    <input type="hidden" name="id_record" value="'.$record['id'].'">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

	<div class="row">

		<div class="col-md-4">
			{[ "type": "text", "label": "'.tr('Numero protocollo').'", "name": "numero_protocollo", "required": 1, "value": "'.$record['numero_protocollo'].'", "help": "'.tr("Il numero di protocollo della dichiarazione d'intento, rilevabile dalla ricevuta telematica rilasciata dall'Agenzia delle entrate, è composto di due parti:<br><ul><li>la prima, formata da 17 cifre (es. 08060120341234567), che rappresenta il protocollo di ricezione;</li><li>la seconda, di 6 cifre (es. 000001), che rappresenta il progressivo e deve essere separata dalla prima dal segno '-' oppure dal segno '/'</li></ul>").'", "maxlength": "24", "charcounter": 1 ]}
		</div>

		<div class="col-md-3">
			{[ "type": "date", "label": "'.tr('Data protocollo').'", "name": "data_protocollo", "value": "'.$record['data_protocollo'].'", "required": 1 ]}
		</div>

		<div class="col-md-2">
			{[ "type": "text", "label": "'.tr('Progressivo int.').'", "name": "numero_progressivo", "required": 1, "value": "'.$record['numero_progressivo'].'", "help": "'.tr("Progressivo ad uso interno").'" ]}
		</div>	

		<div class="col-md-3">
			{[ "type": "date", "label": "'.tr('Data ricezione').'", "name": "data", "required": 1, "value": "'.$record['data'].'" ]}
		</div>

	</div>

	<div class="row">
		<div class="col-md-3">
			{[ "type": "date", "label": "'.tr('Data inizio').'", "name": "data_inizio", "required": 1, "value": "'.$record['data_inizio'].'" ]}
		</div>

        <div class="col-md-3">
			{[ "type": "date", "label": "'.tr('Data fine').'", "name": "data_fine", "required": 1, "value": "'.$record['data_fine'].'" ]}
		</div>

		<div class="col-md-3">
			{[ "type": "number", "label": "'.tr('Massimale').'", "name": "massimale", "required": 1, "icon-after": "'.currency().'", "value": "'.$record['massimale'].'" ]}
		</div>

		<div class="col-md-3">
			{[ "type": "date", "label": "'.tr('Data emissione').'", "name": "data_emissione", "value": "'.$record['data_emissione'].'", "required": 1 ]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-12">
			<span class="pull-right" ><b>'.tr('Totale utilizzato').':</b> '.moneyFormat($record['totale']).'</span>
		</div>
		<div class="clearfix">&nbsp;</div>
	</div>

';

// Collegamenti diretti (numerici)
$numero_documenti = $dbo->fetchNum('SELECT id FROM co_documenti WHERE id_dichiarazione_intento='.prepare($id_record));

if (!empty($numero_documenti)) {
    echo '
<div class="alert alert-danger">
    '.tr('Ci sono _NUM_ documenti collegati', [
        '_NUM_' => $numero_documenti,
    ]).'.
</div>';
}

echo '

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12">
            <a class="btn btn-danger ask '.(!empty($numero_documenti) ? 'disabled' : '').'" data-backto="record-edit" data-op="delete" data-id_record="'.$record['id'].'" data-id_plugin="'.$id_plugin.'" data-id_parent="'.$id_parent.'" '.(!empty($numero_documenti) ? 'disabled' : '').'>
                <i class="fa fa-trash"></i> '.tr('Elimina').'
            </a>

			<button type="submit" class="btn btn-primary pull-right">
			    <i class="fa fa-edit"></i> '.tr('Modifica').'
			</button>
		</div>
	</div>
</form>';
