<?php

// Descrizione
echo App::internalLoad('descrizione.php', $result, $options);

// Conti, rivalsa INPS e ritenuta d'acconto
echo App::internalLoad('conti.php', $result, $options);

// Iva
echo '
    <div class="row">
        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Iva').'", "name": "idiva", "required": 1, "value": "'.$result['idiva'].'", "ajax-source": "iva" ]}
        </div>';

// Quantità
echo '
        <div class="col-md-4">
            {[ "type": "number", "label": "'.tr('Q.tà').'", "name": "qta", "required": 1, "value": "'.$result['qta'].'", "decimals": "qta" ]}
        </div>';

// Unità di misura
echo '
        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Unità di misura').'", "icon-after": "add|'.Modules::get('Unità di misura')['id'].'", "name": "um", "value": "'.$result['um'].'", "ajax-source": "misure" ]}
        </div>
    </div>';

// Costo unitario
echo '
    <div class="row">
        <div class="col-md-6">
            {[ "type": "number", "label": "'.tr('Costo unitario').'", "name": "prezzo", "value": "'.$result['prezzo'].'", "required": 1, "icon-after": "&euro;" ]}
        </div>';

// Sconto unitario
echo '
        <div class="col-md-6">
            {[ "type": "number", "label": "'.tr('Sconto unitario').'", "name": "sconto", "value": "'.$result['sconto_unitario'].'", "icon-after": "choice|untprc|'.$result['tipo_sconto'].'" ]}
        </div>
    </div>';

if ($module['name'] == 'Fatture di vendita') {
    $collapsed = empty($result['data_inizio_periodo']) && empty($result['data_fine_periodo']) && empty($result['riferimento_amministrazione']);

    echo '
    <div class="box box-info '.($collapsed ? 'collapsed-box' : '').'">
	    <div class="box-header with-border">
	        <h3 class="box-title">'.tr('Dati Fatturazione Elettronica').'</h3>
	        <div class="box-tools pull-right">
	            <button type="button" class="btn btn-box-tool" data-widget="collapse">
	                <i class="fa fa-plus"></i>
	            </button>
	        </div>
	    </div>
        <div class="box-body">';

    $tipi_cessione_prestazione = [
        [
            'id' => 'SC',
            'text' => 'SC - '.tr('Sconto'),
        ],
        [
            'id' => 'PR',
            'text' => 'PR - '.tr('Premio'),
        ],
        [
            'id' => 'AB',
            'text' => 'AB - '.tr('Abbuono'),
        ],
        [
            'id' => 'AC',
            'text' => 'AC - '.tr('Spesa accessoria'),
        ],
    ];

    // Data inizio periodo
    echo '
            <div class="row">
                <div class="col-md-6">
                    {[ "type": "select", "label": "'.tr('Tipo Cessione Prestazione').'", "name": "tipo_cessione_prestazione", "value": "'.$result['tipo_cessione_prestazione'].'", "values": '.json_encode($tipi_cessione_prestazione).' ]}
                </div>';

    // Riferimento amministrazione
    echo '
                <div class="col-md-6">
                    {[ "type": "text", "label": "'.tr('Riferimento Amministrazione').'", "name": "riferimento_amministrazione", "value": "'.$result['riferimento_amministrazione'].'", "maxlength": 20 ]}
                </div>
            </div>';

    // Data inizio periodo
    echo '
            <div class="row">
                <div class="col-md-6">
                    {[ "type": "date", "label": "'.tr('Data Inizio Periodo').'", "name": "data_inizio_periodo", "value": "'.$result['data_inizio_periodo'].'" ]}
                </div>';

    // Data fine periodo
    echo '
                <div class="col-md-6">
                    {[ "type": "date", "label": "'.tr('Data Fine Periodo').'", "name": "data_fine_periodo", "value": "'.$result['data_fine_periodo'].'" ]}
                </div>
            </div>';

    echo '
        </div>
    </div>';
}
