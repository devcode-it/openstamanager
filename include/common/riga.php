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

<<<<<<< HEAD
echo '<div class="row">';

if (in_array($module["name"], ["Fatture di vendita", "Preventivi"])) {
    $col_md = 3;
} else {
    $col_md = 6;
}

// Prezzo di acquisto unitario
if (in_array($module["name"], ["Fatture di vendita", "Preventivi"])) {
    echo '
        <div class="col-md-' . $col_md . '">
            {[ "type": "number", "label": "' . tr('Prezzo di acquisto unitario') . '", "name": "prezzo_acquisto", "value": "' . $result['subtotale_acquisto'] . '", "required": 0, "icon-after": "&euro;", "onkeyup": "aggiorna_guadagno()" ]}
        </div>';
}
=======
>>>>>>> 2ae57384089d87555550bf51f8419fa60ad26f2b
// Costo unitario
echo '
        <div class="col-md-' . $col_md . '">
            {[ "type": "number", "label": "'.tr('Costo unitario').'", "name": "prezzo", "value": "'.$result['prezzo'].'", "required": 1, "icon-after": "&euro;", "onkeyup": "aggiorna_guadagno()" ]}
        </div>';

// Sconto unitario
echo '
        <div class="col-md-' . $col_md . '">
            {[ "type": "number", "label": "'.tr('Sconto unitario').'", "name": "sconto", "value": "'.$result['sconto_unitario'].'", "icon-after": "choice|untprc|'.$result['tipo_sconto'].'" ]}
        </div>';

// Guadagno (calcolato automaticamente con funzione JavaScript)
if (in_array($module["name"], ["Fatture di vendita", "Preventivi"])) {
    echo '
    <!-- Script per il calcolo e la verifica del guadagno in tempo reale -->
    <script>
    // Converti il numero (es. 2.654,52) in formato internazionale (es. 2564.52)
    function converti_numero(text) {
        return text.replace(".", "").replace(",", ".")
    }
    
    // Verifica se il guadagno è negativo.
    function verifica_guadagno () {
        var guadagno = $("#guadagno");
        var div = guadagno.closest(".form-group");
        if (parseFloat(converti_numero(guadagno.val())) < 0) {
            if (!div.hasClass("has-error")) {
                div.addClass("has-error");
            }
        } else {
            if (div.hasClass("has-error")) {
                div.removeClass("has-error");
            }
        }
    }
    
    // Aggiorna il campo Guadagno, in tempo reale. Richiama poi la funzione verifica_guadagno
    function aggiorna_guadagno() {
        var prezzo_acquisto = $("#prezzo_acquisto");
        var prezzo_vendita = $("#prezzo");
        var guadagno = $("#guadagno");
        if (prezzo_acquisto.val() !== "" && prezzo_vendita.val() !== "") {
              guadagno.val(parseFloat(converti_numero(prezzo_vendita.val())) - parseFloat(converti_numero(prezzo_acquisto.val())));
              verifica_guadagno()
        }
        
    }
    </script>
        <div class="col-md-' . $col_md . '">
            {[ "type": "number", "label": "' . tr('Guadagno') . '", "name": "guadagno", "value": "' . $result['guadagno'] . '", "required": 0, "icon-after": "&euro;", "extra":"readonly" ]}
        </div>
<<<<<<< HEAD
        <script>
        verifica_guadagno()
</script>
    ';
}

echo '</div>';
=======
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
>>>>>>> 2ae57384089d87555550bf51f8419fa60ad26f2b
