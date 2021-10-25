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

use API\Services;
use Carbon\Carbon;

include_once __DIR__.'/../../core.php';

// Informazioni sui servizi attivi
echo '
<div class="row">';
$days = 60;
$limite_scadenze = (new Carbon())->addDays($days);
if (Services::isEnabled()) {
    echo '
    <!-- Informazioni sui Servizi attivi -->
    <div class="col-md-12 col-lg-6">
        <div class="box box-primary">
            <div class="box-header">
                <h3 class="box-title">
                    '.tr('Servizi').'
                </h3>
            </div>

            <div class="box-body">';

    $servizi = Services::getServiziAttivi()->flatten(1);
    if (!$servizi->isEmpty()) {
        echo '
                <table class="box-body table table-striped table-hover table-condensed">
                    <thead>
                        <tr>
                            <th width="50%">'.tr('Nome').'</th>
                            <th>'.tr('Tipo').'</th>
                            <th width="30%">'.tr('Scadenza').'</th>
                        </tr>
                    </thead>

                    <tbody>';
        foreach ($servizi as $servizio) {
            $scadenza = Carbon::parse($servizio['data_conclusione']);

            echo '
                        <tr class="'.($scadenza->lessThan(Carbon::now()) ? 'danger' : ($scadenza->lessThan($limite_scadenze) ? 'warning' : '')).'">
                            <td>'.$servizio['codice'].' - '.$servizio['nome'].'</td>
                            <td>'.$servizio['sottocategoria'].'</td>
                            <td>'.dateFormat($scadenza).' ('.$scadenza->diffForHumans().')</td>
                        </tr>';
        }

        echo '
                    </tbody>
                </table>';
    } else {
        echo '
                <div class="alert alert-info" role="alert">
                    <i class="fa fa-info"></i> '.tr('Nessun servizio abilitato al momento').'.
                </div>';
    }

    echo '
            </div>
        </div>
    </div>

    <!-- Informazioni sulle Risorse API -->
    <div class="col-md-12 col-lg-6">
        <div class="box box-primary">
            <div class="box-header">
                <h3 class="box-title">
                    '.tr('Risorse').'
                </h3>
            </div>

            <div class="box-body">';

    // Elaborazione delle risorse API in scadenza
    $risorse_attive = Services::getRisorseAttive();
    if (!$risorse_attive->isEmpty()) {
        $risorse_in_scadenza = Services::getRisorseInScadenza($limite_scadenze);
        $risorse_scadute = Services::getRisorseScadute();

        if (!$risorse_in_scadenza->isEmpty() || !$risorse_scadute->isEmpty() ) {
            
            if (!$risorse_in_scadenza->isEmpty()){
                echo '
                    <div class="alert alert-warning" role="alert"> <i class="fa fa-clock-o"></i> '.tr('Attenzione, alcune risorse sono in scadenza o stanno per esaurire i crediti:', [
                        '_NUM_' => $risorse_in_scadenza->count(),
                    ]).'</div>';

            }

            if (!$risorse_scadute->isEmpty()){
                echo '
                    <div class="alert alert-danger" role="alert"> <i class="fa fa-exclamation-triangle"></i> '.tr('Attenzione, alcune risorse sono scadute o hanno esaurito i crediti:', [
                        '_NUM_' => $risorse_scadute->count(),
                    ]).'</div>';
            }
        
        } else {
            echo '
            <div class="alert alert-success" role="alert"> <i class="fa fa-check-circle"></i> '.tr('Bene, tutte le risorse sono attive e non presentano avvisi:', [
                '_NUM_' => $risorse_attive->count(),
            ]).'</div>';
        }

        echo '
            <table class="box-body table table-striped table-hover table-condensed">
                <thead>
                    <tr>
                        <th width="50%">'.tr('Nome').'</th>
                        <th>'.tr('Crediti').'</th>
                        <th width="30%">'.tr('Scadenza').'</th>
                    </tr>
                </thead>
                
                <tbody>';

        foreach ($risorse_attive as $servizio) {
            $scadenza = Carbon::parse($servizio['expiration_at']);
            echo '
                <tr class="'.($scadenza->lessThan(Carbon::now()) ? 'danger' : ($scadenza->lessThan($limite_scadenze) ? 'warning' : '')).'">
                    <td>'.$servizio['name'].'</td>
                    <td>'.(($servizio['credits'] < 100 && $servizio['credits']) ? '<b><i class="fa fa-icon fa-warning" ></i>' : '').(($servizio['credits']) ? $servizio['credits'] : '-').(($servizio['credits'] < 100 && $servizio['credits']) ? '</b>' : '').'</td>
                    <td>'.((Carbon::now()->diffInDays($scadenza, false) < $days && $scadenza) ? '<b><i class="fa fa-icon fa-warning" ></i>' : '').dateFormat($scadenza).' ('.$scadenza->diffForHumans().')'.((Carbon::now()->diffInDays($scadenza, false) < $days && $scadenza) ? '</b>' : '').'</td>
                </tr>';
        }

        echo '
                </tbody>
            </table></div></div>';

        //Il servizio Fatturazione Elettronica deve essere presente per visualizzare le Statistiche su Fatture Elettroniche
        if (Services::getRisorseAttive()->where('name', 'Fatturazione Elettronica')->count()) {
            echo '

                <div class="panel panel-info">
                    <div class="panel-heading" >  <i class="fa fa-bar-chart"></i> '.tr('Statistiche su Fatture Elettroniche').'</div>

                    <div class="panel-body">
                                
                        <div class="alert hidden" role="alert" id="spazio-fe">
                            <i id="spazio-fe-icon" class=""></i> <span>'.tr('Spazio per fatture elettroniche _TEXT_: _NUM_ utilizzati su _TOT_ disponibili', [
                                '_TEXT_' => '<span id="spazio-fe-text"></span>',
                                '_NUM_' => '<span id="spazio-fe-occupato"></span>',
                                '_TOT_' => '<span id="spazio-fe-totale"></span>',
                            ]).'.<br>'.tr("Contatta l'assistenza per risolvere il problema").'</span>.
                        </div>


                        <div class="alert hidden" role="alert" id="numero-fe">
                            <i id="numero-fe-icon" class=""></i> <span>'.tr('Numero di fatture elettroniche per l\'annualità _TEXT_: _NUM_ documenti transitati su _TOT_ disponibili', [
                                '_TEXT_' => '<span id="numero-fe-text"></span>',
                                '_NUM_' => '<span id="numero-fe-occupato"></span>',
                                '_TOT_' => '<span id="numero-fe-totale"></span>',
                            ]).'.<br>'.tr("Contatta l'assistenza per risolvere il problema").'</span>.
                        </div>


                        <table class="box-body table table-striped table-hover table-condensed">
                            <thead>
                                <tr>
                                    <th>'.tr('Anno').'</th>
                                    <th>
                                        '.tr('Documenti archiviati').'
                                        <span class="tip" title="'.tr('Fatture attive e relative ricevute, fatture passive').'.">
                                            <i class="fa fa-question-circle-o"></i>
                                        </span>
                                    </th>

                                    <th>
                                        '.tr('Totale spazio occupato').'
                                        <span class="tip" title="'.tr('Fatture attive con eventuali allegati e ricevute, fatture passive con eventuali allegati').'.">
                                            <i class="fa fa-question-circle-o"></i>
                                        </span>
                                    </th>
                                </tr>
                            </thead>

                            <tbody id="elenco-fe">
                                <tr style="background-color:#CCCCCC;" >
                                    <td>'.tr('Totale').'</td>
                                    <td id="fe_numero"></td>
                                    <td id="fe_spazio"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <script>
                $(document).ready(function (){
                    aggiornaStatisticheFE();
                });
                </script>';
        }
    } else {
        echo '
                <div class="alert alert-info" role="alert">
                    <i class="fa fa-info"></i> '.tr('Nessuna risorsa Services abilitata').'.
                </div>';
    }

    echo '
    </div>';
} else {
    /*
    echo '
    <div class="col-md-12 col-lg-6">
        <div class="alert alert-warning" role="alert">
            <i class="fa fa-warning"></i> '.tr("Configurazione per l'accesso Services non completata correttamente").'. '.tr('Per abilitare i servizi, compilare l\'impostazione "OSMCloud Services API Token"').'.
        </div>
    </div>';
    */
}

