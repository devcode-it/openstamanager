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
<div class="text-right">
    <button type="button" class="btn btn-lg '.$btn_class.'" data-op="apri-bilancio" data-title="'.tr('Apertura bilancio').'" data-backto="record-list" data-msg="'.$msg.'" data-button="'.tr('Riprendi saldi').'" data-class="btn btn-lg btn-warning" onclick="message( this );">
        <i class="fa fa-folder-open"></i> '.tr('Apertura bilancio').'
    </button>
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
<div class="box">
    <div class="box-header">
        <h3 class="box-title">'.$titolo.'</h3>
        <div class="pull-right">
           '.Prints::getLink('Mastrino', $conto_primo['id'], null, tr('Stampa'), null, 'lev=1').'
        </div>
    </div>

    <div class="box-body">';

    // Livello 2
    $query2 = 'SELECT * FROM `co_pianodeiconti2` WHERE idpianodeiconti1 = '.prepare($conto_primo['id']).' ORDER BY numero ASC';
    $secondo_livello = $dbo->fetchArray($query2);

    foreach ($secondo_livello as $conto_secondo) {
        // Livello 2
        echo '
        <div class="pull-right">
            '.Prints::getLink('Mastrino', $conto_secondo['id'], 'btn-info btn-xs', '', null, 'lev=2').'

            <button type="button" class="btn btn-warning btn-xs" onclick="modificaConto('.$conto_secondo['id'].', 2)">
                <i class="fa fa-edit"></i>
            </button>
        </div>

        <h5><b>'.$conto_secondo['numero'].' '.$conto_secondo['descrizione'].'</b></h5>

        <div class="table-responsive">
            <table class="table table-striped table-hover table-condensed">
                <thead>
                    <tr>
                        <th>'.tr('Descrizione').'</th>
                        <th style="width: 10%" class="text-center">'.tr('Importo').'</th>
                        <th style="width: 10%" class="text-center">'.tr('Importo reddito').'</th>
                    </tr>
                </thead>

                <tbody>';

        // Livello 3
        $query3 = 'SELECT `co_pianodeiconti3`.*, movimenti.numero_movimenti, movimenti.totale, movimenti.totale_reddito, anagrafica.idanagrafica, anagrafica.deleted_at
            FROM `co_pianodeiconti3`
                LEFT OUTER JOIN (
                    SELECT idanagrafica,
                           idconto_cliente,
                           idconto_fornitore,
                           deleted_at
                    FROM an_anagrafiche
                ) AS anagrafica ON co_pianodeiconti3.id IN (anagrafica.idconto_cliente, anagrafica.idconto_fornitore)
                LEFT OUTER JOIN (
                    SELECT COUNT(idconto) AS numero_movimenti,
                       idconto,
                       SUM(ROUND(totale, 2)) AS totale,
                       SUM(ROUND(totale_reddito, 2)) AS totale_reddito
                    FROM co_movimenti
                    WHERE data BETWEEN '.prepare($_SESSION['period_start']).' AND '.prepare($_SESSION['period_end']).' GROUP BY idconto
                ) movimenti ON co_pianodeiconti3.id=movimenti.idconto
            WHERE `idpianodeiconti2` = '.prepare($conto_secondo['id']).' ORDER BY numero ASC';
        $terzo_livello = $dbo->fetchArray($query3);
        foreach ($terzo_livello as $conto_terzo) {
            // Se il conto non ha documenti collegati posso eliminarlo
            $numero_movimenti = $conto_terzo['numero_movimenti'];

            $totale_conto = $conto_terzo['totale'];
            $totale_reddito = $conto_terzo['totale_reddito'];
            if ($conto_primo['descrizione'] != 'Patrimoniale') {
                $totale_conto = -$totale_conto;
                $totale_reddito = -$totale_reddito;
            }

            // Somma dei totali
            if ($conto_primo['descrizione'] == 'Patrimoniale') {
                if ($totale_conto > 0) {
                    $totale_attivita[] = abs($totale_conto);
                } else {
                    $totale_passivita[] = abs($totale_conto);
                }
            } else {
                if ($totale_conto > 0) {
                    $totale_ricavi[] = abs($totale_conto);
                } else {
                    $totale_costi[] = abs($totale_conto);
                }
            }

            echo '
                <tr style="'.(!empty($numero_movimenti) ? '' : 'opacity: 0.5;').'">
                    <td>';

            // Possibilità di esplodere i movimenti del conto
            if (!empty($numero_movimenti)) {
                echo '
                        <a href="javascript:;" class="btn btn-primary btn-xs plus-btn"><i class="fa fa-plus"></i></a>';
            }

            // Span con i pulsanti
            echo '
                        <span class="hide tools pull-right">';

            //  Possibilità di visionare l'anagrafica
            $id_anagrafica = $conto_terzo['idanagrafica'];
            $anagrafica_deleted = $conto_terzo['deleted_at'];
            if (isset($id_anagrafica)) {
                echo Modules::link('Anagrafiche', $id_anagrafica, ' <i title="'.(isset($anagrafica_deleted) ? 'Anagrafica eliminata' : 'Visualizza anagrafica').'" class="btn btn-'.(isset($anagrafica_deleted) ? 'danger' : 'primary').' btn-xs fa fa-user" ></i>');
            }

            // Stampa mastrino
            if (!empty($numero_movimenti)) {
                echo '
                            '.Prints::getLink('Mastrino', $conto_terzo['id'], 'btn-info btn-xs', '', null, 'lev=3');
            }

            // Pulsante per aggiornare il totale reddito del conto di livello 3
            if ($conto_secondo['dir'] == 'uscita') {
                echo '
                                <button type="button" class="btn btn-info btn-xs" onclick="aggiornaReddito('.$conto_terzo['id'].')">
                                    <i class="fa fa-refresh"></i>
                                </button>';
            }

            // Pulsante per modificare il nome del conto di livello 3
            echo '
                            <button type="button" class="btn btn-warning btn-xs" onclick="modificaConto('.$conto_terzo['id'].')">
                                <i class="fa fa-edit"></i>
                            </button>';

            // Possibilità di eliminare il conto se non ci sono movimenti collegati
            if ($numero_movimenti <= 0) {
                echo '
                            <a class="btn btn-danger btn-xs ask" data-toggle="tooltip" title="'.tr('Elimina').'" data-backto="record-list" data-op="del" data-idconto="'.$conto_terzo['id'].'">
                                <i class="fa fa-trash"></i>
                            </a>';
            }

            echo  '
                        </span>';

            // Span con info del conto
            echo '
                        <span class="clickable" id="movimenti-'.$conto_terzo['id'].'">
                            &nbsp;'.$conto_secondo['numero'].'.'.$conto_terzo['numero'].' '.$conto_terzo['descrizione'].($conto_terzo['percentuale_deducibile'] < 100 ? ' <span class="text-muted">('.tr('deducibile al _PERC_%', ['_PERC_' => Translator::numberToLocale($conto_terzo['percentuale_deducibile'], 0)]).')</span>' : '').'
                        </span>
                        <div id="conto_'.$conto_terzo['id'].'" style="display:none;"></div>
                    </td>

                    <td class="text-right">
                        '.moneyFormat($totale_conto, 2).'
                    </td>
                    <td class="text-right">
                        '.moneyFormat($totale_reddito, 2).'
                    </td>
                </tr>';
        }

        echo '
                </tbody>

                <tfoot>
                    <tr>
                        <th>'.tr('Descrizione').'</th>
                        <th class="text-center">'.tr('Importo').'</th>
                        <th class="text-center">'.tr('Importo reddito').'</th>
                    </tr>
                </tfoot>
            </table>';

        // Possibilità di inserire un nuovo conto
        echo '
            <button type="button" class="btn btn-xs btn-primary" data-toggle="tooltip" title="'.tr('Aggiungi un nuovo conto...').'" onclick="aggiungiConto('.$conto_secondo['id'].')">
                <i class="fa fa-plus-circle"></i>
            </button>

            <br><br>
        </div>';
    }

    echo '
    </div>

        <table class="table table-condensed table-hover">';

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
            </tr>

            <tr>
                <th class="text-right">
                    <big>'.tr('Costi').':</big>
                </th>
                <td class="text-right" width="150">
                    <big>'.moneyFormat(sum($totale_costi), 2).'</big>
                </td>
            </tr>

            <tr>
                <th class="text-right">
                    <big>'.tr('Utile/perdita').':</big>
                </th>
                <td class="text-right" width="150">
                    <big>'.moneyFormat(sum($totale_ricavi) - abs(sum($totale_costi)), 2).'</big>
                </td>
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
        $("tr").each(function() {
            $(this).on("mouseover", function() {
                $(this).find(".tools").removeClass("hide");
            });

            $(this).on("mouseleave", function() {
                $(this).find(".tools").addClass("hide");
            });
        });

        $("span[id^=movimenti-]").each(function() {
            $(this).on("click", function() {
                let movimenti = $(this).parent().find("div[id^=conto_]");

                if(!movimenti.html()) {
                    let id_conto = $(this).attr("id").split("-").pop();

                    caricaMovimenti(movimenti.attr("id"), id_conto);
                } else {
                    movimenti.slideToggle();
                }

                $(this).find(".plus-btn i").toggleClass("fa-plus").toggleClass("fa-minus");
            });
        })
    });

    function aggiungiConto(id_conto_lvl2) {
        openModal("'.tr('Nuovo conto').'", "'.$structure->fileurl('add_conto.php').'?id=" + id_conto_lvl2);
    }

    function modificaConto(id_conto, level = 3) {
        launch_modal("'.tr('Modifica conto').'", "'.$structure->fileurl('edit_conto.php').'?id=" + id_conto + "&lvl=" + level);
    }

    function caricaMovimenti(selector, id_conto) {
        $("#main_loading").show();

        $.ajax({
            url: "'.$structure->fileurl('dettagli_conto.php').'",
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
</script>';
