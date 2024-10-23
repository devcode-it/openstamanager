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
include_once __DIR__.'/../init.php';
use Models\Module;

$id_contratto_precedente = $record['idcontratto_prev'];
$module = Module::find($id_module);

echo '
<form action="" method="post" id="rinnovo-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update_rinnovo">
    <input type="hidden" name="id_record" value="'.$id_record.'">

    <div class="card card-primary">
		<div class="card-header">
			<h3 class="card-title">'.tr('Informazioni per rinnovo').'</h3>
		</div>

		<div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    {[ "type": "checkbox", "label": "'.tr('Rinnovabile').'", "name": "rinnovabile", "help": "'.tr('Il contratto è rinnovabile?').'", "value": "$rinnovabile$" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "checkbox", "label": "'.tr('Tacito rinnovo').'", "name": "rinnovo_automatico", "help": "'.tr('Il contratto è da rinnovare automaticamente alla scadenza').'", "value": "$rinnovo_automatico$", "disabled": '.($record['rinnovabile'] ? 0 : 1).' ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "number", "label": "'.tr('Preavviso per rinnovo').'", "name": "giorni_preavviso_rinnovo", "decimals": "2", "value": "$giorni_preavviso_rinnovo$", "icon-after": "giorni", "disabled": '.($record['rinnovabile'] ? 0 : 1).' ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "number", "label": "'.tr('Ore rimanenti rinnovo').'", "name": "ore_preavviso_rinnovo", "decimals": "2", "value": "$ore_preavviso_rinnovo$", "icon-after": "ore", "disabled": '.($record['rinnovabile'] ? 0 : 1).', "help": "'.tr('Ore residue nel contratto prima di visualizzare una avviso per un eventuale rinnovo anticipato.').'" ]}
                </div>
            </div>

            <div class="col-md-12 text-right">
                <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> '.tr('Salva').'</button>
            </div>
        </div>
    </div>
</form>';

if (!empty($id_contratto_precedente)) {
    echo '
<table class="table table-hover table-sm table-bordered table-striped">
    <thead>
        <tr>
            <th>'.tr('Descrizione').'</th>
            <th width="100">'.tr('Totale').'</th>
            <th width="150">'.tr('Data inizio').'</th>
            <th width="150">'.tr('Data conclusione').'</th>
        </tr>
    </thead>

    <tbody>';
}

$counter = 0;
while (!empty($id_contratto_precedente) && $counter < 50) {
    ++$counter;
    $rs = $dbo->fetchArray('SELECT nome, numero, data_accettazione, data_conclusione, budget, idcontratto_prev FROM co_contratti WHERE id='.prepare($id_contratto_precedente));

    echo '
        <tr>
            <td>
                '.Modules::link($module->getTranslation('title'), $id_contratto_precedente, tr('Contratto num. _NUM_', [
        '_NUM_' => $rs[0]['numero'],
    ]).'<br><small class="text-muted">'.$rs[0]['nome'].'</small>').'
            </td>
            <td class="text-right">'.moneyFormat($rs[0]['budget']).'</td>
            <td align="center">'.Translator::dateToLocale($rs[0]['data_accettazione']).'</td>
            <td align="center">'.Translator::dateToLocale($rs[0]['data_conclusione']).'</td>
        </tr>';

    $id_contratto_precedente = $rs[0]['idcontratto_prev'];
}

echo '
    </tbody>
</table>

<script type="text/javascript">
    input("rinnovabile").on("change", function() {
        const disabled = parseInt($(this).val()) === 0;

        input("giorni_preavviso_rinnovo").setDisabled(disabled);
        input("ore_preavviso_rinnovo").setDisabled(disabled);
        input("rinnovo_automatico").setDisabled(disabled);
    });
</script>';
