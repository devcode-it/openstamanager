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

use Plugins\PianificazioneFatturazione\Pianificazione;

include_once __DIR__.'/../../../core.php';

$mesi = [
    1 => 'Gennaio',
    2 => 'Febbraio',
    3 => 'Marzo',
    4 => 'Aprile',
    5 => 'Maggio',
    6 => 'Giugno',
    7 => 'Luglio',
    8 => 'Agosto',
    9 => 'Settembre',
    10 => 'Ottobre',
    11 => 'Novembre',
    12 => 'Dicembre',
];

$pianificazioni = Pianificazione::doesntHave('fattura')
    ->orderBy('data_scadenza', 'asc')
    ->whereHas('contratto', function ($q) {
        $q->whereHas('stato', function ($q) {
            $q
            ->where('is_fatturabile', 1)
            ->where('descrizione', '<>', 'Concluso');
        });
    })->get();


    if ($pianificazioni->isEmpty()) {
        echo '<p>'.tr('Non ci sono fatture da emettere').'.</p>';
        return;
    }
    
    $conteggio = Pianificazione::doesntHave('fattura')
        ->selectRaw('month(co_fatturazione_contratti.data_scadenza) mese, count(*) conto')
        ->whereHas('contratto', function ($q) {
            $q->whereHas('stato', function ($q) {
                $q
                    ->where('is_fatturabile', 1)
                    ->where('descrizione', '<>', 'Concluso');
            });
        })
        ->whereYear('co_fatturazione_contratti.data_scadenza', date('Y'))
        ->groupBy('mese')
        ->get();
    
    $raggruppamenti = $pianificazioni->groupBy(function ($item) {
        return ucfirst($item->data_scadenza->formatLocalized('%B %Y'));
    });
    
    

