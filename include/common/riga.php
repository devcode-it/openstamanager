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
            {[ "type": "number", "label": "'.tr('Q.tà').'", "name": "qta", "required": 1, "value": "'.$result['qta'].'", "decimals": "qta"'.(isset($result['max_qta']) ? ', "icon-after": "/ '.numberFormat($result['max_qta'], 'qta').'", "help": "'.tr("Quantità dell'elemento / quantità totale massima").'"' : '').' ]}
        </div>';

// Unità di misura
echo '
        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Unità di misura').'", "icon-after": "add|'.Modules::get('Unità di misura')['id'].'", "name": "um", "value": "'.$result['um'].'", "ajax-source": "misure" ]}
        </div>
    </div>';

echo '
    <div class="row">';

//Fix per Altre spese intervento
if ($module['name'] == 'Interventi') {
    $options['dir'] = 'entrata';
    $result['prezzo_unitario_acquisto'] = $result['prezzo_acquisto'];
    $result['prezzo'] = $result['prezzo_vendita'];
}

$width = $options['dir'] == 'entrata' ? 4 : 6;
$label = $options['dir'] == 'entrata' ? tr('Prezzo unitario di vendita') : tr('Prezzo unitario');

if ($options['dir'] == 'entrata') {
    // Prezzo di acquisto unitario
    echo '
        <div class="col-md-'.$width.'">
            {[ "type": "number", "label": "'.tr('Prezzo unitario di acquisto').'", "name": "prezzo_acquisto", "value": "'.$result['prezzo_unitario_acquisto'].'", "icon-after": "'.currency().'" ]}
        </div>';

    // Funzione per l'aggiornamento in tempo reale del guadagno
    echo '
    <script>
        function aggiorna_guadagno() {
            var prezzo_acquisto = $("#prezzo_acquisto").val().toEnglish();
            var prezzo = $("#prezzo").val().toEnglish();
            var sconto = $("#sconto").val().toEnglish();
            if ($("#tipo_sconto").val() === "PRC") {
                sconto = sconto / 100 * prezzo;
            }

            var guadagno = prezzo - sconto - prezzo_acquisto;
            var parent = $("#prezzo_acquisto").closest("div").parent();
            var div = parent.find("div[id*=\"errors\"]");

            div.html("<small>'.tr('Guadagno').': " + guadagno.toLocale() + " " + globals.currency + "</small>");
            if (guadagno < 0) {
                parent.addClass("has-error");
                div.addClass("text-danger").removeClass("text-success");
            } else {
                parent.removeClass("has-error");
                div.removeClass("text-danger").addClass("text-success");
            }
        }

        aggiorna_guadagno();

        $("#prezzo").keyup(aggiorna_guadagno);
        $("#prezzo_acquisto").keyup(aggiorna_guadagno);
        $("#sconto").keyup(aggiorna_guadagno);
        $("#tipo_sconto").change(aggiorna_guadagno);
    </script>';
}

// Prezzo di vendita unitario
echo '
        <div class="col-md-'.$width.'">
            {[ "type": "number", "label": "'.$label.'", "name": "prezzo", "value": "'.$result['prezzo'].'", "required": 1, "icon-after": "'.currency().'" ]}
        </div>';

// Sconto unitario
echo '
        <div class="col-md-'.$width.'">
            {[ "type": "number", "label": "'.tr('Sconto unitario').'", "name": "sconto", "value": "'.$result['sconto_unitario'].'", "icon-after": "choice|untprc|'.$result['tipo_sconto'].'", "help": "'.tr('Il valore positivo indica uno sconto. Per applicare un rincaro inserire un valore negativo.').'" ]}
        </div>
    </div>';
