<?php

// Descrizione
echo App::internalLoad('descrizione.php', $result, $options);

// Conti, rivalsa INPS e ritenuta d'acconto
echo App::internalLoad('conti.php', $result, $options);

// Sconto percentuale
echo '
    <div class="row">';

if ($options['action'] == 'add') {
    echo '
        <div class="col-md-4">
            {[ "type": "number", "label": "'.tr('Sconto/maggiorazione percentuale').'", "name": "sconto_percentuale", "icon-after": "%", "help": "'.tr('Il valore positivo indica uno sconto: per applicare una maggiorazione inserire un valore negativo').'" ]}
        </div>';
}

// Sconto unitario
echo '
        <div class="col-md-'.($options['action'] == 'add' ? 4 : 6).'">
            {[ "type": "number", "label": "'.tr('Sconto/maggiorazione unitario').'", "name": "sconto_unitario", "value": "'.$result['sconto_unitario'].'", "icon-after": "'.currency().'", "help": "'.tr('Il valore positivo indica uno sconto: per applicare una maggiorazione inserire un valore negativo').'" ]}
        </div>';

// Iva
echo '
        <div class="col-md-'.($options['action'] == 'add' ? 4 : 6).'">
            {[ "type": "select", "label": "'.tr('Iva').'", "name": "idiva", "required": 1, "value": "'.$result['idiva'].'", "ajax-source": "iva" ]}
        </div>
    </div>';

// Funzione per l'aggiornamento in tempo reale del guadagno
echo '
    <script>
        var descrizione = $("#descrizione_riga");

        var form = descrizione.closest("form");
        var sconto_percentuale = form.find("#sconto_percentuale");
        var sconto_unitario = form.find("#sconto_unitario");

        var totale = '.($options['totale_imponibile'] ?: 0).';

        function aggiorna_sconto_percentuale() {
            var sconto = sconto_percentuale.val().toEnglish();
            var unitario = sconto / 100 * totale;

            var msg = sconto >= 0 ? "'.tr('Sconto percentuale').'" : "'.tr('Maggiorazione percentuale').'";

            sconto_unitario.val(unitario.toLocale());

            if (!descrizione.val() && sconto !== 0) {
                descrizione.val(msg + " " + Math.abs(sconto).toLocale() + "%");
            }
        }

        function aggiorna_sconto_unitario(){
            var sconto = sconto_unitario.val().toEnglish();
            var msg = sconto >= 0 ? "'.tr('Sconto unitario').'" : "'.tr('Maggiorazione unitaria').'";

            sconto_percentuale.val(0);

            if (!descrizione.val() && sconto !== 0) {
                descrizione.val(msg);
            }
        }

        sconto_percentuale.keyup(aggiorna_sconto_percentuale);
        sconto_unitario.keyup(aggiorna_sconto_unitario);
    </script>';
