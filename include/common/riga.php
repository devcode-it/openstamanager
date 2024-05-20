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

use Models\Module;
use Modules\Articoli\Articolo;

$articolo = Articolo::find($result['idarticolo']);
$width = $options['dir'] == 'uscita' && $articolo->fattore_um_secondaria ? 3 : 4;

// Descrizione
echo App::internalLoad('descrizione.php', $result, $options);

// Conti, rivalsa INPS e ritenuta d'acconto
echo App::internalLoad('conti.php', $result, $options);

// Iva
echo '
    <div class="row">
        <div class="col-md-'.$width.' '.(!empty($options['nascondi_prezzi']) ? 'hidden' : '').'">
            {[ "type": "select", "label": "'.tr('Iva').'", "name": "idiva", "required": 1, "value": "'.$result['idiva'].'", "ajax-source": "iva", "select-options": '.json_encode($options['select-options']['iva']).' ]}
        </div>';

// Quantità
echo '
        <div class="col-md-'.$width.'">
            {[ "type": "number", "label": "'.tr('Q.tà').'", "name": "qta", "required": 1, "value": "'.abs((float) $result['qta']).'", "decimals": "qta"'.(isset($result['max_qta']) ? ', "icon-after": "<span class=\"tip\" title=\"'.tr("L'elemento è collegato a un documento: la quantità massima ammessa è relativa allo stato di evasione dell'elemento nel documento di origine (quantità dell'elemento / quantità massima ammessa)").'\">/ '.numberFormat(abs((float) $result['max_qta']), 'qta').' <i class=\"fa fa-question-circle-o\"></i></span>"' : '').', "min-value": "'.abs((float) $result['qta_evasa']).'" ]}
        </div>';

// Unità di misura
echo '
        <div class="col-md-'.$width.'">
            {[ "type": "select", "label": "'.tr('Unità di misura').'", "icon-after": "add|'.(new Module())->getByField('title', 'Unità di misura', Models\Locale::getPredefined()->id).'", "name": "um", "value": "'.$result['um'].'", "ajax-source": "misure" ]}
        </div>';

// Unità di misura
if ($options['dir'] == 'uscita' && $articolo->fattore_um_secondaria) {
    echo '
        <div class="col-md-3">
            {[ "type": "number", "label": "'.tr('Q.tà secondaria').'", "name": "fattore_um_secondaria", "value": "'.abs((float) $articolo->fattore_um_secondaria * $result['qta']).'", "icon-after": "'.$articolo->um_secondaria.'" ]}
        </div>
        
        <script>
            input("fattore_um_secondaria").on("change", function() {
                let articolo = input("idarticolo").getData();
                let qta = input(this).get() / articolo.fattore_um_secondaria;

                if (input("qta").get() != qta) {
                    input("qta").set(qta);
                }
            });
            input("qta").on("change", function() {
                let articolo = input("idarticolo").getData();
                let fattore_um_secondaria = input(this).get() * articolo.fattore_um_secondaria;

                if (input("fattore_um_secondaria").get() != fattore_um_secondaria) {
                    input("fattore_um_secondaria").set(fattore_um_secondaria);
                }
            });
        </script>';
}
echo '
    </div>';

$is_nota = $options['is_nota'] ?: 0;
echo '
    <div class="row '.(!empty($options['nascondi_prezzi']) ? 'hidden' : '').'">
    <input type="hidden" name="prezzi_ivati" value="'.setting('Utilizza prezzi di vendita comprensivi di IVA').'">
    <input type="hidden" name="is_nota" value="'.$is_nota.'">
    <input type="hidden" name="dir" value="'.$options['dir'].'">';

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
            var prezzi_ivati = input("prezzi_ivati").get();
            var costo_unitario = $("#costo_unitario").val().toEnglish();
            var prezzo = 0;
            var sconto = $("#sconto").val().toEnglish();

            if ($("#modals select[id^=\'tipo_sconto\']").val() === "PRC") {
                sconto = sconto / 100 * $("#prezzo_unitario").val().toEnglish();
            }
            if (prezzi_ivati!=0) {
                percentuale_iva = input("idiva").getElement().selectData().percentuale;
                prezzo = ($("#prezzo_unitario").val().toEnglish() / (1 + percentuale_iva / 100)) - (sconto / (1 + percentuale_iva / 100));
            } else {
                prezzo = $("#prezzo_unitario").val().toEnglish() - sconto;
            }
            
            var provvigione = $("#provvigione").val().toEnglish();
            if ($("#modals select[id^=\'tipo_provvigione\']").val() === "PRC") {
                provvigione = provvigione / 100 * prezzo;
            }

            var guadagno = prezzo - provvigione - costo_unitario;
            var ricarico = ((prezzo / costo_unitario) - 1) * 100;
            var margine = (1 - (costo_unitario / prezzo)) * 100;            
            var parent = $("#costo_unitario").closest("div").parent();
            var div = $(".margine");
            var mediaponderata = 0;

            margine = isNaN(margine) || !isFinite(margine) ? 0: margine; // Fix per magine NaN
            ricarico = isNaN(ricarico) || !isFinite(ricarico) ? 0: ricarico; // Fix per ricarico NaN

            if ($("#idarticolo").val()) {
                mediaponderata = parseFloat($("#idarticolo").selectData().media_ponderata);
            }

            div.html("<table class=\"table table-extra-condensed table-margine\" >\
                        <tr>\
                            <td>\
                                <small>&nbsp;'.tr('Guadagno').':</small>\
                            </td>\
                            <td align=\"right\">\
                                <small>" + guadagno.toLocale() + "</small>\
                            </td>\
                            <td align=\"center\">\
                                <small>" + globals.currency + "</small>\
                            </td>\
                        </tr>\
                        <tr>\
                            <td>\
                                <small>&nbsp;'.tr('Margine').':</small>\
                            </td>\
                            <td align=\"right\">\
                                <small>" + margine.toLocale() + "<small>\
                            </td>\
                            <td align=\"center\">\
                                <small>&nbsp;%<small>\
                            </td>\
                        </tr>\
                        <tr>\
                            <td>\
                                <small>&nbsp;'.tr('Ricarico').':</small>\
                            </td>\
                            <td align=\"right\">\
                                <small>" + ricarico.toLocale() + "<small>\
                            </td>\
                            <td align=\"center\">\
                                <small>&nbsp;%<small>\
                            </td>\
                        </tr>\
                        <tr>\
                            <td>\
                                <small>&nbsp;'.tr('Costo medio').':</small>\
                            </td>\
                            <td align=\"right\">\
                                <small>" + (mediaponderata!=0 ? mediaponderata.toLocale() : "- ") + "</small>\
                            </td>\
                            <td align=\"center\">\
                                <small>" + globals.currency + "</small>\
                            </td>\
                        </tr>\
                    </table>");
                    
            if (guadagno < 0) {
                parent.addClass("has-error");
                $(".table-margine").addClass("badge-danger").removeClass("badge-success");
            } else {
                parent.removeClass("has-error");
                $(".table-margine").removeClass("badge-danger").addClass("badge-success");
            }
        }

        $("#modals > div").on("shown.bs.modal", function () {
            aggiorna_guadagno();
        });

        $("#prezzo_unitario").keyup(aggiorna_guadagno);
        $("#costo_unitario").keyup(aggiorna_guadagno);
        $("#sconto").keyup(aggiorna_guadagno);
        $("#modals select[id^=\'tipo_sconto\']").change(aggiorna_guadagno);
        $("#provvigione").keyup(aggiorna_guadagno);
        $("#modals select[id^=\'tipo_provvigione\']").change(aggiorna_guadagno);
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

