<?php

use Modules\Contratti\Contratto;

include_once __DIR__.'/../../core.php';

$contratto = Contratto::find($id_record);

echo '
<form action="" method="post">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="id_module" value="'.$id_module.'">
	<input type="hidden" name="id_plugin" value="'.$id_plugin.'">
	<input type="hidden" name="id_record" value="'.$id_record.'">

    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs nav-justified">
            <li class="active"><a href="#periodi" data-toggle="tab">'.tr('Periodi').'</a></li>

            <li><a href="#righe" data-toggle="tab">'.tr('Righe').'</a></li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane active" id="periodi">
                <div class="row">';

$data_corrente = $contratto->data_accettazione->startOfMonth();
$data_conclusione = $contratto->data_conclusione;
$count = 0;
while ($data_corrente->lessThanOrEqualTo($data_conclusione)) {
    $data = $data_corrente->endOfMonth()->format('Y-m-d');

    echo '
                    <div class="col-md-3">
                        <label for="m_'.$count.'">
                            <input type="checkbox" class="unblockable" id="m_'.$count.'" name="selezione_periodo['.$count.']" />
                            '.ucfirst($data_corrente->formatLocalized('%B %Y')).'
                        </label>
                        <input type="hidden" name="periodo['.$count.']" value="'.$data.'">
                    </div>';

    $data_corrente = $data_corrente->addDay();
    ++$count;
}

echo '
                </div>
            </div>

            <div class="tab-pane" id="righe">';

$iva_righe = $contratto->getRighe()->groupBy('idiva');
foreach ($iva_righe as $id_iva => $righe) {
    $iva = $righe->first()->aliquota;
    $righe = $righe->toArray();

    echo '
                <h5>'.tr('Informazioni generali sulle righe con IVA: _IVA_', [
                    '_IVA_' => $iva->descrizione,
                ]).'</h5>

                <div class="row">
                    <div class="col-md-9">
                        {[ "type": "textarea", "label": "'.tr('Descrizione').'", "name": "descrizione['.$id_iva.']", "value": "'.tr('Canone contratto numero _NUM__IVA_', [
                            '_IVA_' => (count($iva_righe) > 1) ? ': '.$iva->descrizione : '',
                            '_NUM_' => $contratto->numero,
                        ]).'" ]}

                        {[ "type": "number", "label": "'.tr('Q.tà per fattura').'", "name": "qta['.$id_iva.']", "required": 1, "value": "1", "decimals": "qta", "min-value": "1" ]}
                    </div>
                    <div class="col-md-3">
                        <p><b>'.tr('Imponibile').'</b>: '.moneyFormat(sum(array_column($righe, 'totale_imponibile'))).'</p>
                        <p><b>'.tr('IVA').'</b>: '.moneyFormat(sum(array_column($righe, 'iva'))).'</p>
                        <p><b>'.tr('Totale').'</b>: '.moneyFormat(sum(array_column($righe, 'totale'))).'</p>
                    </div>
                </div>
                <hr>';
}

echo '
            </div>
        </div>
    </div>

    <div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-chevron-right"></i> '.tr('Procedi').'</button>
		</div>
	</div>
</form>';

echo '
<script>$(document).ready(init)</script>';
