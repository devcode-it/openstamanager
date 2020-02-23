<?php

use Modules\Contratti\Contratto;

include_once __DIR__.'/../../core.php';

$contratto = Contratto::find($id_record);

$rata = get('rata');
$pianificazione = $contratto->pianificazioni[$rata];

$module_fattura = Modules::get('Fatture di vendita');

echo '
<form action="" method="post">
    <input type="hidden" name="op" value="add_fattura">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="rata" value="'.$rata.'">
    <input type="hidden" name="id_module" value="'.$id_module.'">
	<input type="hidden" name="id_plugin" value="'.$id_plugin.'">
	<input type="hidden" name="id_record" value="'.$id_record.'">';

// Data
echo '
    <div class="row">
        <div class="col-md-4">
            {[ "type": "date", "label": "'.tr('Data').'", "name": "data", "required": 1, "class": "text-center", "value": "-now-" ]}
        </div>';

// Tipo di documento
echo '
        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Tipo di fattura').'", "name": "idtipodocumento", "required": 1, "values": "query=SELECT * FROM co_tipidocumento WHERE dir=\'entrata\'" ]}
        </div>';

// Sezionale
echo '
        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "values": "query=SELECT id, name AS descrizione FROM zz_segments WHERE id_module='.$module_fattura['id'].' ORDER BY name", "value":"'.$_SESSION['module_'.$module_fattura['id']]['id_segment'].'" ]}
        </div>
    </div>';

// Descrizione fattura
$descrizione = tr('Rata _N_ del contratto numero _NUM_', [
    '_N_' => ($rata + 1),
    '_NUM_' => $contratto->numero,
]);

echo '
    <div class="row">
        <div class="col-md-12">
            {[ "type": "textarea", "label": "'.tr('Note della fattira').'", "name": "note", "value": "'.$descrizione.'" ]}
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
                            <small class="help-block">'.$riga->aliquota->descrizione.'</small>
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
