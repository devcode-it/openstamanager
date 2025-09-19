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

// Verifico se è già stata eseguita l'apertura bilancio
$bilancio_gia_aperto = $dbo->fetchNum('SELECT id FROM co_movimenti WHERE is_apertura=1 AND data BETWEEN '.prepare($_SESSION['period_start']).' AND '.prepare($_SESSION['period_end']));

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

// Livello 1
$query1 = 'SELECT * FROM `co_pianodeiconti1` ORDER BY id DESC';
$primo_livello = $dbo->fetchArray($query1);
foreach ($primo_livello as $conto_primo) {
    $totale_attivita = [];
    $totale_passivita = [];

    $costi = [];
    $ricavi = [];

    $titolo = $conto_primo['descrizione'] == 'Economico' ? tr('Conto economico') : tr('Stato patrimoniale');

    echo '
<hr>
<div class="card conto1">
    <div class="card-header">
        <h3 class="card-title">
            '.$titolo.' 
            <button type="button" class="btn btn-xs btn-primary" data-card-widget="tooltip" title="'.tr('Aggiungi un nuovo conto...').'" onclick="aggiungiConto('.$conto_primo['id'].', 2)">
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
    if ($conto_primo['descrizione'] == 'Economico') {
        echo '
                        <th width="10%" class="text-center">'.tr('Importo reddito').'</th>';
    } else {
        echo '
                        <th width="10%"></th>';
    }
    echo '
                        <th width="5%" class="text-center"></th>
                    </tr>
                </thead>
                <tbody>';

    // Livello 2
    $query2 = 'SELECT * FROM `co_pianodeiconti2` WHERE idpianodeiconti1 = '.prepare($conto_primo['id']).' ORDER BY numero ASC';
    $secondo_livello = $dbo->fetchArray($query2);

    foreach ($secondo_livello as $conto_secondo) {
        // Livello 2
        if ($conto_primo['descrizione'] == 'Economico') {
            $totale_conto2 = $dbo->fetchOne('SELECT SUM(-totale) AS totale FROM `co_movimenti` INNER JOIN co_pianodeiconti3 ON co_movimenti.idconto=co_pianodeiconti3.id WHERE idconto IN(SELECT id FROM co_pianodeiconti3 WHERE idpianodeiconti2='.prepare($conto_secondo['id']).') AND co_movimenti.data>='.prepare($_SESSION['period_start']).' AND co_movimenti.data<='.prepare($_SESSION['period_end']))['totale'];

            // Calcolo del totale_reddito con gestione dei risconti
            $totale_reddito2 = $dbo->fetchOne('
                            SELECT SUM(
                                CASE
                                    WHEN data_inizio_competenza IS NULL OR data_fine_competenza IS NULL THEN
                                        -totale_reddito
                                    ELSE
                                        -totale_reddito * (
                                            DATEDIFF(
                                                LEAST(data_fine_competenza, '.prepare($_SESSION['period_end']).'),
                                                GREATEST(data_inizio_competenza, '.prepare($_SESSION['period_start']).')
                                            ) + 1
                                        ) / (
                                            DATEDIFF(data_fine_competenza, data_inizio_competenza) + 1
                                        )
                                END
                            ) AS totale_reddito
                            FROM `co_movimenti` 
                            INNER JOIN co_pianodeiconti3 ON co_movimenti.idconto=co_pianodeiconti3.id 
                            WHERE idconto IN(
                                SELECT id FROM co_pianodeiconti3 
                                WHERE idpianodeiconti2='.prepare($conto_secondo['id']).'
                            ) 
                            AND (
                                (co_movimenti.data >= '.prepare($_SESSION['period_start']).' AND co_movimenti.data <= '.prepare($_SESSION['period_end']).') 
                                OR 
                                (data_inizio_competenza IS NOT NULL AND data_fine_competenza IS NOT NULL AND 
                                 data_fine_competenza >= '.prepare($_SESSION['period_start']).' AND 
                                 data_inizio_competenza <= '.prepare($_SESSION['period_end']).')
                                OR
                                (data_inizio_competenza IS NOT NULL AND data_fine_competenza IS NOT NULL AND
                                 data_inizio_competenza <= '.prepare($_SESSION['period_start']).' AND
                                 data_fine_competenza >= '.prepare($_SESSION['period_end']).')
                                OR
                                (data_inizio_competenza IS NOT NULL AND data_fine_competenza IS NOT NULL AND
                                 data_inizio_competenza <= '.prepare($_SESSION['period_end']).' AND
                                 data_inizio_competenza >= '.prepare($_SESSION['period_start']).')
                                OR
                                (data_inizio_competenza IS NOT NULL AND data_fine_competenza IS NOT NULL AND
                                 data_fine_competenza >= '.prepare($_SESSION['period_start']).' AND
                                 data_fine_competenza <= '.prepare($_SESSION['period_end']).')
                            )
                        ')['totale_reddito'];
        } else {
            $totale_conto2 = $dbo->fetchOne('SELECT SUM(totale) AS totale FROM `co_movimenti` INNER JOIN co_pianodeiconti3 ON co_movimenti.idconto=co_pianodeiconti3.id WHERE idconto IN(SELECT id FROM co_pianodeiconti3 WHERE idpianodeiconti2='.prepare($conto_secondo['id']).') AND co_movimenti.data>='.prepare($_SESSION['period_start']).' AND co_movimenti.data<='.prepare($_SESSION['period_end']))['totale'];
            $totale_reddito2 = 0;
        }

        echo '
                    <tr class="conto2" id="conto2-'.$conto_secondo['id'].'">
                        <td>
                            <h5>
                                <button type="button" id="conto2-'.$conto_secondo['id'].'" class="btn btn-default btn-xs plus-btn search"><i class="fa fa-plus"></i></button>
                                <span class="clickable" id="conto2-'.$conto_secondo['id'].'">
                                    <b>'.$conto_secondo['numero'].' '.$conto_secondo['descrizione'].'</b>
                                </span>
                                <div id="conto2_'.$conto_secondo['id'].'" style="display:none;"></div>
                            </h5>
                        </td>

                        <td class="text-right">
                            <b>'.moneyFormat($totale_conto2, 2).'</b>
                        </td>';

        if ($conto_primo['descrizione'] == 'Economico') {
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
                            '.Prints::getLink('Mastrino', $conto_secondo['id'], 'btn-info btn-xs', '', null, 'lev=2').'

                            <button type="button" class="btn btn-warning btn-xs" onclick="modificaConto('.$conto_secondo['id'].', 2)">
                                <i class="fa fa-edit"></i>
                            </button>

                            <button type="button" class="btn btn-xs btn-primary" data-card-widget="tooltip" title="'.tr('Aggiungi un nuovo conto...').'" onclick="aggiungiConto('.$conto_secondo['id'].')">
                            <i class="fa fa-plus-circle"></i>
                            </button>
                        </td>
                    </tr>';
        // Somma dei totali
        if ($totale_conto2) {
            if ($conto_primo['descrizione'] == 'Patrimoniale') {
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
            if ($conto_primo['descrizione'] == 'Economico') {
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

    // Riepiloghi
    if ($conto_primo['descrizione'] == 'Patrimoniale') {
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

        // Attività
        echo '
            <tr>
                <th class="text-right">
                    <big>'.tr('Totale attività').':</big>
                </th>
                <td class="text-right" width="150">
                    <big>'.moneyFormat($attivita, 2).'</big>
                </td>
                <td width="50"></td>';

        // Passività
        echo '
                <th class="text-right">
                    <big>'.tr('Passività').':</big>
                </th>
                <td class="text-right" width="150">
                    <big>'.moneyFormat($passivita, 2).'</big>
                </td>
                <td width="5%"></td>
            </tr>';

        // Perdita d'esercizio
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

        // Totale a pareggio
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

// Verifico se è già stata eseguita l'apertura bilancio
$bilancio_gia_chiuso = $dbo->fetchNum('SELECT id FROM co_movimenti WHERE is_chiusura=1 AND data BETWEEN '.prepare($_SESSION['period_start']).' AND '.prepare($_SESSION['period_end']));

$msg = tr('Sei sicuro di voler aprire il bilancio?');
$btn_class = 'btn-info';

if ($bilancio_gia_chiuso) {
    $msg .= ' '.tr('I movimenti di apertura già esistenti verranno annullati e ricreati').'.';
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
                // Ottieni l\'ID del conto
                let id_conto = $(this).attr("id").split("-").pop();
                let tr = $("#conto2-" + id_conto);
                let conto3 = $("#conto2_" + id_conto);
                
                // Aggiungi una riga dopo la riga corrente se non esiste
                if (!$("#conto3-row-" + id_conto).length) {
                    tr.after(\'<tr id="conto3-row-\' + id_conto + \'" class="conto3-container"><td colspan="4"><div id="conto3-content-\' + id_conto + \'"></div></td></tr>\');
                    // Sposta il div dei contenuti nella nuova riga
                    conto3.appendTo("#conto3-content-" + id_conto);
                }
                
                // Mostra/nascondi la riga dei contenuti
                $("#conto3-row-" + id_conto).toggle();
                
                if(!conto3.html()) {
                    $("#conto3-row-" + id_conto).show();
                    caricaConti3("conto2_" + id_conto, id_conto);
                }

                // Cambia l\'icona plus/minus
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
                    $(".conto3").show();
                    $(".conto1").show();
                    $(".conto2").show();
                    $(".totali").show();
                } else {
                    $(".conto1").hide();
                    $(".conto2").hide();
                    $(".conto3").hide();
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
                        $("#conto3-"+ item).show();
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
