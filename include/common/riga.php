<?php

// Descrizione
echo App::internalLoad('descrizione.php', $result, $options);

// Conti, rivalsa INPS e ritenuta d'acconto
echo App::internalLoad('conti.php', $result, $options);

// Iva
echo '
    <div class="row">
        <div class="col-md-4 '.(!empty($options['nascondi_prezzi']) ? 'hidden' : '').'">
            {[ "type": "select", "label": "'.tr('Iva').'", "name": "idiva", "required": 1, "value": "'.$result['idiva'].'", "ajax-source": "iva" ]}
        </div>';

// Quantità
echo '
        <div class="col-md-4">
            {[ "type": "number", "label": "'.tr('Q.tà').'", "name": "qta", "required": 1, "value": "'.$result['qta'].'", "decimals": "qta"'.(isset($result['max_qta']) ? ', "icon-after": "<span class=\"tip\" title=\"'.tr("L'elemento è collegato a un documento: la quantità massima ammessa è relativa allo stato di evasione dell'elemento nel documento di origine (quantità dell'elemento / quantità massima ammessa)").'\">/ '.numberFormat($result['max_qta'], 'qta').' <i class=\"fa fa-question-circle-o\"></i></span>"' : '').', "min-value": "'.Translator::numberToLocale($result['qta_evasa']).'" ]}
        </div>';

// Unità di misura
echo '
        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Unità di misura').'", "icon-after": "add|'.Modules::get('Unità di misura')['id'].'", "name": "um", "value": "'.$result['um'].'", "ajax-source": "misure" ]}
        </div>
    </div>';

echo '
    <div class="row '.(!empty($options['nascondi_prezzi']) ? 'hidden' : '').'">';

$width = $options['dir'] == 'entrata' ? 4 : 6;
$label = $options['dir'] == 'entrata' ? tr('Prezzo unitario di vendita') : tr('Prezzo unitario');

if ($options['dir'] == 'entrata') {
    // Prezzo di acquisto unitario
    echo '
        <div class="col-md-'.$width.'">
            {[ "type": "number", "label": "'.tr('Prezzo unitario di acquisto').'", "name": "costo_unitario", "value": "'.$result['costo_unitario'].'", "icon-after": "'.currency().'" ]}
        </div>';

    // Funzione per l'aggiornamento in tempo reale del guadagno
    echo '
    <script>
        function aggiorna_guadagno() {
            var costo_unitario = $("#costo_unitario").val().toEnglish();
            var prezzo = $("#prezzo_unitario").val().toEnglish();
            var sconto = $("#sconto").val().toEnglish();
            if ($("#tipo_sconto").val() === "PRC") {
                sconto = sconto / 100 * prezzo;
            }

            var guadagno = prezzo - sconto - costo_unitario;
            var margine = (((prezzo - sconto) * 100) / costo_unitario) - 100;
            var parent = $("#costo_unitario").closest("div").parent();
            var div = parent.find("div[id*=\"errors\"]");

            margine = isNaN(margine) || !isFinite(margine) ? 0: margine; // Fix per magine NaN

            div.html("<small>'.tr('Guadagno').': " + guadagno.toLocale() + " " + globals.currency + " &nbsp; '.tr('Margine').': " + margine.toLocale() + " %</small>");
            if (guadagno < 0) {
                parent.addClass("has-error");
                div.addClass("text-danger").removeClass("text-success");
            } else {
                parent.removeClass("has-error");
                div.removeClass("text-danger").addClass("text-success");
            }
        }

        aggiorna_guadagno();

        $("#prezzo_unitario").keyup(aggiorna_guadagno);
        $("#costo_unitario").keyup(aggiorna_guadagno);
        $("#sconto").keyup(aggiorna_guadagno);
        $("#tipo_sconto").change(aggiorna_guadagno);
    </script>';
}

// Prezzo di vendita unitario
echo '
        <div class="col-md-'.$width.'">
            {[ "type": "number", "label": "'.$label.'", "name": "prezzo_unitario", "value": "'.$result['prezzo_unitario_corrente'].'", "required": 1, "icon-after": "'.currency().'", "help": "'.($options['dir'] == 'entrata' && setting('Utilizza prezzi di vendita comprensivi di IVA') ? tr('Importo IVA inclusa') : '').'" ]}
        </div>';

// Sconto unitario
echo '
        <div class="col-md-'.$width.'">
            {[ "type": "number", "label": "'.tr('Sconto unitario').'", "name": "sconto", "value": "'.($result['sconto_percentuale'] ?: $result['sconto_unitario_corrente']).'", "icon-after": "choice|untprc|'.$result['tipo_sconto'].'", "help": "'.tr('Il valore positivo indica uno sconto. Per applicare una maggiorazione inserire un valore negativo.').'" ]}
        </div>
    </div>';
