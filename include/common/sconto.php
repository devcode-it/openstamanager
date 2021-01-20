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

// Descrizione
echo App::internalLoad('descrizione.php', $result, $options);

// Conti, rivalsa INPS e ritenuta d'acconto
echo App::internalLoad('conti.php', $result, $options);

$incorpora_iva = $options['dir'] == 'entrata' && setting('Utilizza prezzi di vendita comprensivi di IVA');

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
        {[ "type": "number", "label": "'.tr('Sconto/maggiorazione unitario').'", "name": "sconto_unitario", "value": "'.$result['sconto_unitario_corrente'].'", "icon-after": "'.currency().'", "help": "'.tr('Il valore positivo indica uno sconto: per applicare una maggiorazione inserire un valore negativo').'" ]}
    </div>';

// Iva
echo '
    <div class="col-md-'.($options['action'] == 'add' ? 4 : 6).'">
        {[ "type": "select", "label": "'.tr('Iva').'", "name": "idiva", "required": 1, "value": "'.$result['idiva'].'", "ajax-source": "iva" ]}
    </div>
</div>';

// Funzione per l'aggiornamento in tempo reale dello sconto
$totale_documento = $incorpora_iva ? $options['totale_documento'] : $options['totale_imponibile_documento'];
echo '
<script>
    var descrizione = $("#descrizione_riga");

    var form = descrizione.closest("form");
    var sconto_percentuale = form.find("#sconto_percentuale");
    var sconto_unitario = form.find("#sconto_unitario");

    var totale = '.($totale_documento ?: 0).';

    function aggiorna_sconto_percentuale() {
        var sconto = sconto_percentuale.val().toEnglish();
        var unitario = sconto / 100 * totale;

        var msg = sconto >= 0 ? "'.tr('Sconto percentuale').'" : "'.tr('Maggiorazione percentuale').'";

        sconto_unitario.val(unitario.toLocale());

        if (sconto !== 0) {
            descrizione.val(msg + " " + Math.abs(sconto).toLocale() + "%");
        }
    }

    function aggiorna_sconto_unitario(){
        var sconto = sconto_unitario.val().toEnglish();
        var msg = sconto >= 0 ? "'.tr('Sconto unitario').'" : "'.tr('Maggiorazione unitaria').'";

        sconto_percentuale.val(0);

        if (sconto !== 0) {
            descrizione.val(msg);
        }
    }

    sconto_percentuale.keyup(aggiorna_sconto_percentuale);
    sconto_unitario.keyup(aggiorna_sconto_unitario);
</script>';
