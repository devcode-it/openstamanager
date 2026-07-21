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

include_once __DIR__.'/../../core.php';

use Modules\Partitario\Movimento;
use Modules\Partitario\PianoDeiConti1;
use Modules\Partitario\PianoDeiConti2;
use Modules\Partitario\PianoDeiConti3;

$period_start = $_SESSION['period_start'];
$period_end = $_SESSION['period_end'];

$bilancio_gia_aperto = Movimento::where('is_apertura', 1)
    ->whereBetween('data', [$period_start, $period_end])
    ->exists();

$msg = tr('Sei sicuro di voler aprire il bilancio?');
$btn_class = 'btn-info';

if ($bilancio_gia_aperto) {
    $msg .= ' '.tr('I movimenti di apertura già esistenti verranno annullati e ricreati').'.';
    $btn_class = 'btn-default';
}

echo '
<div class="row">
    <div class="offset-md-4 col-md-3">
            <input type="text" class="form-control form-control-lg text-center" id="input-cerca" placeholder="'.tr('Cerca').'...">
    </div>

    <div class="col-md-1">
        <button type="button" class="btn btn-lg btn-primary" id="button-search">
            <i class="fa fa-search"></i> '.tr('Cerca').'
        </button>
    </div>

    <div class="col-md-4 text-right">
        <button type="button" class="btn btn-lg '.$btn_class.'" data-op="apri-bilancio" data-title="'.tr('Apertura bilancio').'" data-backto="record-list" data-msg="'.$msg.'" data-button="'.tr('Riprendi saldi').'" data-class="btn btn-lg btn-warning" onclick="message( this );">
            <i class="fa fa-folder-open"></i> '.tr('Apertura bilancio').'
        </button>
    </div>
</div>';

$primo_livello = PianoDeiConti1::with(['secondiLivelli' => function ($q) {
    $q->orderBy('numero', 'asc');
}])->orderBy('id', 'desc')->get();

