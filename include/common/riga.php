<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

// Iva
echo '
    <div class="row">
        <div class="col-md-4 '.(!empty($options['nascondi_prezzi']) ? 'hidden' : '').'">
            {[ "type": "select", "label": "'.tr('Iva').'", "name": "idiva", "required": 1, "value": "'.$result['idiva'].'", "ajax-source": "iva", "select-options": '.json_encode($options['select-options']['iva']).' ]}
        </div>';

// Quantità
echo '
        <div class="col-md-4">
            {[ "type": "number", "label": "'.tr('Q.tà').'", "name": "qta", "required": 1, "value": "'.$result['qta'].'", "decimals": "qta"'.(isset($result['max_qta']) ? ', "icon-after": "<span class=\"tip\" title=\"'.tr("L'elemento è collegato a un documento: la quantità massima ammessa è relativa allo stato di evasione dell'elemento nel documento di origine (quantità dell'elemento / quantità massima ammessa)").'\">/ '.numberFormat($result['max_qta'], 'qta').' <i class=\"fa fa-question-circle-o\"></i></span>"' : '').', "min-value": "'.$result['qta_evasa'].'" ]}
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

            div.html("<small>&nbsp;'.tr('Guadagno').': " + guadagno.toLocale() + " " + globals.currency + " &nbsp; '.tr('Margine').': " + margine.toLocale() + " %</small>");
            if (guadagno < 0) {
                parent.addClass("has-error");
                div.addClass("label-danger").removeClass("label-success");
            } else {
                parent.removeClass("has-error");
                div.removeClass("label-danger").addClass("label-success");
            }
        }

        $("#modals > div").on("shown.bs.modal", function () {
            aggiorna_guadagno();
        });

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

// Data prevista evasione (per ordini)

if (in_array($module['name'], ['Ordini cliente', 'Ordini fornitore'])) {
    if ($options['action'] == 'add') {
        if ($options['dir'] == 'entrata') {
            $confermato = setting('Conferma automaticamente le quantità negli ordini cliente');
        } else {
            $confermato = setting('Conferma automaticamente le quantità negli ordini fornitore');
        }
    } else {
        $confermato = $result['confermato'];
    }
    echo '
<div class="box box-warning collapsable collapsed-box">
    <div class="box-header with-border">
        <h3 class="box-title">'.tr('Informazioni aggiuntive').'</h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i></button>
        </div>
    </div>

    <div class="box-body">
        <div class="row">
            <div class="col-md-4">
                {[ "type": "date", "label": "'.tr('Data prevista evasione').'", "name": "data_evasione", "value": "'.$result['data_evasione'].'" ]}
            </div>
            <div class="col-md-4">
                {[ "type": "checkbox", "label": "'.tr('Cambia data a tutte le righe').'", "name": "data_evasione_all", "value": "" ]}
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                {[ "type": "checkbox", "label": "'.tr('Articolo confermato').'", "name": "confermato", "value": "'.$confermato.'" ]}
            </div>
            <div class="col-md-4">
                {[ "type": "checkbox", "label": "'.tr('Cambia stato a tutte le righe').'", "name": "confermato_all", "value": "" ]}
            </div>
        </div>
    </div>
</div>';
}
