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

$id_conto = get('id_conto');
$conto_secondo = $dbo->selectOne('co_pianodeiconti2', '*', ['id' => $id_conto]);
$conto_primo = $dbo->selectOne('co_pianodeiconti1', '*', ['id' => $conto_secondo['idpianodeiconti1']]);

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
            SUM(totale) AS totale,
            SUM(totale_reddito) AS totale_reddito
            FROM co_movimenti
            WHERE data BETWEEN '.prepare($_SESSION['period_start']).' AND '.prepare($_SESSION['period_end']).' GROUP BY idconto
        ) movimenti ON co_pianodeiconti3.id=movimenti.idconto
    WHERE `idpianodeiconti2` = '.prepare($conto_secondo['id']).' ORDER BY numero ASC';

$terzo_livello = $dbo->fetchArray($query3);

if (!empty($terzo_livello)) {
    echo '
    <div class="table-responsive">
        <table class="table table-striped table-hover table-condensed">
            <thead>
                <tr>
                    <th>'.tr('Descrizione').'</th>
                    <th style="width: 10%" class="text-center">'.tr('Importo').'</th>';
    if ($conto_primo['descrizione'] == 'Economico') {
        echo '
                        <th style="width: 10%" class="text-center">'.tr('Importo reddito').'</th>';
    }
    echo '
                </tr>
            </thead>
            <tbody>';
    foreach ($terzo_livello as $conto_terzo) {
        // Se il conto non ha documenti collegati posso eliminarlo
        $numero_movimenti = $conto_terzo['numero_movimenti'];

        $totale_conto = $conto_terzo['totale'];
        $totale_reddito = $conto_terzo['totale_reddito'];
        if ($conto_primo['descrizione'] != 'Patrimoniale') {
            $totale_conto = -$totale_conto;
            $totale_reddito = -$totale_reddito;
        }

        $totale_conto2 += $totale_conto;
        $totale_reddito2 += $totale_reddito;

            echo '
                <tr class="conto3" id="conto3-'.$conto_terzo['id'].'" style="'.(!empty($numero_movimenti) ? '' : 'opacity: 0.5;').'">
                    <td>';

        // Possibilità di esplodere i movimenti del conto
        if (!empty($numero_movimenti)) {
            echo '
                        <button type="button" id="movimenti-'.$conto_terzo['id'].'" class="btn btn-default btn-xs plus-btn"><i class="fa fa-plus"></i></button>';
        }

        // Span con i pulsanti
        echo '
                        <span class="hide tools pull-right">';

        //  Possibilità di visionare l'anagrafica
        $id_anagrafica = $conto_terzo['idanagrafica'];
        $anagrafica_deleted = $conto_terzo['deleted_at'];
        if (isset($id_anagrafica)) {
            echo Modules::link('Anagrafiche', $id_anagrafica, ' <i title="'.(isset($anagrafica_deleted) ? tr('Anagrafica eliminata') : tr('Visualizza anagrafica')).'" class="btn btn-'.(isset($anagrafica_deleted) ? 'danger' : 'primary').' btn-xs fa fa-user" ></i>');
        }

        // Stampa mastrino
        if (!empty($numero_movimenti)) {
            echo '
                        '.Prints::getLink('Mastrino', $conto_terzo['id'], 'btn-info btn-xs', '', null, 'lev=3');
        }

        // Pulsante per aggiornare il totale reddito del conto di livello 3
        echo '
                            <button type="button" class="btn btn-info btn-xs" onclick="aggiornaReddito('.$conto_terzo['id'].')">
                                <i class="fa fa-refresh"></i>
                            </button>';

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

        echo '
                        </span>';

        // Span con info del conto
        echo '
                        <span class="clickable" id="movimenti-'.$conto_terzo['id'].'">
                            &nbsp;'.$conto_secondo['numero'].'.'.$conto_terzo['numero'].' '.$conto_terzo['descrizione'].' <span class="text-muted">('.tr('deducibile al _PERC_%', ['_PERC_' => Translator::numberToLocale($conto_terzo['percentuale_deducibile'], 0)]).')</span>' .'
                        </span>
                        <div id="conto_'.$conto_terzo['id'].'" style="display:none;"></div>
                    </td>

                    <td class="text-right">
                        '.moneyFormat($totale_conto, 2).'
                    </td>';
        if ($conto_primo['descrizione'] == 'Economico') {
            echo '
                    <td class="text-right">
                        '.moneyFormat($totale_reddito, 2).'
                    </td>';
        }
        echo '
                </tr>';
    }
} else {
    echo '
                <br><span>'.tr('Nessun conto presente').'</span>';
}

if (!empty($terzo_livello)) {
    echo '
            </tbody>
            <tfoot>
                <tr class="totali">
                    <th class="text-right">'.tr('Totale').'</th>
                    <th class="text-right">'.moneyFormat($totale_conto2).'</th>';
    if ($conto_primo['descrizione'] == 'Economico') {
        echo '      <th class="text-right">'.moneyFormat($totale_reddito2).'</th>';
    }
    echo '
                </tr>
            </tfoot>
        </table>
        <br><br>
    </div>';
}

echo '
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
            $(this).unbind().on("click", function() {
                let movimenti = $(this).parent().find("div[id^=conto_]");

                if(!movimenti.html()) {
                    let id_conto = $(this).attr("id").split("-").pop();

                    caricaMovimenti(movimenti.attr("id"), id_conto);
                } else {
                    movimenti.slideToggle();
                }

                $(this).parent().find(".plus-btn i").toggleClass("fa-plus").toggleClass("fa-minus");
            });
        });

        $("button[id^=movimenti-]").each(function() {
            $(this).unbind().on("click", function() {
                let movimenti = $(this).parent().find("div[id^=conto_]");

                if(!movimenti.html()) {
                    let id_conto = $(this).attr("id").split("-").pop();

                    caricaMovimenti(movimenti.attr("id"), id_conto);
                } else {
                    movimenti.slideToggle();
                }

                $(this).parent().find(".plus-btn i").toggleClass("fa-plus").toggleClass("fa-minus");
            });
        })
    });

    function caricaMovimenti(selector, id_conto) {
        $("#main_loading").show();

        $.ajax({
            url: "'.$structure->fileurl('dettagli_conto3.php').'",
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
</script>';
