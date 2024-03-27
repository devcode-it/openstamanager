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

use Models\Module;
use Plugins\PianificazioneFatturazione\Pianificazione;

include_once __DIR__.'/../../core.php';

$id_rata = get('rata');
$pianificazione = Pianificazione::find($id_rata);
$contratto = $pianificazione->contratto;

$id_pianificazione = $pianificazione->id;

foreach ($contratto->pianificazioni as $i => $p) {
    if ($p->id == $id_pianificazione) {
        $numero_rata = $i + 1;
        break;
    }
}

$id_module_fattura = (new Module())->getByField('name', 'Fatture di vendita', Models\Locale::getPredefined()->id);

$id_conto = setting('Conto predefinito fatture di vendita');
$data = date('Y-m', strtotime($pianificazione->data_scadenza)).'-'.date('d', strtotime($contratto->data_accettazione));

echo '
<form action="" method="post">
    <input type="hidden" name="op" value="add_fattura">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="rata" value="'.$id_rata.'">
    <input type="hidden" name="id_module" value="'.$id_module.'">
	<input type="hidden" name="id_plugin" value="'.$id_plugin.'">
	<input type="hidden" name="id_record" value="'.$id_record.'">';

// Data
echo '
    <div class="row">
        <div class="col-md-6">
            {[ "type": "date", "label": "'.tr('Data').'", "name": "data", "required": 1, "class": "text-center", "value": "'.$pianificazione->data_scadenza.'" ]}
        </div>';

// Tipo di documento
echo '
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Tipo di fattura').'", "name": "idtipodocumento", "required": 1, "values": "query=SELECT * FROM `co_tipidocumento` LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento`.`id` = `co_tipidocumento_lang`.`id_record` AND `co_tipidocumento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `dir`=\'entrata\'" ]}
        </div>
    </div>';

// Sezionale
echo '
    <div class="row">
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "ajax-source": "segmenti", "select-options": '.json_encode(['id_module' => $id_module_fattura, 'is_sezionale' => 1]).', "value":"'.$_SESSION['module_'.$id_module_fattura]['id_segment'].'" ]}
        </div>';

// Conto
echo '
        <div class="col-md-6">
                {[ "type": "select", "label": "'.tr('Conto').'", "name": "id_conto", "required": 1, "value": "'.$id_conto.'", "ajax-source": "conti-vendite" ]}
        </div>
    </div>';

// Accoda a fatture non emesse
echo '
    <div class="row">
        <div class="col-md-6">
            {[ "type": "checkbox", "label": "<small>'.tr('Aggiungere alle fatture di vendita non ancora emesse?').'</small>", "placeholder": "'.tr('Aggiungere alle fatture di vendita nello stato bozza?').'", "name": "accodare" ]}
        </div>
    </div>';

// gestione replace
$descrizione = setting('Descrizione fattura pianificata');
$modules = (new Module())->getByField('name', 'Contratti', Models\Locale::getPredefined()->id);
$variables = include Modules::filepath($modules, 'variables.php');
foreach ($variables as $variable => $value) {
    $descrizione = str_replace('{'.$variable.'}', $value, $descrizione);
}
$descrizione = str_replace('{rata}', $numero_rata, $descrizione);
$descrizione = str_replace('{zona}', $zona, $descrizione);

echo '
    <div class="row">
        <div class="col-md-12">
            {[ "type": "textarea", "label": "'.tr('Note della fattura').'", "name": "note", "value": "'.$descrizione.'" ]}
        </div>
    </div>';

// Righe
echo '
    <div class="box box-info">
        <div class="box-header with-border">
            <h3 class="box-title">
                '.tr('Righe previste').'
            </h3>
        </div>
        <div class="box-body">
            <table class="table table-bordered table-striped table-hover table-condensed">
                <thead>
                    <tr>
                        <th width="40%">'.tr('Descrizione').'</th>
                        <th class="text-center">'.tr('Q.tà').'</th>
                        <th class="text-center">'.tr('Prezzo unitario').'</th>
                        <th class="text-center">'.tr('IVA').'</th>
                        <th class="text-center">'.tr('Totale imponbile').'</th>
                    </tr>
                </thead>
                <tbody>';

$righe = $pianificazione->getRighe();
foreach ($righe as $riga) {
    echo '
                    <tr>
                        <td>'.$riga->descrizione.'</td>
                        <td class="text-center">'.$riga->qta.'</td>
                        <td class="text-right">'.moneyFormat($riga->prezzo_unitario).'</td>
                        <td class="text-right">
                            '.moneyFormat($riga->iva).'<br>
                            <small class="help-block">'.$riga->aliquota->getTranslation('name').'</small>
                        </td>
                        <td class="text-right">'.moneyFormat($riga->totale_imponibile).'</td>
                    </tr>';
}

echo '
                </tbody>
            </table>
        </div>
    </div>';

echo '
    <!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary pull-right">
                <i class="fa fa-plus"></i> '.tr('Aggiungi').'
            </button>
		</div>
    </div>
</form>';

echo '
<script>$(document).ready(init)</script>';