if ($options['dir'] == 'entrata') {
    echo '
    <div class="row">
        <div class="col-md-4 margine"></div>
        <div class="col-md-4 prezzi"></div>';

    // Provvigione
    echo '
        <div class="col-md-4">
            <div class="sconto"></div>
            {[ "type": "number", "label": "'.tr('Provvigione unitaria').'", "name": "provvigione", "value": "'.($result['provvigione_percentuale'] ?: ($result['provvigione_unitaria'] ?: $result['provvigione_default'])).'", "icon-after": "choice|untprc|'.($result['tipo_provvigione'] ?: $result['tipo_provvigione_default']).'", "help": "'.tr('Provvigione destinata all\'agente.').'", "min-value": "0" ]}
        </div>
    </div>
    <div class="row">
        <div class="offset-md-4 col-md-4 minimo_vendita text-center"></div>
    </div>';
} else {
    echo '
    <div class="row">
        <div class="col-md-6 prezzi"></div>
        <div class="col-md-6 sconto"></div>
    </div>
    <br>';
}

// Data prevista evasione (per ordini)

if (in_array($module->getTranslation('title'), ['Ordini cliente', 'Ordini fornitore', 'Preventivi'])) {
    if ($options['action'] == 'add') {
        if ($module->getTranslation('title') == 'Ordini cliente') {
            $confermato = setting('Conferma automaticamente le quantità negli ordini cliente');
        } elseif ($module->getTranslation('title') == 'Ordini fornitore') {
            $confermato = setting('Conferma automaticamente le quantità negli ordini fornitore');
        } else {
            $confermato = setting('Conferma automaticamente le quantità nei preventivi');
        }
    } else {
        $confermato = $result['confermato'];
    }
    echo '
    <div class="card card-info collapsable collapsed-card">
        <div class="card-header with-border">
            <h3 class="card-title">'.tr('Informazioni aggiuntive').'</h3>
            <div class="card-tools pull-right">
                <button type="button" class="btn btn-card-tool" data-widget="collapse"><i class="fa fa-plus"></i></button>
            </div>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    {[ "type": "date", "label": "'.tr('Data prevista evasione').'", "name": "data_evasione", "value": "'.$result['data_evasione'].'" ]}
                </div>
                <div class="col-md-4">
                    {[ "type": "time", "label": "'.tr('Ora prevista evasione').'", "name": "ora_evasione", "value": "'.$result['ora_evasione'].'", "disabled": 1 ]}
                </div>
                <div class="col-md-4">
                    {[ "type": "checkbox", "label": "'.tr('Cambia data a tutte le righe').'", "name": "data_evasione_all", "value": "" ]}
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">

                </div>
                <div class="col-md-4">
                    {[ "type": "checkbox", "label": "'.tr('Articolo confermato').'", "name": "confermato", "value": "'.$confermato.'", "help": "'.tr('Articolo confermato dal _ANA_ e che è possibile evadere', ['_ANA_' => $module->getTranslation('title') == 'Ordini fornitore' ? tr('fornitore') : tr('cliente')]).'" ]}
                </div>
                <div class="col-md-4">
                    {[ "type": "checkbox", "label": "'.tr('Cambia stato a tutte le righe').'", "name": "confermato_all", "value": "" ]}
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            if(input("data_evasione").get()){
                input("ora_evasione").enable();
            }
        });

        $("#data_evasione").blur(function() {
            if(input("data_evasione").get()){
                input("ora_evasione").enable();
            } else{
                input("ora_evasione").disable();
                input("ora_evasione").set();
            }
        });
        </script>';
}

