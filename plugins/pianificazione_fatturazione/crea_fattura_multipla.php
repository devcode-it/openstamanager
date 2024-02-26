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

use Plugins\PianificazioneFatturazione\Pianificazione;

include_once __DIR__.'/../../core.php';

$records = json_decode(get('records'), true);

if (empty($records)) {
    echo '<p>'.tr('Nessuna rata selezionata').'.</p>';

    return;
}

// print_r($records);
// echo '<script>console.log('.$records.')</script>';
foreach ($records as $j => $record) {
    $id_rata[$j] = $record['rata'];
    $pianificazione[$j] = Pianificazione::find($id_rata);
    $contratto[$j] = $pianificazione->contratto;
    $id_pianificazione[$j] = $pianificazione->id;

    foreach ($contratto[$j]->pianificazioni as $i => $p) {
        if ($p->id == $id_pianificazione[$i]) {
            $numero_rata[$i] = $i + 1;
            break;
        }
    }
}

$module_fattura = Modules::get('Fatture di vendita');
$id_conto = setting('Conto predefinito fatture di vendita');

echo '<form action="" method="post">
    <input type="hidden" name="op" value="add_fattura_multipla">
    <input type="hidden" name="backto" value="record-list">
    <input type="hidden" name="id_module" value="'.$id_module.'">
    <input type="hidden" name="id_plugin" value="'.$id_plugin.'">';

foreach ($records as $j => $record) {
    echo '<input type="hidden" name="rata['.$j.']" value="'.$record['rata'].'">';
}

// Data
echo '
    <div class="row">
        <div class="col-md-6">
            {[ "type": "date", "label": "'.tr('Data').'", "name": "data", "required": 1, "class": "text-center", "value": "'.date('Y-m-d').'" ]}
        </div>';

// Tipo di documento
echo '
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Tipo di fattura').'", "name": "idtipodocumento", "required": 1, "values": "query=SELECT * FROM `co_tipidocumento` LEFT JOIN `co_tipidocumento_lang` ON(`co_tipidocumento_lang`.`id_record` = `co_tipidocumento`.`id` AND `co_tipidocumento_lang`.`id_lang` = '.prepare(setting('Lingua')).') WHERE `dir`=\'entrata\'" ]}
        </div>';

// Sezionale
echo '<div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "values": "query=SELECT id, name AS descrizione FROM zz_segments WHERE id_module='.$module_fattura['id'].' ORDER BY name", "value":"'.$_SESSION['module_'.$module_fattura['id']]['id_segment'].'" ]}
        </div>';

// Conto
echo '
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Conto').'", "name": "id_conto", "required": 1, "value": "'.$id_conto.'", "ajax-source": "conti-vendite" ]}
        </div>';

// Accoda a fatture non emesse
echo '<div class="col-md-6">
            {[ "type": "checkbox", "label": "<small>'.tr('Aggiungere alle fatture di vendita non ancora emesse?').'</small>", "placeholder": "'.tr('Aggiungere alle fatture di vendita nello stato bozza?').'", "name": "accodare" ]}
        </div>
    </div>';

echo '<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary pull-right">
                <i class="fa fa-plus"></i> '.tr('Aggiungi').'
            </button>
		</div>
    </div>
</form>';
echo '<script>
    $(document).ready(init)
</script>';
