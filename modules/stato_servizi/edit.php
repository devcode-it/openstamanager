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
use Models\User;
use Util\FileSystem;

include_once __DIR__.'/../../core.php';

$days = 60;
$limite_scadenze = (new Carbon())->addDays($days);

if (Services::isEnabled()) {
    echo '
<div class="row">
    <div class="col-md-12 col-lg-6">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <div class="card-title">
                    <i class="fa fa-cogs mr-2"></i>'.tr('Servizi OSMCloud').'
                </div>
            </div>

            <div class="card-body p-0">';
    $servizi = Services::getServiziAttivi(true)->flatten(1);
    if (!$servizi->isEmpty()) {
        // Calcolo degli elementi in scadenza e scadut
        $servizi_in_scadenza = $servizi->filter(function ($item) use ($limite_scadenze) {
            if (!is_array($item) || !isset($item['data_conclusione'])) {
                return false;
            }
            $scadenza = Carbon::parse($item['data_conclusione']);
            $is_expiring = $scadenza->greaterThan(Carbon::now()) && $scadenza->lessThan($limite_scadenze);

            return $is_expiring;
        });

        $servizi_scaduti = $servizi->filter(function ($item) {
            if (!is_array($item) || !isset($item['data_conclusione'])) {
                return false;
            }
            $scadenza = Carbon::parse($item['data_conclusione']);
            $is_expired = $scadenza->lessThan(Carbon::now());

            return $is_expired;
        });

        // Messaggi di avviso
        if (!$servizi_scaduti->isEmpty()) {
            echo '
                <div class="alert alert-danger m-3 mb-0">
                    <i class="fa fa-exclamation-triangle mr-2"></i>'.tr('Attenzione, alcuni elementi sono scaduti: _NUM_', [
                        '_NUM_' => $servizi_scaduti->count(),
                    ]).'
                </div>';
        }

        if (!$servizi_in_scadenza->isEmpty()) {
            echo '
                <div class="alert alert-warning m-3 mb-0">
                    <i class="fa fa-clock-o mr-2"></i>'.tr('Attenzione, alcuni elementi sono in scadenza: _NUM_', [
                        '_NUM_' => $servizi_in_scadenza->count(),
                    ]).'
                </div>';
        }

        echo '
                <table class="table table-hover table-striped table-sm mb-0">
                    <thead>
                        <tr>
                            <th width="5%" class="text-center">'.tr('Stato').'</th>
                            <th>'.tr('Nome').'</th>
                            <th width="15%">'.tr('Tipo').'</th>
                            <th width="15%">'.tr('Scadenza').'</th>
                            <th width="20%" class="text-center"></th>
                        </tr>
                    </thead>

                    <tbody>';
        foreach ($servizi as $servizio) {
            $scadenza = Carbon::parse($servizio['data_conclusione']);
            $is_expired = $scadenza->lessThan(Carbon::now());
            $is_expiring = $scadenza->greaterThan(Carbon::now()) && $scadenza->lessThan($limite_scadenze);

            $spazio_utilizzato = FileSystem::folderSize(base_dir(), ['htaccess']) / (1024 ** 3);
            $utenti_attivi = User::where('enabled', 1)->count();
            $spazio_warning = $servizio['spazio_limite'] && $spazio_utilizzato >= $servizio['spazio_limite'];
            $utenti_warning = $servizio['utenti_limite'] && $utenti_attivi >= $servizio['utenti_limite'];

            // Determinazione dello stato
            $status_class = $is_expired ? 'table-danger' : ($is_expiring ? 'table-warning' : '');
            $status_icon = $is_expired ? '<i class="fa fa-times-circle text-danger" title="'.tr('Scaduto/Esaurito').'"></i>' :
                          ($is_expiring ? '<i class="fa fa-exclamation-triangle text-warning" title="'.tr('Attenzione').'"></i>' :
                          '<i class="fa fa-check-circle text-success" title="'.tr('Attivo').'"></i>');

            $spazio_class = ($spazio_warning ? 'danger' : 'secondary');
            $spazio_icon = ($spazio_warning ? '<i class="fa fa-exclamation-triangle" title="'.tr('Attenzione').'"></i>' : '');
            $utenti_class = ($utenti_warning ? 'danger' : 'secondary');
            $utenti_icon = ($utenti_warning ? '<i class="fa fa-exclamation-triangle" title="'.tr('Attenzione').'"></i>' : '');
            echo '
                        <tr class="'.$status_class.'">
                            <td class="text-center">'.$status_icon.'</td>
                            <td><strong>'.$servizio['codice'].'</strong><br><small class="text-muted">'.$servizio['nome'].'</small></td>
                            <td><span class="badge badge-info">'.$servizio['sottocategoria'].'</span></td>
                            <td>'.dateFormat($scadenza).' <br><small class="text-muted">'.$scadenza->diffForHumans().'</small></td>
                            <td class="text-center">
                                '.($servizio['spazio_limite'] ? '<span class="badge badge-'.$spazio_class.'"><i class="fa fa-database mr-1"></i> '.numberFormat($spazio_utilizzato,1).' / '.numberFormat($servizio['spazio_limite'],1).' '.tr('GB').' '.$spazio_icon.'</span>' : '').'
                                '.($servizio['utenti_limite'] ? '<br><span class="badge badge-'.$utenti_class.'"><i class="fa fa-users mr-1"></i> '.$utenti_attivi.' / '.$servizio['utenti_limite'].' '.tr('utenti').' '.$utenti_icon.'</span>' : '').'
                            </td>
                        </tr>';
        }

         // Conteggio servizi e risorse
        $count_servizi = $servizi->filter(fn ($item) => !isset($item['credits']))->count();
        echo '
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <td colspan="4">
                                <strong>'.tr('Totale elementi: _NUM_', ['_NUM_' => $servizi->count()]).'</strong>
                            </td>
                            <td colspan="2" class="text-right">';
                            if (!$servizi_in_scadenza->isEmpty() || !$servizi_scaduti->isEmpty()) {
                                echo '<a href="https://marketplace.devcode.it/" target="_blank" id="btn_rinnova" class="btn btn-sm btn-warning"><i class="fa fa-shopping-cart mr-1"></i>'.tr('Rinnova').'</a>';
                            }
                            echo '
                            </td>
                        </tr>
                    </tfoot>
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

        <div class="card card-primary card-outline">
            <div class="card-header">
                <div class="card-title">
                    <i class="fa fa-cubes mr-2"></i>'.tr('Risorse').'
                </div>
            </div>

            <div class="card-body p-0">';

    // Recupero di tutti i servizi e risorse attivi
    $servizi = Services::getRisorseAttive(true);
    if (!$servizi->isEmpty()) {
        // Calcolo degli elementi in scadenza e scaduti
        $servizi_in_scadenza = $servizi->filter(function ($item) use ($limite_scadenze) {
            if (!is_array($item) || !isset($item['expiration_at'])) {
                return false;
            }
            $scadenza = Carbon::parse($item['expiration_at']);
            $credits_warning = isset($item['credits']) && $item['credits'] < 100 && $item['credits'] !== null;
            $is_expiring = $scadenza->greaterThan(Carbon::now()) && $scadenza->lessThan($limite_scadenze);

            return $is_expiring || $credits_warning;
        });

        $servizi_scaduti = $servizi->filter(function ($item) {
            if (!is_array($item) || !isset($item['expiration_at'])) {
                return false;
            }
            $scadenza = Carbon::parse($item['expiration_at']);
            $credits_expired = isset($item['credits']) && $item['credits'] < 0;
            $is_expired = $scadenza->lessThan(Carbon::now());

            return $is_expired || $credits_expired;
        });

        // Messaggi di avviso
        if (!$servizi_scaduti->isEmpty()) {
            echo '
                <div class="alert alert-danger m-3 mb-0">
                    <i class="fa fa-exclamation-triangle mr-2"></i>'.tr('Attenzione, alcuni elementi sono scaduti o hanno esaurito i crediti: _NUM_', [
                        '_NUM_' => $servizi_scaduti->count(),
                    ]).'
                </div>';
        }

        if (!$servizi_in_scadenza->isEmpty()) {
            echo '
                <div class="alert alert-warning m-3 mb-0">
                    <i class="fa fa-clock-o mr-2"></i>'.tr('Attenzione, alcuni elementi sono in scadenza o stanno per esaurire i crediti: _NUM_', [
                        '_NUM_' => $servizi_in_scadenza->count(),
                    ]).'
                </div>';
        }

        echo '
                <table class="table table-hover table-striped table-sm mb-0">
                    <thead>
                        <tr>
                            <th width="5%" class="text-center">'.tr('Stato').'</th>
                            <th>'.tr('Nome').'</th>
                            <th width="15%">'.tr('Tipo').'</th>
                            <th width="15%">'.tr('Scadenza').'</th>
                            <th width="20%" class="text-center">'.tr('#').'</th>
                        </tr>
                    </thead>

                    <tbody>';
        foreach ($servizi as $elemento) {
            // Verifica che $elemento sia un array e contenga i campi necessari
            if (!is_array($elemento) || !isset($elemento['expiration_at'])) {
                continue;
            }

            $scadenza = Carbon::parse($elemento['expiration_at']);
            $has_credits = isset($elemento['credits']);
            $credits_warning = $has_credits && $elemento['credits'] < 100 && $elemento['credits'] !== null;
            $credits_expired = $has_credits && $elemento['credits'] < 0;
            $is_expired = $scadenza->lessThan(Carbon::now());
            $is_expiring = $scadenza->greaterThan(Carbon::now()) && $scadenza->lessThan($limite_scadenze);

            // Determinazione dello stato
            $status_class = ($is_expired || $credits_expired) ? 'table-danger' : (($is_expiring || $credits_warning) ? 'table-warning' : '');
            $status_icon = ($is_expired || $credits_expired) ? '<i class="fa fa-times-circle text-danger" title="'.tr('Scaduto/Esaurito').'"></i>' :
                          (($is_expiring || $credits_warning) ? '<i class="fa fa-exclamation-triangle text-warning" title="'.tr('Attenzione').'"></i>' :
                          '<i class="fa fa-check-circle text-success" title="'.tr('Attivo').'"></i>');

            $credits_class = ($credits_expired ? 'danger' : ($credits_warning ? 'warning' : 'secondary'));
            $credits_icon = ($credits_warning || $credits_expired ? '<i class="fa fa-exclamation-triangle" title="'.tr('Attenzione').'"></i>' : '');


            // Campi dell'elemento
            $codice = $elemento['code'] ?? $elemento['name'] ?? 'N/A';
            $nome = $elemento['name'] ?? 'N/A';
            $tipo = $elemento['type'] ?? $elemento['category'] ?? 'N/A';

            // Gestione crediti: mostra solo se presenti, altrimenti "-"
            $crediti_display = $has_credits ?
                ($credits_warning || $credits_expired ? '<i class="fa fa-exclamation-triangle text-warning mr-1"></i>' : '').($elemento['credits'] ?? '-') :
                '<span class="text-muted">-</span>';

            echo '
                        <tr class="'.$status_class.'">
                            <td class="text-center">'.$status_icon.'</td>
                            <td><strong>'.$codice.'</strong><br><small class="text-muted">'.$nome.'</small></td>
                            <td><span class="badge badge-info">'.$tipo.'</span></td>
                            <td>'.dateFormat($scadenza).' <br><small class="text-muted">'.$scadenza->diffForHumans().'</small></td>
                            <td class="text-center">
                                <span class="badge badge-'.$credits_class.'">'.$crediti_display.' '.tr('Crediti').' '.$credits_icon.'</span>
                            </td>
                        </tr>';
        }

        // Conteggio servizi e risorse
        $count_servizi = $servizi->filter(fn ($item) => !isset($item['credits']))->count();

        echo '
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <td colspan="4">
                                <strong>'.tr('Totale elementi: _NUM_', ['_NUM_' => $servizi->count()]).'</strong>
                            </td>
                            <td colspan="2" class="text-right">';
                            if (!$servizi_in_scadenza->isEmpty() || !$servizi_scaduti->isEmpty()) {
                                echo '<a href="https://marketplace.devcode.it/" target="_blank" id="btn_rinnova" class="btn btn-sm btn-warning"><i class="fa fa-shopping-cart mr-1"></i>'.tr('Rinnova').'</a>';
                            }
                            echo '
                            </td>
                        </tr>
                    </tfoot>
                </table>';

    } else {
        echo '
                <div class="alert alert-info m-3">
                    <i class="fa fa-info-circle mr-2"></i>'.tr('Nessun servizio OSMCloud abilitato al momento').'.
                </div>';
    }
    echo '
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
                                <th class="text-right pr-3">
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
                                <td class="text-right pr-3"><strong id="fe_numero">-</strong></td>
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

            // Informazioni sullo spazio occupato
            $("#spazio-fe-occupato").html(response.spazio_occupato);
            $("#spazio-fe-totale").html(response.spazio_totale);

            if (response.avviso_spazio) {

                $("#spazio-fe").removeClass("hidden");

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
                        <td class="text-right pr-3">` + data["number"] + `</td>
                        <td class="text-center">` + data["size"] + `</td>
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



$(document).ready(function() {
    caricaElencoModuli();
    caricaElencoWidget();
    caricaElencoHooks();
    caricaElencoSessioni();

    init();
});
</script>';
