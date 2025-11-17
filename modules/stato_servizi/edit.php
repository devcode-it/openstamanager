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
use Models\Cache;
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
                    <i class="fa fa-cogs mr-2"></i>'.tr('Servizi e Risorse OSMCloud').'
                </div>
            </div>

            <div class="card-body p-0">';

    // Recupero servizi e risorse
    $servizi = Services::getServiziAttivi(true)->flatten(1);
    $risorse = Services::getRisorseAttive(true);

    // Combina servizi e risorse in un'unica collezione
    $tutti_elementi = collect();

    // Aggiungi servizi (con flag per identificarli)
    foreach ($servizi as $servizio) {
        $servizio['tipo_elemento'] = 'servizio';
        $tutti_elementi->push($servizio);
    }

    // Aggiungi risorse (con flag per identificarli)
    foreach ($risorse as $risorsa) {
        $risorsa['tipo_elemento'] = 'risorsa';
        $tutti_elementi->push($risorsa);
    }

    if (!$tutti_elementi->isEmpty()) {
        // Calcolo degli elementi in scadenza e scaduti (servizi)
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

        // Calcolo degli elementi in scadenza e scaduti (risorse)
        $risorse_in_scadenza = $risorse->filter(function ($item) use ($limite_scadenze) {
            if (!is_array($item) || !isset($item['expiration_at'])) {
                return false;
            }
            $scadenza = Carbon::parse($item['expiration_at']);
            $credits_warning = isset($item['credits']) && $item['credits'] < 100 && $item['credits'] !== null;
            $is_expiring = $scadenza->greaterThan(Carbon::now()) && $scadenza->lessThan($limite_scadenze);

            return $is_expiring || $credits_warning;
        });

        $risorse_scadute = $risorse->filter(function ($item) {
            if (!is_array($item) || !isset($item['expiration_at'])) {
                return false;
            }
            $scadenza = Carbon::parse($item['expiration_at']);
            $credits_expired = isset($item['credits']) && $item['credits'] < 0;
            $is_expired = $scadenza->lessThan(Carbon::now());

            return $is_expired || $credits_expired;
        });

        // Totali unificati
        $totale_scaduti = $servizi_scaduti->count() + $risorse_scadute->count();
        $totale_in_scadenza = $servizi_in_scadenza->count() + $risorse_in_scadenza->count();

        // Messaggi di avviso unificati
        if ($totale_scaduti > 0) {
            echo '
                <div class="alert alert-danger m-3 mb-0">
                    <i class="fa fa-exclamation-triangle mr-2"></i>'.tr('Attenzione, alcuni elementi sono scaduti o hanno esaurito i crediti: _NUM_', [
                '_NUM_' => $totale_scaduti,
            ]).'
                </div>';
        }

        if ($totale_in_scadenza > 0) {
            echo '
                <div class="alert alert-warning m-3 mb-0">
                    <i class="fa fa-clock-o mr-2"></i>'.tr('Attenzione, alcuni elementi sono in scadenza o stanno per esaurire i crediti: _NUM_', [
                '_NUM_' => $totale_in_scadenza,
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
                            <th width="20%" class="text-center">'.tr('Info').'</th>
                        </tr>
                    </thead>

                    <tbody>';

        // Prima mostra i servizi
        foreach ($servizi as $servizio) {
            $scadenza = Carbon::parse($servizio['data_conclusione']);
            $is_expired = $scadenza->lessThan(Carbon::now());
            $is_expiring = $scadenza->greaterThan(Carbon::now()) && $scadenza->lessThan($limite_scadenze);

            $spazio_utilizzato = FileSystem::folderSize(base_dir(), ['htaccess']) / (1024 ** 3);
            $utenti_attivi = User::where('enabled', 1)->count();

            // Calcolo percentuale spazio utilizzato e determinazione livello di allerta
            $spazio_percentuale = ($servizio['spazio_limite'] && $servizio['spazio_limite'] > 0) ? ($spazio_utilizzato / $servizio['spazio_limite']) * 100 : 0;
            $spazio_warning = ($servizio['spazio_limite'] && $servizio['spazio_limite'] > 0) && $spazio_percentuale >= 80;
            $spazio_danger = ($servizio['spazio_limite'] && $servizio['spazio_limite'] > 0) && $spazio_percentuale >= 100;

            // Determinazione livello di allerta per utenti
            $utenti_warning = ($servizio['utenti_limite'] && $servizio['utenti_limite'] > 0) && $utenti_attivi >= ($servizio['utenti_limite'] - 1);
            $utenti_danger = ($servizio['utenti_limite'] && $servizio['utenti_limite'] > 0) && $utenti_attivi >= $servizio['utenti_limite'];

            // Determinazione dello stato
            $status_class = $is_expired ? 'table-danger' : ($is_expiring ? 'table-warning' : '');
            $status_icon = $is_expired ? '<i class="fa fa-times-circle text-danger" title="'.tr('Scaduto/Esaurito').'"></i>' :
                          ($is_expiring ? '<i class="fa fa-exclamation-triangle text-warning" title="'.tr('Attenzione').'"></i>' :
                          '<i class="fa fa-check-circle text-success" title="'.tr('Attivo').'"></i>');

            // Determinazione classe e icona per lo spazio
            if ($spazio_danger) {
                $spazio_class = 'danger';
                $spazio_icon = '<i class="fa fa-exclamation-triangle" style="font-size: 0.65rem; margin-left: 4px;" title="'.tr('Spazio esaurito').'"></i>';
            } elseif ($spazio_warning) {
                $spazio_class = 'warning';
                $spazio_icon = '<i class="fa fa-exclamation-triangle" style="font-size: 0.65rem; margin-left: 4px;" title="'.tr('Spazio in esaurimento').'"></i>';
            } else {
                $spazio_class = 'secondary';
                $spazio_icon = '';
            }

            // Determinazione classe e icona per gli utenti
            if ($utenti_danger) {
                $utenti_class = 'danger';
                $utenti_icon = '<i class="fa fa-exclamation-triangle" style="font-size: 0.65rem; margin-left: 4px;" title="'.tr('Limite utenti raggiunto').'"></i>';
            } elseif ($utenti_warning) {
                $utenti_class = 'warning';
                $utenti_icon = '<i class="fa fa-exclamation-triangle" style="font-size: 0.65rem; margin-left: 4px;" title="'.tr('Limite utenti quasi raggiunto').'"></i>';
            } else {
                $utenti_class = 'secondary';
                $utenti_icon = '';
            }
            echo '
                        <tr class="'.$status_class.'">
                            <td class="text-center">'.$status_icon.'</td>
                            <td><strong>'.$servizio['codice'].'</strong><br><small class="text-muted">'.$servizio['nome'].'</small></td>
                            <td><span class="badge badge-primary">'.$servizio['sottocategoria'].'</span><br><small class="text-muted">
                            <td>'.dateFormat($scadenza).' <br><small class="text-muted">'.$scadenza->diffForHumans().'</small></td>
                            <td class="text-center">
                                '.($servizio['spazio_limite'] ? '<div class="mb-1"><span class="badge badge-'.$spazio_class.' d-inline-flex align-items-center" style="font-size: 0.7rem; padding: 0.25rem 0.5rem; line-height: 1.2;"><i class="fa fa-database" style="font-size: 0.65rem; margin-right: 4px;"></i>'.numberFormat($spazio_utilizzato,1).' / '.numberFormat($servizio['spazio_limite'],1).' '.tr('GB').$spazio_icon.'</span></div>' : '').'
                            </td>
                        </tr>';
        }

        // Poi mostra le risorse
        foreach ($risorse as $elemento) {
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
            $codice = $elemento['code'];
            $nome = $elemento['name'];

            // Gestione crediti: mostra solo se presenti, altrimenti "-"
            $crediti_display = $has_credits ? ($elemento['credits'] ?? '-') : '∞';
            $credits_warning_icon = ($credits_warning || $credits_expired) ? '<i class="fa fa-exclamation-triangle" style="font-size: 0.7rem; margin-left: 3px;"></i>' : '';

            $max_size = null;
            if ($elemento['name'] == 'Fatturazione Elettronica') {
                $info = Cache::where('name', 'Informazioni su spazio FE')->first();
                $max_size = $info->content['maxSize'];
            }

            echo '
                        <tr class="'.$status_class.'">
                            <td class="text-center">'.$status_icon.'</td>
                            <td><strong>'.$nome.'</strong><br><small class="text-muted">'.$codice.'</small></td>
                            <td><span class="badge badge-info">'.tr('Risorsa').'</span></td>
                            <td>'.dateFormat($scadenza).' <br><small class="text-muted">'.$scadenza->diffForHumans().'</small></td>
                            <td class="text-center">
                                <span class="badge badge-'.$credits_class.' d-inline-flex align-items-center" style="font-size: 0.75rem;">'.$crediti_display.' '.tr('Crediti').' '.$credits_warning_icon.'</span>';
                                if ($max_size) {
                                    echo '<br><span class="badge badge-secondary d-inline-flex align-items-center" style="font-size: 0.75rem;">'.$max_size.' '.tr('MB').'</span>';
                                }
                            echo '
                            </td>
                        </tr>';
        }

        echo '
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <td colspan="4">
                                <strong>'.tr('Totale elementi: _NUM_ (_SERVIZI_ servizi, _RISORSE_ risorse)', [
            '_NUM_' => $tutti_elementi->count(),
            '_SERVIZI_' => $servizi->count(),
            '_RISORSE_' => $risorse->count(),
        ]).'</strong>
                            </td>
                            <td class="text-right">';
        if ($totale_in_scadenza > 0 || $totale_scaduti > 0) {
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
                    <i class="fa fa-info-circle mr-2"></i>'.tr('Nessun servizio o risorsa OSMCloud abilitato al momento').'.
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
            }

            response.spazio_occupato = parseFloat(response.spazio_occupato);
            response.spazio_totale = parseFloat(response.spazio_totale);

            if (response.spazio_totale){
                $("#fe_spazio").html($("#fe_spazio").html() + " / " + response.spazio_totale + " MB");

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

    // Mostra loading
    localLoading(container, true);

    $.get("'.$structure->fileurl('elenco-operazioni.php').'?id_module='.$id_module.'&offset=" + offset + "&limit=" + limit, function(data) {
        // Sostituisci completamente il contenuto del container
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
    caricaElencoOperazioni();

    init();
});
</script>';
