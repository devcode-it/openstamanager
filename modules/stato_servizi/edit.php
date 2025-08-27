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

$days = 60;
$limite_scadenze = (new Carbon())->addDays($days);

if (Services::isEnabled()) {
    echo '
<div class="row">
    <div class="col-md-12 col-lg-6">
        <div class="row">
            <div class="col-12 mb-3">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fa fa-cogs mr-2"></i>'.tr('Servizi').'
                        </div>
                    </div>

                    <div class="card-body p-0">';

    $servizi = Services::getServiziAttivi(true);

    if (!$servizi->isEmpty()) {
        echo '
                        <table class="table table-hover table-striped table-sm mb-0">
                            <thead>
                                <tr>
                                    <th width="5%" class="text-center">'.tr('Stato').'</th>
                                    <th width="40%">'.tr('Nome').'</th>
                                    <th width="20%">'.tr('Tipo').'</th>
                                    <th width="25%">'.tr('Scadenza').'</th>
                                    <th width="10%" class="text-center">'.tr('#').'</th>
                                </tr>
                            </thead>

                            <tbody>';
        foreach ($servizi as $servizio) {
            // Verifica che $servizio sia un array e contenga i campi necessari
            if (!is_array($servizio) || !isset($servizio['expiration_at'])) {
                continue;
            }
            $scadenza = Carbon::parse($servizio['expiration_at']);
            $status_class = $scadenza->lessThan(Carbon::now()) ? 'table-danger' : ($scadenza->lessThan($limite_scadenze) ? 'table-warning' : '');
            $status_icon = $scadenza->lessThan(Carbon::now()) ? '<i class="fa fa-times-circle text-danger" title="'.tr('Scaduto').'"></i>' : ($scadenza->lessThan($limite_scadenze) ? '<i class="fa fa-exclamation-triangle text-warning" title="'.tr('In scadenza').'"></i>' : '<i class="fa fa-check-circle text-success" title="'.tr('Attivo').'"></i>');

            // Usa i campi corretti dalla risposta API
            $codice = $servizio['code'] ?? $servizio['name'] ?? 'N/A';
            $nome = $servizio['name'] ?? 'N/A';
            $tipo = $servizio['type'] ?? $servizio['category'] ?? 'N/A';

            echo '
                                <tr class="'.$status_class.'">
                                    <td class="text-center">'.$status_icon.'</td>
                                    <td><strong>'.$codice.'</strong><br><small class="text-muted">'.$nome.'</small></td>
                                    <td><span class="badge badge-secondary">'.$tipo.'</span></td>
                                    <td>'.dateFormat($scadenza).' <br><small class="text-muted">'.$scadenza->diffForHumans().'</small></td>
                                    <td class="text-center">
                                        <input type="checkbox" class="check_rinnova '.($scadenza->lessThan($limite_scadenze) ? '' : 'hide').'" name="rinnova[]" value="'.$codice.'">
                                    </td>
                                </tr>';
        }

        $servizi_in_scadenza = Services::getServiziInScadenza($limite_scadenze);
        $servizi_scaduti = Services::getServiziScaduti();

                    echo '  </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <td colspan="3"><strong>'.tr('Totale servizi: _NUM_', ['_NUM_' => $servizi->count()]).'</strong></td>
                                    <td colspan="2" class="text-right">';

        if (!$servizi_in_scadenza->isEmpty() || !$servizi_scaduti->isEmpty()) {
            echo '<a href="https://marketplace.devcode.it/" target="_blank" id="btn_rinnova" class="btn btn-sm btn-primary"><i class="fa fa-shopping-cart mr-1"></i>'.tr('Rinnova').'</a>';
        }

        echo '                      </td>
                                </tr>
                            </tfoot>';

        echo '
                        </table>';
    } else {
        echo '
                        <div class="alert alert-info m-3">
                            <i class="fa fa-info-circle mr-2"></i>'.tr('Nessun servizio abilitato al momento').'.
                        </div>';
    }

    echo '
                    </div>
                </div>
            </div>

            <!-- Informazioni sulle Risorse API -->
            <div class="col-12 mb-3">
                <div class="card card-success card-outline">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fa fa-cubes mr-2"></i>'.tr('Risorse').'
                        </div>
                    </div>

                    <div class="card-body p-0">';

    // Elaborazione delle risorse API in scadenza
    $risorse_attive = Services::getRisorseAttive(true);
    if (!$risorse_attive->isEmpty()) {
        $risorse_in_scadenza = Services::getRisorseInScadenza($limite_scadenze);
        $risorse_scadute = Services::getRisorseScadute();

        if (!$risorse_in_scadenza->isEmpty() || !$risorse_scadute->isEmpty()) {
            if (!$risorse_scadute->isEmpty()) {
                echo '
                        <div class="alert alert-danger m-3 mb-0">
                            <i class="fa fa-exclamation-triangle mr-2"></i>'.tr('Attenzione, alcune risorse sono scadute o hanno esaurito i crediti:', [
                            '_NUM_' => $risorse_scadute->count(),
                        ]).'
                        </div>';
            }

            if (!$risorse_in_scadenza->isEmpty()) {
                echo '
                        <div class="alert alert-warning m-3 mb-0">
                            <i class="fa fa-clock-o mr-2"></i>'.tr('Attenzione, alcune risorse sono in scadenza o stanno per esaurire i crediti:', [
                            '_NUM_' => $risorse_in_scadenza->count(),
                        ]).'
                        </div>';
            }
        }

        echo '
                        <table class="table table-hover table-striped table-sm mb-0">
                            <thead>
                                <tr>
                                    <th width="5%" class="text-center">'.tr('Stato').'</th>
                                    <th width="40%">'.tr('Nome').'</th>
                                    <th width="20%">'.tr('Crediti').'</th>
                                    <th width="35%">'.tr('Scadenza').'</th>
                                </tr>
                            </thead>

                            <tbody>';

        foreach ($risorse_attive as $servizio) {
            // Verifica che $servizio sia un array e contenga i campi necessari
            if (!is_array($servizio) || !isset($servizio['expiration_at'])) {
                continue;
            }
            $scadenza = Carbon::parse($servizio['expiration_at']);
            $credits_warning = $servizio['credits'] < 100 && $servizio['credits'] !== null;
            $is_expired = $scadenza->lessThan(Carbon::now());
            $is_expiring = $scadenza->lessThan($limite_scadenze);

            $status_class = $is_expired ? 'table-danger' : ($is_expiring || $credits_warning ? 'table-warning' : '');
            $status_icon = $is_expired ? '<i class="fa fa-times-circle text-danger" title="'.tr('Scaduto').'"></i>' : ($is_expiring || $credits_warning ? '<i class="fa fa-exclamation-triangle text-warning" title="'.tr('Attenzione').'"></i>' : '<i class="fa fa-check-circle text-success" title="'.tr('Attivo').'"></i>');

            echo '
                                <tr class="'.$status_class.'">
                                    <td class="text-center">'.$status_icon.'</td>
                                    <td><strong>'.$servizio['name'].'</strong></td>
                                    <td>'.($credits_warning ? '<i class="fa fa-exclamation-triangle text-warning mr-1"></i>' : '').($servizio['credits'] ?? '-').'</td>
                                    <td>'.dateFormat($scadenza).' <br><small class="text-muted">'.$scadenza->diffForHumans().'</small></td>
                                </tr>';
        }

        echo '
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <td colspan="4"><strong>'.tr('Totale risorse: _NUM_', ['_NUM_' => $risorse_attive->count()]).'</strong></td>
                                </tr>
                            </tfoot>
                        </table>';

    } else {
        echo '
                        <div class="alert alert-info m-3">
                            <i class="fa fa-info-circle mr-2"></i>'.tr('Nessuna risorsa Services abilitata').'.
                        </div>';
    }

    echo '
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Colonna destra: Statistiche su Fatture Elettroniche -->
    <div class="col-md-12 col-lg-6">';

    // Il servizio Fatturazione Elettronica deve essere presente per visualizzare le Statistiche su Fatture Elettroniche
    if (Services::isEnabled() && Services::getRisorseAttive()->where('name', 'Fatturazione Elettronica')->count()) {
        echo '
        <!-- Statistiche su Fatture Elettroniche -->
        <div class="card card-info card-outline h-100">
            <div class="card-header">
                <div class="card-title">
                    <i class="fa fa-file-text-o mr-2"></i>'.tr('Statistiche FE').'
                </div>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>

            <div class="card-body">
                <div class="alert hidden" role="alert" id="spazio-fe">
                    <i id="spazio-fe-icon" class=""></i> <span>'.tr('Attenzione, spazio per fatture elettroniche _TEXT_: _NUM_ utilizzati su _TOT_ disponibili', [
            '_TEXT_' => '<span id="spazio-fe-text"></span>',
            '_NUM_' => '<span id="spazio-fe-occupato"></span>',
            '_TOT_' => '<span id="spazio-fe-totale"></span>',
        ]).'.<br>'.tr("Contattare l'assistenza per risolvere il problema").'</span>.
                </div>

                <div class="alert hidden" role="alert" id="numero-fe">
                    <i id="numero-fe-icon" class=""></i> <span>'.tr('Attenzione, numero di fatture elettroniche per l\'annualità _TEXT_: _NUM_ documenti transitati su _TOT_ disponibili', [
            '_TEXT_' => '<span id="numero-fe-text"></span>',
            '_NUM_' => '<span id="numero-fe-occupato"></span>',
            '_TOT_' => '<span id="numero-fe-totale"></span>',
        ]).'.<br>'.tr("Contattare l'assistenza per risolvere il problema").'</span>.
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-sm mb-0">
                        <thead>
                            <tr>
                                <th>'.tr('Anno').'</th>
                                <th class="text-center">
                                    '.tr('Doc.').'
                                    <i class="fa fa-question-circle-o text-muted" title="'.tr('Fatture attive e relative ricevute, fatture passive').'"></i>
                                </th>
                                <th class="text-center">
                                    '.tr('Spazio').'
                                    <i class="fa fa-question-circle-o text-muted" title="'.tr('Fatture attive con eventuali allegati e ricevute, fatture passive con eventuali allegati').'"></i>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="elenco-fe">
                        </tbody>
                        <tfoot>
                            <tr class="table-secondary">
                                <td><strong>'.tr('Totale').'</strong></td>
                                <td class="text-center"><strong id="fe_numero">-</strong></td>
                                <td class="text-center"><strong id="fe_spazio">-</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <script>
        $(document).ready(function (){
            aggiornaStatisticheFE();
        });
        </script>';
    } else {
        echo '
        <div class="card card-secondary card-outline h-100">
            <div class="card-header">
                <div class="card-title">
                    <i class="fa fa-file-text-o mr-2"></i>'.tr('Statistiche FE').'
                </div>
            </div>
            <div class="card-body text-center p-3">
                <i class="fa fa-info-circle fa-2x text-muted mb-2"></i>
                <p class="text-muted mb-0">'.tr('Non disponibili').'<br><small>'.tr('Servizio FE non attivo').'</small></p>
            </div>
        </div>';
    }

    echo '
    </div>
