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
        <script>
        verifica_guadagno()
</script>
    ';
}

echo '</div>';