echo
'<div class="container"
    <div class="row">
        <div class="col-md-2 col-md-offset-9">
            <select class="form-control select-input openstamanager-input superselect select-year">';

            for ($i=intval(date('Y')); $i<=intval(date('Y')) + 10; $i++) {
                $selectType = ($i == date('m'))? "selected" : "";
                echo
                '<option value="' . $i . '" ' . $selectType . '>' . $i . '</option>';
            }

            echo
            '</select><br>
        </div>
    </div>
    <div class="div-month">';
            for ($i=1; $i<=12; $i++) {
            
            $btnType = ($i == date('m'))? "btn-primary":"";
            echo
            '<div class="col-md-1">
                <a class="btn btn-month ' . $btnType . '" data-month="' . $i . '" style="cursor:pointer" onclick="month_click($(this))">' .
                $mesi[$i] . ' </br>(' . $conteggio[$i-1]->conto . ')</a>
            </div>';
        }

    echo
    '</div>';

    echo
    '<div style="display:none" class="template-month">
        <div class="col-md-1 " style="margin:10px 0px; padding:0px;">
            <a class="btn btn-month" onclick="month_click($(this))">
                <div class="text"></div>
                <div class="text-count"></div>
            </a>
        </div>
    </div>';
    ?>

    <div class="row">
        <div class="col-md-6">
            <br>
            <input type="text" class="filter-input form-control" placeholder="<?= tr('Applica filtro...') ?>">
            <br>
        </div>
    </div>

    <?php

    echo
    '<div>
        <table id="tbl-rate" class="table-rate table table-hover table-striped">
            <thead>
                <tr>
                    <th width="4%">'.tr('').'</th>
                    <th width="28%">'.tr('Scadenza').'</th>
                    <th width="32%">'.tr('Ragione sociale').'</th>
                    <th width="26%">'.tr('Importo').'</th>
                    <th width="10%"></th>
                </tr>
            </thead>
            <tbody>';

            // Elenco fatture da emettere
            foreach ($pianificazioni as $pianificazione) {
                $contratto = $pianificazione->contratto;
                $anagrafica = $contratto->anagrafica;
                $numero_pianificazioni = $contratto->pianificazioni()->count();
                echo '
                <tr>
                    <td>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                            data-contrattoId="' . $contratto->id . '" data-pianificazioneId="' . $pianificazione->id . '">
                        </div>
                    </td>
                    <td>
                        <div>'.dateFormat($pianificazione->data_scadenza).'</div>
                        <small>'.reference($contratto).'</small>
                    </td>
                    <td>
                        '.Modules::link('Anagrafiche', $anagrafica->id, nl2br($anagrafica->ragione_sociale)).'
                    </td>
                    <td>
                        <div>'.moneyFormat($pianificazione->totale).'</div>
                        <small>'.tr('Rata _IND_/_NUM_ (totale: _TOT_)', [
                                '_IND_' => numberFormat($pianificazione->getNumeroPianificazione(), 0),
                                '_NUM_' => numberFormat($numero_pianificazioni, 0),
                                '_TOT_' => moneyFormat($contratto->totale),
                            ]).
                        '</small>
                    </td>';

                    // Pulsanti
                    echo '
                    <td class="text-center">
                        <button type="button" class="btn btn-primary btn-sm" onclick="crea_fattura('.$contratto->id.', '.$pianificazione->id.')">
                            <i class="fa fa-euro"></i> '.tr('Crea fattura').'
                        </button>
                    </td>
                    </tr>';
                    }

                    echo
                    '</tbody>
                    <tfoot style="display:none">
                    <tr>

                    <td class="seleziona">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox">
                        </div>
                    </td>
                    <td class="data-scadenza">
                        <div class="text"></div>
                        <small></small>
                    </td>

                    <td class="ragione-sociale">
                    </td>

                    <td class="importo">
                        <div class="text"></div>
                        <small></small>
                    </td>
                    <td class="text-center azione">
                        <button type="button" class="btn btn-default btn-sm">
                            <i class="fa fa-euro"></i> '.tr('Crea fattura').'
                        </button>
                    </td>

                </tr>
            </tfoot>
        </table><br>
    </div>';
    ?>
    <div class="row">
        <div class="col-md-4">
            <a class="btn btn-primary seleziona-tutti"><?= tr('Seleziona tutti') ?></a>
            <a class="btn btn-default deseleziona-tutti"><?= tr('Deseleziona tutti') ?></a>
        </div>
        <?php
        echo'
    
        <div class="col-md-3 col-md-offset-5">
            <button type="button" class="btn btn-primary" onclick="crea_fattura_multipla($(this))">
                <i class="fa fa-euro"></i> '.tr('Fattura tutti i selezionati').'
            </button>
        </div>
    </div>
</div>';

$modulo_pianificazione = Modules::get('Contratti');
$plugin_pianificazione = Plugins::get('Pianificazione fatturazione');


echo '
<script>