</div>';
}

echo '
<div class="row">
    <div class="col-md-12 col-lg-6">
        <div class="card card-info card-outline">
            <div class="card-header">
                <div class="card-title">
                    <i class="fa fa-puzzle-piece mr-2"></i>'.tr('Moduli disponibili').'
                </div>
            </div>

            <div class="card-body p-0" id="moduli">
            </div>
        </div>
    </div>';

// Widgets + Hooks + Sessioni
echo '
    <div class="col-md-12 col-lg-6">
        <div class="card card-info card-outline mb-4">
            <div class="card-header">
                <div class="card-title">
                    <i class="fa fa-th mr-2"></i>'.tr('Widget disponibili').'
                </div>
            </div>

            <div class="card-body p-0" id="widget">
            </div>
        </div>

        <div class="card card-info card-outline mb-4">
            <div class="card-header">
                <div class="card-title">
                    <i class="fa fa-link mr-2"></i>'.tr('Hooks disponibili').'
                </div>
            </div>

            <div class="card-body p-0" id="hook">
            </div>
        </div>

        <div class="card card-info card-outline mb-4">
            <div class="card-header">
                <div class="card-title">
                    <i class="fa fa-users mr-2"></i>'.tr('Sessioni attive durante ultimi _MINUTI_ minuti', ['_MINUTI_' => setting('Timeout notifica di presenza (minuti)')]).'
                </div>
            </div>

            <div class="card-body p-0" id="sessioni">
            </div>
        </div>

        <div class="card card-info card-outline mb-4">
            <div class="card-header">
                <div class="card-title">
                    <i class="fa fa-history mr-2"></i>'.tr('Ultime operazioni').'
                </div>
            </div>

            <div class="card-body p-0" id="operazioni">
            </div>
        </div>

    </div>