if (in_array($module->getTranslation('title'), ['Fatture di vendita', 'Fatture di acquisto'])) {
    echo '
    <script>
        $(document).ready(function() {
            if(input("data_evasione").get()){
                input("ora_evasione").enable();
            }

            controlla_prezzo();
            controlla_sconto();
        });

        $("#data_evasione").blur(function() {
            if(input("data_evasione").get()){
                input("ora_evasione").enable();
            } else{
                input("ora_evasione").disable();
                input("ora_evasione").set();
            }
        });

        $("#prezzo_unitario").on("keyup", function() {
            controlla_prezzo();
        });

        $("#sconto").on("keyup", function() {
            controlla_sconto();
        });

        function controlla_prezzo() {
            let prezzo_unitario = $("#prezzo_unitario").val().toEnglish();
            let div = $("#prezzo_unitario").closest("div").next("div[id*=errors]");
            if (prezzo_unitario < 0) {
                if (input("is_nota").get() == true) {
                    if (input("dir").get() == "entrata") {
                        div.html(`<span class="right badge badge-warning"><i class="fa fa-exclamation-triangle"></i> '.tr('Importo a credito').'</small>`);
                    } else {
                        div.html(`<span class="right badge badge-warning"><i class="fa fa-exclamation-triangle"></i> '.tr('Importo a debito').'</small>`);
                    }
                } else {
                    if (input("dir").get() == "entrata") {
                        div.html(`<span class="right badge badge-warning"><i class="fa fa-exclamation-triangle"></i> '.tr('Importo a debito').'</small>`);
                    } else {
                        div.html(`<span class="right badge badge-warning"><i class="fa fa-exclamation-triangle"></i> '.tr('Importo a credito').'</small>`);
                    }
                }
            } else {
                div.html("");
            }
        }

        function controlla_sconto() {
            let sconto = $("#sconto").val().toEnglish();
            let div = $("#sconto").closest("div").next("div[id*=errors]");
            let div_margine = $(".margine");
            let div_prezzi = $(".prezzi");

            div.css("margin-top", "-13px");
            if (sconto > 0) {
                div_margine.css("margin-top", "-20px");
                div_prezzi.css("margin-top", "-20px");
                div_prezzi.css("margin-bottom", "20px");
                div.html(`<span class="right badge badge-default" >'.tr('Sconto').'</small>`);
            } else if (sconto < 0) {
                div_margine.css("margin-top", "-20px");
                div_prezzi.css("margin-top", "-20px");
                div_prezzi.css("margin-bottom", "20px");
                div.html(`<span class="right badge badge-default" >'.tr('Maggiorazione').'</small>`);
            } else {
                div_margine.css("margin-top", "0px");
                div_prezzi.css("margin-top", "0px");
                div.html("");
            }
        }
    </script>';
}