$(document).ready(function () {
    $(".select-year").on("change", function() {
        var $this = $(this);
        var currentMonth = $(".div-month .btn-primary").data("month");
        var currentYear = $this.val();
        update_month(currentMonth, currentYear);
        update_table(currentMonth, currentYear);
    });
    $(".filter-input").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#tbl-rate tbody tr").filter(function() {
          $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
    $(".seleziona-tutti").on("click", function() {
        $("#tbl-rate").find("input[type=checkbox]").each(function() {
            if ($(this).closest("tr").css("display") != "none") {
                $(this).prop("checked", true);
            }
        });
    });
    $(".deseleziona-tutti").on("click", function() {
        $("#tbl-rate").find("input[type=checkbox]").prop("checked", false);
    });
    $(".select-year").change();

});
function month_click($this) {
    var oldSelected = $(".div-month .btn-primary");
    oldSelected.removeClass("btn-primary");
    oldSelected.addClass("btn-light");
    $this.removeClass("btn-light");
    $this.addClass("btn-primary");
    var currentMonth = $this.data("month");
    var currentYear = $(".select-year").val();
    update_table(currentMonth, currentYear);
}
function update_month(currentMonth, currentYear) {
    $.ajax({
        url: "' . $plugin_pianificazione->fileurl('ajax_rate.php') . '",
        type: "POST",
        data: {
            action: "update_month",
            currentYear: currentYear,
        },
        success: function(data){
            data = JSON.parse(data);
            var $template = $(".template-month");
            var $div = $(".div-month");
            $div.html("");
            for (var i=1; i<=12; i++) {
                $template.find("a").attr("data-month", i);
                $template.find(".text").html(mesi[i]);
                if (typeof data[i] === "undefined") {
                    $template.find(".text-count").html("(0)");
                } else {
                    $template.find(".text-count").html("(" + data[i] + ")");
                }
                if (i == parseInt(currentMonth)) {
                    $template.find("a").addClass("btn-primary");
                } else {
                    $template.find("a").addClass("btn-light");
                }
                $div.append($template.html());
                $template.find("a").removeClass("btn-primary");
                $template.find("a").removeClass("btn-light");
            }
        },
    });
}
function update_table(currentMonth, currentYear) {
    $.ajax({
        url: "' . $plugin_pianificazione->fileurl('ajax_rate.php') . '",
        type: "POST",
        data: {
            action: "update_table",
            currentMonth: currentMonth,
            currentYear: currentYear,
        },
        success: function(data){
            data = JSON.parse(data);
            var $template = $(".table-rate tfoot");
            var $tbody = $(".table-rate tbody");
            $tbody.html("");
            $.each(data, function(key, value) {
                $template.find(".seleziona input").attr("data-contrattoId", value.idContratto);
                $template.find(".seleziona input").attr("data-pianificazioneId", value.idPianificazione);
                $template.find(".data-scadenza .text").html(value.dataScadenza);
                $template.find(".data-scadenza small").html(value.contratto);
                $template.find(".ragione-sociale").html(value.ragioneSociale);
                $template.find(".importo .text").html(value.totale);
                $template.find(".importo small").html(value.importo);
                $template.find(".azione button").attr("onclick","crea_fattura(" + value.idContratto + ", " + value.idPianificazione + ")");
                $tbody.append($template.html());
            });
        },
    });
}
function crea_fattura(contratto, rata){
    openModal("Crea fattura", "'.$plugin_pianificazione->fileurl('crea_fattura.php').'?id_module='.$modulo_pianificazione->id.'&id_plugin='.$plugin_pianificazione->id.'&id_record=" + contratto + "&rata=" + rata);
}
function crea_fattura_multipla($this) {
    var $table = $("table");
    var $rows = $table.find("tbody");
    var fatture = [];
    $rows.find("input[type=checkbox]").each(function() {
        if ($(this).is(":checked")) {
            fatture.push($(this));
        }
     });
    $fatture = $(fatture);
    records = []
    for (var i=0; i<$fatture.length; i++) {
        records.push(
            {
                rata: $fatture[i].attr("data-pianificazioneId"),
                contratto: $fatture[i].attr("data-contrattoId"),
            }
        );
    }
    records = JSON.stringify(records);
    console.log(records);
    if (records.length > 0) {
        openModal(
            "Crea fattura multipla",
            "' . $plugin_pianificazione->fileurl('crea_fattura_multipla.php') . '?id_module=' . $modulo_pianificazione->id .
              '&id_plugin=' . $plugin_pianificazione->id . '&records=" + records
        );
    }
}
var mesi = {
    1: "Gennaio",
    2: "Febbraio",
    3: "Marzo",
    4: "Aprile",
    5: "Maggio",
    6: "Giugno",
    7: "Luglio",
    8: "Agosto",
    9: "Settembre",
    10: "Ottobre",
    11: "Novembre",
    12: "Dicembre",
};

init();
</script>';