</div>
<script>

$(".check_rinnova").each(function() {

    var len = 0;

    input(this).change(function() {

        len = $("input[type=checkbox]:checked.check_rinnova").length;

        if (len>0){
            $("#btn_rinnova").removeClass("disabled");
        }else{
            $("#btn_rinnova").addClass("disabled");
        }

    });
});

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

            // Informazioni sullo spazio occupato
            $("#spazio-fe-occupato").html(response.spazio_occupato);
            $("#spazio-fe-totale").html(response.spazio_totale);

            if (response.avviso_spazio) {

                $("#spazio-fe").removeClass("hidden");
                $("input.check_rinnova").addClass("disabled");

                response.spazio_occupato = parseFloat(response.spazio_occupato);
                response.spazio_totale = parseFloat(response.spazio_totale);

                if (response.spazio_totale){
                    $("#fe_spazio").html($("#fe_spazio").html() + " / " + response.spazio_totale);

                    if (response.spazio_occupato>response.spazio_totale && response.avviso_spazio){
                        $("#fe_spazio").html("<span style=\"font-weight:bold;\" ><i class=\"fa fa-warning text-warning\" ></i> " + $("#fe_spazio").html() + "</span>");
                    }
                }

                if (response.spazio_occupato<response.spazio_totale){
                    $("#spazio-fe-icon").addClass("fa fa-clock-o");
                    $("#spazio-fe").addClass("alert-warning");
                    $("#spazio-fe-text").html("'.tr('in esaurimento').'");
                }
                else if (response.spazio_occupato>=response.spazio_totale){
                    $("#spazio-fe-icon").addClass("fa fa-warning");
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
                            data["number"] = "<span style=\"font-weight:bold;\" > <i class=\"fa fa-warning text-warning\" ></i> " + data["number"] + "</span>";

                        $("#numero-fe-occupato").html(number);
                        $("#numero-fe-totale").html(response.maxNumber);

                        if (response.avviso_numero) {

                            $("#numero-fe").removeClass("hidden");
                            $("input.check_rinnova").addClass("disabled");

                            if (number<response.maxNumber){
                                $("#numero-fe-icon").addClass("fa fa-clock-o");
                                $("#numero-fe").addClass("alert-warning");
                                $("#numero-fe-text").html("'.tr('in esaurimento').'");
                            }
                            else if (number>=response.maxNumber){
                                $("#numero-fe-icon").addClass("fa fa-warning");
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

function caricaElencoHooks() {
    let container = $("#hook");

    localLoading(container, true);
    return $.get("'.$structure->fileurl('elenco-hooks.php').'?id_module='.$id_module.'", function(data) {
        container.html(data);
        localLoading(container, false);

        init();
    });
}

function caricaElencoSessioni() {
    let container = $("#sessioni");

    localLoading(container, true);
    return $.get("'.$structure->fileurl('elenco-sessioni.php').'?id_module='.$id_module.'", function(data) {
        container.html(data);
        localLoading(container, false);

        init();
    });
}

function caricaElencoOperazioni() {
    let container = $("#operazioni");

    localLoading(container, true);
    return $.get("'.$structure->fileurl('elenco-operazioni.php').'?id_module='.$id_module.'", function(data) {
        container.html(data);
        localLoading(container, false);

        init();
    });
}

function caricaAltreOperazioni(offset, limit) {
    let container = $("#operazioni");
    let currentContent = container.find("table tbody");
    let loadMoreButton = container.find(".text-center");

    // Mostra loading sul pulsante
    loadMoreButton.html("<i class=\"fa fa-refresh fa-spin\"></i> '.tr('Caricamento...').'");

    $.get("'.$structure->fileurl('elenco-operazioni.php').'?id_module='.$id_module.'&offset=" + offset + "&limit=" + limit, function(data) {
        let newData = $(data);
        let newRows = newData.find("tbody tr");
        let newLoadMore = newData.find(".text-center");

        // Aggiungi le nuove righe alla tabella esistente
        if (newRows.length > 0) {
            currentContent.append(newRows);
        }

        // Sostituisci il pulsante "Carica di più" o rimuovilo se non ci sono più dati
        if (newLoadMore.length > 0) {
            loadMoreButton.replaceWith(newLoadMore);
        } else {
            loadMoreButton.remove();
        }

        init();
    });
}

$(document).ready(function() {
    caricaElencoModuli();
    caricaElencoWidget();
    caricaElencoHooks();
    caricaElencoSessioni();
    caricaElencoOperazioni();

    init();
});
</script>';