foreach ($primo_livello as $conto_primo) {
    $totale_attivita = [];
    $totale_passivita = [];

    $costi = [];
    $ricavi = [];

    $titolo = $conto_primo->descrizione == 'Economico' ? tr('Conto economico') : tr('Stato patrimoniale');

    echo '
<hr>
<div class="card conto1">
    <div class="card-header">
        <h3 class="card-title">
            '.$titolo.' 
            <button type="button" class="btn btn-xs btn-primary" data-card-widget="tooltip" title="'.tr('Aggiungi un nuovo conto...').'" onclick="aggiungiConto('.$conto_primo->id.', 2)">
                <i class="fa fa-plus-circle"></i>
            </button>
        </h3>
    </div>

    <div class="card-body">
        <!-- Intestazione colonne -->
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>'.tr('Descrizione').'</th>
                        <th width="10%" class="text-center">'.tr('Importo').'</th>';
    if ($conto_primo->descrizione == 'Economico') {
        echo '
                        <th width="10%" class="text-center">'.tr('Importo reddito').'</th>';
    } else {
        echo '
                        <th width="10%"></th>';
    }
    echo '
                        <th width="8%" class="text-center"></th>
                    </tr>
                </thead>
                <tbody>';

    $secondo_livello = $conto_primo->secondiLivelli;

    foreach ($secondo_livello as $conto_secondo) {
        $conto2_id = $conto_secondo->id;

        if ($conto_primo->descrizione == 'Economico') {
            $totale_conto2 = Movimento::whereHas('conto', function ($q) use ($conto2_id) {
                $q->where('id_piano_dei_conti2', $conto2_id);
            })->whereBetween('data', [$period_start, $period_end])->sum('totale');

            $totale_reddito2 = Movimento::whereHas('conto', function ($q) use ($conto2_id) {
                $q->where('id_piano_dei_conti2', $conto2_id);
            })->where(function ($q) use ($period_start, $period_end) {
                $q->whereBetween('data', [$period_start, $period_end])
                    ->orWhere(function ($q2) use ($period_start, $period_end) {
                        $q2->whereNotNull('data_inizio_competenza')
                            ->whereNotNull('data_fine_competenza')
                            ->where(function ($q3) use ($period_start, $period_end) {
                                $q3->where('data_fine_competenza', '>=', $period_start)
                                    ->where('data_inizio_competenza', '<=', $period_end);
                            })
                            ->orWhere(function ($q3) use ($period_start, $period_end) {
                                $q3->where('data_inizio_competenza', '<', $period_start)
                                    ->where('data_fine_competenza', '>', $period_end);
                            })
                            ->orWhere(function ($q3) use ($period_start, $period_end) {
                                $q3->where('data_inizio_competenza', '<=', $period_end)
                                    ->where('data_inizio_competenza', '>=', $period_start);
                            })
                            ->orWhere(function ($q3) use ($period_start, $period_end) {
                                $q3->where('data_fine_competenza', '>=', $period_start)
                                    ->where('data_fine_competenza', '<=', $period_end);
                            });
                    });
            })->get()->sum(function ($m) use ($period_start, $period_end) {
                if ($m->data_inizio_competenza === null || $m->data_fine_competenza === null) {
                    return $m->totale_reddito;
                }
                $inizio = max($m->data_inizio_competenza, $period_start);
                $fine = min($m->data_fine_competenza, $period_end);
                $giorni_periodo = $inizio->diffInDays($fine) + 1;
                $giorni_totali = $m->data_inizio_competenza->diffInDays($m->data_fine_competenza) + 1;
                return $m->totale_reddito * ($giorni_periodo / $giorni_totali);
            });
        } else {
            $totale_conto2 = Movimento::whereHas('conto', function ($q) use ($conto2_id) {
                $q->where('id_piano_dei_conti2', $conto2_id);
            })->whereBetween('data', [$period_start, $period_end])->sum('totale');
            $totale_reddito2 = 0;
        }

        echo '
                    <tr class="conto2" id="conto2-'.$conto_secondo->id.'">
                        <td>
                            <h5>
                                <button type="button" id="conto2-'.$conto_secondo->id.'" class="btn btn-default btn-xs plus-btn search"><i class="fa fa-plus"></i></button>
                                <span class="clickable" id="conto2-'.$conto_secondo->id.'">
                                    <b>'.$conto_secondo->numero.' '.$conto_secondo->descrizione.'</b>
                                </span>
                                <div id="conto2_'.$conto_secondo->id.'" style="display:none;"></div>
                            </h5>
                        </td>

                        <td class="text-right">
                            <b>'.moneyFormat($totale_conto2, 2).'</b>
                        </td>';

        if ($conto_primo->descrizione == 'Economico') {
            echo '
                        <td class="text-right">
                            <b>'.moneyFormat($totale_reddito2, 2).'</b>
                        </td>';
        } else {
            echo '
                        <td></td>';
        }

        echo '
                        <td class="text-right">
                            '.Prints::getLink('Mastrino', $conto_secondo->id, 'btn-info btn-xs', '', null, 'lev=2').'

                            <button type="button" class="btn btn-warning btn-xs" onclick="modificaConto('.$conto_secondo->id.', 2)">
                                <i class="fa fa-edit"></i>
                            </button>

                            <button type="button" class="btn btn-xs btn-primary" data-card-widget="tooltip" title="'.tr('Aggiungi un nuovo conto...').'" onclick="aggiungiConto('.$conto_secondo->id.')">
                                <i class="fa fa-plus-circle"></i>
                            </button>

                            <button type="button" class="btn btn-danger btn-xs" onclick="eliminaConto('.$conto_secondo->id.', 2)">
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
                    </tr>';
        if ($totale_conto2) {
            if ($conto_primo->descrizione == 'Patrimoniale') {
                if ($totale_conto2 > 0) {
                    $totale_attivita[] = abs($totale_conto2);
                } else {
                    $totale_passivita[] = abs($totale_conto2);
                }
            } else {
                if ($totale_conto2 > 0) {
                    $totale_ricavi[] = abs($totale_conto2);
                } else {
                    $totale_costi[] = abs($totale_conto2);
                }
            }
        }
        if ($totale_reddito2) {
            if ($conto_primo->descrizione == 'Economico') {
                if ($totale_reddito2 > 0) {
                    $totale_ricavi_reddito[] = abs($totale_reddito2);
                } else {
                    $totale_costi_reddito[] = abs($totale_reddito2);
                }
            }
        }

        $totale_conto2 = 0;
        $totale_reddito2 = 0;
    }

    echo '
            </tbody>
        </table>
    </div>

        <table class="table table-sm table-hover totali">';

    if ($conto_primo->descrizione == 'Patrimoniale') {
        $attivita = abs(sum($totale_attivita));
        $passivita = abs(sum($totale_passivita));
        $utile_perdita = abs(sum($totale_ricavi)) - abs(sum($totale_costi));
        if ($utile_perdita < 0) {
            $pareggio1 = $attivita + abs($utile_perdita);
            $pareggio2 = abs($passivita);
        } else {
            $pareggio1 = $attivita;
            $pareggio2 = abs($passivita) + abs($utile_perdita);
        }

        echo '
            <tr>
                <th class="text-right">
                    <big>'.tr('Totale attività').':</big>
                </th>
                <td class="text-right" width="150">
                    <big>'.moneyFormat($attivita, 2).'</big>
                </td>
                <td width="50"></td>';

        echo '
                <th class="text-right">
                    <big>'.tr('Passività').':</big>
                </th>
                <td class="text-right" width="150">
                    <big>'.moneyFormat($passivita, 2).'</big>
                </td>
                <td width="5%"></td>
            </tr>';

        if ($utile_perdita < 0) {
            echo '
            <tr>
                <th class="text-right">
                    <big>'.tr("Perdita d'esercizio").':</big>
                </th>
                <td class="text-right">
                    <big>'.moneyFormat(sum($utile_perdita), 2).'</big>
                </td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>';
        } else {
            echo '
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <th class="text-right">
                    <big>'.tr('Utile').':</big>
                </th>
                <td class="text-right">
                    <big>'.moneyFormat(sum($utile_perdita), 2).'</big>
                </td>
                <td></td>
            </tr>';
        }

        echo '
            <tr>
                <th class="text-right">
                    <big>'.tr('Totale a pareggio').':</big>
                </th>
                <td class="text-right" width="150">
                    <big>'.moneyFormat(sum($pareggio1), 2).'</big>
                </td>
                <td width="50"></td>

                <th class="text-right">
                    <big>'.tr('Totale a pareggio').':</big>
                </th>
                <td class="text-right" width="150">
                    <big>'.moneyFormat(sum($pareggio2), 2).'</big>
                </td>
                <td></td>
            </tr>';
    } else {
        echo '
            <tr>
                <th class="text-right">
                    <big>'.tr('Ricavi').':</big>
                </th>
                <td class="text-right" width="150">
                    <big>'.moneyFormat(sum($totale_ricavi), 2).'</big>
                </td>
                <td class="text-right" width="10%">
                    <big>'.moneyFormat(sum($totale_ricavi_reddito), 2).'</big>
                </td>
                <td width="5%"></td>
            </tr>

            <tr>
                <th class="text-right">
                    <big>'.tr('Costi').':</big>
                </th>
                <td class="text-right" width="150">
                    <big>'.moneyFormat(sum($totale_costi), 2).'</big>
                </td>
                <td class="text-right">
                    <big>'.moneyFormat(sum($totale_costi_reddito), 2).'</big>
                </td>
                <td></td>
            </tr>

            <tr class="totali">
                <th class="text-right">
                    <big>'.tr('Utile/perdita').':</big>
                </th>
                <td class="text-right" width="150">
                    <big>'.moneyFormat(sum($totale_ricavi) - abs(sum($totale_costi)), 2).'</big>
                </td>
                <td class="text-right">
                    <big>'.moneyFormat(sum($totale_ricavi_reddito) - abs(sum($totale_costi_reddito)), 2).'</big>
                </td>
                <td></td>
            </tr>';
    }

    echo '
        </table>
    </div>
</div>';
}