echo '
</div>

<div class="row">
    <div class="col-md-12 col-lg-6">
        <div class="box box-info">
            <div class="box-header">
                <h3 class="box-title">
                    '.tr('Moduli disponibili').'
                </h3>
            </div>

            <div class="box-body" id="moduli">
            </div>
        </div>
    </div>';

// Widgets
echo '
    <div class="col-md-12 col-lg-6">
        <div class="box box-info">
            <div class="box-header">
                <h3 class="box-title">
                    '.tr('Widget disponibili').'
                </h3>
            </div>

            <div class="box-body" id="widget">
            </div>
        </div>
    </div>
</div>

<script>
function aggiornaStatisticheFE(){
    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "GET",
        dataType: "JSON",
        data: {
            id_module: globals.id_module,
            op: "informazioni-fe",
        },
        success: function (response) {
            $("#fe_numero").html(response.invoice_number);
            
            $("#fe_spazio").html(response.spazio_occupato);

            if (response.spazio_totale){
                $("#fe_spazio").html($("#fe_spazio").html() + " / " + response.spazio_totale);

                if (response.spazio_occupato>response.spazio_totale && response.avviso_spazio){
                    $("#fe_spazio").html("<span style=\"font-weight:bold;\" ><i class=\"fa fa-warning\" ></i> " + $("#fe_spazio").html() + "</span>");
                }
            }

            // Informazioni sullo spazio occupato
            $("#spazio-fe-occupato").html(response.spazio_occupato);
            $("#spazio-fe-totale").html(response.spazio_totale);
            if (response.avviso_spazio) {

                $("#spazio-fe").removeClass("hidden");

                if (response.spazio_occupato<response.spazio_totale){
                    $("#spazio-fe-icon").addClass("fa fa-warning");
                    $("#spazio-fe").addClass("alert-warning");
                    $("#spazio-fe-text").html("'.tr('in esaurimento').'"); 
                }
                else if (response.spazio_occupato>=response.spazio_totale){
                    $("#spazio-fe-icon").addClass("fa fa-times");
                    $("#spazio-fe").addClass("alert-danger");
                    $("#spazio-fe-text").html("'.tr('terminato').'");
                }
            }
            
            if (response.history.length) {
               

                for (let i = 0; i < response.history.length; i++) {
                    
                    const data = response.history[i];
                    if (data["year"] == '.date('Y').'){
                        
                        var highlight = "<tr class=\"info\" >";
                        var number =  data["number"];

                        if (response.maxNumber>0 && response.maxNumber)
                            data["number"] = number + " / " + response.maxNumber;

                        if (response.avviso_numero)
                            data["number"] = "<span style=\"font-weight:bold;\" > <i class=\"fa fa-warning\" ></i> " + data["number"] + "</span>";

                        $("#numero-fe-occupato").html(number);
                        $("#numero-fe-totale").html(response.maxNumber);

                        if (response.avviso_numero) {

                            $("#numero-fe").removeClass("hidden");

                            if (number<response.maxNumber){
                                $("#numero-fe-icon").addClass("fa fa-warning");
                                $("#numero-fe").addClass("alert-warning");
                                $("#numero-fe-text").html("'.tr('in esaurimento').'"); 
                            }
                            else if (number>=response.maxNumber){
                                $("#numero-fe-icon").addClass("fa fa-times");
                                $("#numero-fe").addClass("alert-danger");
                                $("#numero-fe-text").html("'.tr('esaurito').'");
                            }

                        }


                    }else{
                        var highlight = "<tr>";
                    }

                    $("#elenco-fe").prepend(highlight + `
                        <td>` + data["year"] + `</td>
                        <td>` + data["number"] + `</td>
                        <td>` + data["size"] + `</td>
                    </tr>`);
                   
                }
            }
        }
    });
}

function caricaElencoModuli() {
    let container = $("#moduli");

    localLoading(container, true);
    return $.get("'.$structure->fileurl('elenco-moduli.php').'?id_module='.$id_module.'", function(data) {
        container.html(data);
        localLoading(container, false);

        init();
    });
}

function caricaElencoWidget() {
    let container = $("#widget");

    localLoading(container, true);
    return $.get("'.$structure->fileurl('elenco-widget.php').'?id_module='.$id_module.'", function(data) {
        container.html(data);
        localLoading(container, false);

        init();
    });
}

$(document).ready(function() {
    caricaElencoModuli();
    caricaElencoWidget();

    init();
});
</script>';