$bilancio_gia_chiuso = Movimento::where('is_chiusura', 1)
    ->whereBetween('data', [$period_start, $period_end])
    ->exists();

$msg = tr('Sei sicuro di voler chiudere il bilancio?');
$btn_class = 'btn-info';

if ($bilancio_gia_chiuso) {
    $msg .= ' '.tr('I movimenti di chiusura già esistenti verranno annullati e ricreati').'.';
    $btn_class = 'btn-default';
}

echo '
<div class="text-right">
    <button type="button" class="btn btn-lg '.$btn_class.'" data-op="chiudi-bilancio" data-title="'.tr('Chiusura bilancio').'" data-backto="record-list" data-msg="'.$msg.'" data-button="'.tr('Chiudi bilancio').'" data-class="btn btn-lg btn-primary" onclick="message( this );">
        <i class="fa fa-folder"></i> '.tr('Chiusura bilancio').'
    </button>
</div>

<div class="clearfix"></div>
<hr>

<script>
    $(document).ready(function() {
        $("#input-cerca").keyup(function(key) {
            if (key.which == 13) {
                $("#button-search").click();
            }
        });

        $("button[id^=conto2-], span[id^=conto2-]").each(function() {
            $(this).on("click", function() {
                let id_conto = $(this).attr("id").split("-").pop();
                let tr = $("#conto2-" + id_conto);
                let conto3 = $("#conto2_" + id_conto);
                
                if (!$("#conto3-row-" + id_conto).length) {
                    tr.after(\'<tr id="conto3-row-\' + id_conto + \'" class="conto3-container"><td colspan="4"><div id="conto3-content-\' + id_conto + \'"></div></td></tr>\');
                    conto3.appendTo("#conto3-content-" + id_conto);
                }
                
                $("#conto3-row-" + id_conto).toggle();
                
                if(!conto3.html()) {
                    $("#conto3-row-" + id_conto).show();
                    caricaConti3("conto2_" + id_conto, id_conto);
                }

                tr.find(".plus-btn i").toggleClass("fa-plus").toggleClass("fa-minus");
            });
        });
    });

    function aggiungiConto(id_conto, level = 3) {
        openModal("'.tr('Nuovo conto').'", "'.$structure->fileurl('add_conto.php').'?id=" + id_conto + "&lvl=" + level);
    }

    function modificaConto(id_conto, level = 3) {
        launch_modal("'.tr('Modifica conto').'", "'.$structure->fileurl('edit_conto.php').'?id=" + id_conto + "&lvl=" + level);
    }

    function caricaConti3(selector, id_conto) {
        $("#main_loading").show();

        $.ajax({
            url: "'.$structure->fileurl('dettagli_conto2.php').'",
            type: "get",
            data: {
                id_module: globals.id_module,
                id_conto: id_conto,
            },
            success: function(data){
               $("#" + selector).html(data)
                    .slideToggle();

               $("#main_loading").fadeOut();
            }
        });
    }

    function aggiornaReddito(id_conto){
        openModal("'.tr('Ricalcola importo deducibile').'", "'.$structure->fileurl('aggiorna_reddito.php').'?id=" + id_conto)
    }

    function eliminaConto(id_conto, level) {
        Swal.fire({
            title: "'.tr('Sei sicuro?').'",
            html: "'.tr('Eliminare questo elemento?').'",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "'.tr('Elimina').'",
            cancelButtonText: "'.tr('Annulla').'",
            customClass: {
                confirmButton: "btn btn-lg btn-danger",
                cancelButton: "btn btn-lg"
            }
        }).then(function (result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: globals.rootdir + "/actions.php",
                    type: "POST",
                    data: {
                        id_module: globals.id_module,
                        id_conto: id_conto,
                        lvl: level,
                        op: "del",
                    },
                    success: function (response) {
                        location.reload();
                    },
                    error: function() {
                        Swal.fire("'.tr('Errore').'.", "'.tr('Errore durante l\'eliminazione del conto.').'.", "error");
                    }
                });
            }
        });
    }

    function sottocontoNonInDatatable() {
        return $(this).closest(".js-sottoconti-datatable").length === 0;
    }

    function forEachSottocontiDatatable(callback) {
        $(".js-sottoconti-datatable").each(function () {
            if ($.fn.DataTable.isDataTable(this)) {
                callback($(this).DataTable());
            }
        });
    }

    $("#button-search").on("click", function(){
        var text = $("#input-cerca").val();

        $.ajax({
            url: globals.rootdir + "/actions.php",
            type: "POST",
            dataType: "json",
            data: {
                id_module: globals.id_module,
                text: text,
                op: "search",
            },
            success: function (results) {
                if (results.conti2 === 0 && results.conti3 === 0){
                    $(".conto2").each(function() {
                        if ($(this).find(".search > i").hasClass("fa-minus")) {
                            $(this).find(".search").click();
                        }
                    });
                    forEachSottocontiDatatable(function (dt) {
                        dt.search("").draw();
                    });
                    $(".conto3").filter(sottocontoNonInDatatable).show();
                    $(".conto1").show();
                    $(".conto2").show();
                    $(".totali").show();
                } else {
                    $(".conto1").hide();
                    $(".conto2").hide();
                    $(".conto3").filter(sottocontoNonInDatatable).hide();
                    $(".totali").hide();
                    results.conti2.forEach(function(item) {
                        $("#conto2-"+ item).parent().parent().parent().parent().parent().show();
                        $("#conto2-"+ item).show();
                    });

                    results.conti2_3.forEach(function(item) {
                        $("#conto2-"+ item).parent().parent().parent().parent().parent().show();
                        $("#conto2-"+ item).show();
                        if ($("#conto2-"+ item).find(".search > i").hasClass("fa-plus")) {
                            $("#conto2-"+ item).find(".search").click();
                        }
                    });

                    results.conti3.forEach(function(item) {
                        var $row = $("#conto3-"+ item);
                        if ($row.length && sottocontoNonInDatatable.call($row[0])) {
                            $row.show();
                        }
                    });

                    forEachSottocontiDatatable(function (dt) {
                        dt.search(text).draw();
                    });
                }
            }
        });

        
    });
        
    $.expr[":"].contains = $.expr.createPseudo(function(arg) {
        return function( elem ) {
            return $(elem).text().toUpperCase().indexOf(arg.toUpperCase()) >= 0;
        };
    });
</script>';