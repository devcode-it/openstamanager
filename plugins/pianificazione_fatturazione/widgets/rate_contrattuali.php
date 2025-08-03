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

include_once __DIR__.'/../../../core.php';
use Models\Module;
use Models\Plugin;

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

echo '
<div class="container-fluid">
    <!-- Filtro data compatto -->
    <div class="row mb-3">
        <div class="col-md-2">
            <label class="control-label text-muted small">'.tr('Anno').'</label>
            <select class="form-control select-input openstamanager-input superselect select-year">';

for ($i = intval(date('Y')) - 1; $i <= intval(date('Y')) + 10; ++$i) {
    $selectType = ($i == date('Y')) ? 'selected' : '';
    echo '              <option value="'.$i.'" '.$selectType.'>'.$i.'</option>';
}

echo '          </select>
        </div>
        <div class="col-md-10">
            <label class="control-label text-muted small">'.tr('Seleziona mese').'</label>
            <div class="div-month d-flex flex-wrap justify-content-start">';
for ($i = 1; $i <= 12; ++$i) {
    $btnType = ($i == date('m')) ? 'btn-primary' : 'btn-outline-secondary';
    $count = $conteggio[$i - 1]->conto;
    $badgeClass = $count > 0 ? 'badge-danger' : 'badge-light';
    $badgeTextClass = $count > 0 ? 'text-white' : 'text-muted';

    // Abbreviazione del mese per una visualizzazione più compatta
    $meseAbbr = substr($mesi[$i], 0, 3);

    echo '
                <div class="month-button-wrapper mr-2 mb-2">
                    <button type="button" class="btn '.$btnType.' btn-month position-relative d-flex align-items-center justify-content-center"
                            data-month="'.$i.'" onclick="month_click($(this))"
                            style="min-width: 70px; height: 45px; border-radius: 8px; transition: all 0.2s ease;">
                        <div class="month-name font-weight-bold" style="font-size: 12px;">'.$meseAbbr.'</div>
                        <span class="badge '.$badgeClass.' '.$badgeTextClass.' position-absolute"
                              style="top: -8px; right: -8px; font-size: 10px; min-width: 20px; height: 20px;
                                     display: flex; align-items: center; justify-content: center; border-radius: 50%;">'.$count.'</span>
                    </button>
                </div>';
}

echo '          </div>
        </div>
    </div>

    <!-- Template nascosto per i mesi (aggiornato via AJAX) -->
    <div style="display:none" class="template-month">
        <div class="month-button-wrapper mr-2 mb-2">
            <button type="button" class="btn btn-month position-relative d-flex align-items-center justify-content-center"
                    onclick="month_click($(this))"
                    style="min-width: 70px; height: 45px; border-radius: 8px; transition: all 0.2s ease;">
                <div class="month-name font-weight-bold text" style="font-size: 12px;"></div>
                <span class="badge position-absolute text-count"
                      style="top: -8px; right: -8px; font-size: 10px; min-width: 20px; height: 20px;
                             display: flex; align-items: center; justify-content: center; border-radius: 50%;"></span>
            </button>
        </div>
    </div>';
?>

    <!-- Filtro di ricerca migliorato -->
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fa fa-search"></i></span>
                </div>
                <input type="text" class="filter-input form-control" placeholder="<?php echo tr('Cerca per ragione sociale, contratto o importo...'); ?>">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="button" onclick="$('.filter-input').val('').trigger('keyup');">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
            </div>
        </div>

    </div>

    <?php

echo '
    <!-- Tabella rate contrattuali -->
    <div class="table-responsive">
        <table id="tbl-rate" class="table-rate table table-hover table-sm mb-0">
            <thead class="thead-light">
                <tr>
                    <th width="4%" class="text-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="select-all-header">
                            <label class="form-check-label" for="select-all-header"></label>
                        </div>
                    </th>
                    <th width="25%"><i class="fa fa-calendar text-muted"></i> '.tr('Scadenza').'</th>
                    <th width="35%"><i class="fa fa-user text-muted"></i> '.tr('Cliente').'</th>
                    <th width="20%"><i class="fa fa-euro text-muted"></i> '.tr('Importo').'</th>
                    <th width="16%" class="text-center"><i class="fa fa-cogs text-muted"></i> '.tr('Azioni').'</th>
                </tr>
            </thead>
            <tbody>';

// Elenco fatture da emettere
foreach ($pianificazioni as $pianificazione) {
    $contratto = $pianificazione->contratto;
    $anagrafica = $contratto->anagrafica;
    $numero_pianificazioni = $contratto->pianificazioni()->count();

    // Calcolo giorni alla scadenza per indicatori visivi
    $oggi = new DateTime();
    $scadenza = new DateTime($pianificazione->data_scadenza);
    $giorni_scadenza = $oggi->diff($scadenza)->days;
    $scaduto = $scadenza < $oggi;

    // Classi CSS per indicatori visivi
    $row_class = '';
    $status_icon = '';
    $status_class = '';

    if ($scaduto) {
        $row_class = 'table-danger';
        $status_icon = 'fa-exclamation-triangle';
        $status_class = 'text-danger';
    } elseif ($giorni_scadenza <= 7) {
        $row_class = 'table-warning';
        $status_icon = 'fa-clock-o';
        $status_class = 'text-warning';
    } else {
        $status_icon = 'fa-calendar-check-o';
        $status_class = 'text-success';
    }

    echo '
                <tr class="'.$row_class.'">
                    <td class="text-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                            data-contrattoId="'.$contratto->id.'" data-pianificazioneId="'.$pianificazione->id.'">
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <i class="fa '.$status_icon.' '.$status_class.' mr-2"></i>
                            <div>
                                <div class="font-weight-bold">'.dateFormat($pianificazione->data_scadenza).'</div>
                                <small class="text-muted">'.reference($contratto).'</small>';

    if ($scaduto) {
        echo '                      <br><small class="text-danger"><i class="fa fa-exclamation-triangle"></i> '.tr('Scaduta').'</small>';
    } elseif ($giorni_scadenza <= 7) {
        echo '                      <br><small class="text-warning"><i class="fa fa-clock-o"></i> '.tr('Scade tra _DAYS_ giorni', ['_DAYS_' => $giorni_scadenza]).'</small>';
    }

    echo '                      </div>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <i class="fa fa-user text-muted mr-2"></i>
                            <div>
                                '.Modules::link('Anagrafiche', $anagrafica->id, '<span class="font-weight-bold">'.nl2br((string) $anagrafica->ragione_sociale).'</span>').'
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <i class="fa fa-euro text-muted mr-2"></i>
                            <div>
                                <div class="font-weight-bold text-primary">'.moneyFormat($pianificazione->totale).'</div>
                                <small class="text-muted">'.tr('Rata _IND_/_NUM_', [
        '_IND_' => numberFormat($pianificazione->getNumeroPianificazione(), 0),
        '_NUM_' => numberFormat($numero_pianificazioni, 0),
    ]).'</small>
                                <br><small class="text-muted">'.tr('Totale: _TOT_', ['_TOT_' => moneyFormat($contratto->totale)]).'</small>
                            </div>
                        </div>
                    </td>';

    // Pulsanti
    echo '
                    <td class="text-center">
                        <button type="button" class="btn btn-success btn-sm" onclick="crea_fattura('.$contratto->id.', '.$pianificazione->id.')" title="'.tr('Crea fattura').'">
                            <i class="fa fa-plus"></i> <i class="fa fa-file-text-o"></i>
                        </button>
                    </td>
                </tr>';
}

echo '              </tbody>
                    <tfoot style="display:none">
                        <tr>
                            <td class="seleziona text-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox">
                                </div>
                            </td>
                            <td class="data-scadenza">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-calendar text-muted mr-2"></i>
                                    <div>
                                        <div class="text font-weight-bold"></div>
                                        <small class="text-muted"></small>
                                    </div>
                                </div>
                            </td>
                            <td class="ragione-sociale">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-user text-muted mr-2"></i>
                                    <div></div>
                                </div>
                            </td>
                            <td class="importo">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-euro text-muted mr-2"></i>
                                    <div>
                                        <div class="text font-weight-bold text-primary"></div>
                                        <small class="text-muted"></small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center azione">
                                <button type="button" class="btn btn-success btn-sm" title="'.tr('Crea fattura').'">
                                    <i class="fa fa-plus"></i> <i class="fa fa-file-text-o"></i>
                                </button>
                            </td>
                        </tr>
                    </tfoot>
            </table>
        </div>

        <!-- Controlli compatti -->
        <div class="row mt-3 align-items-center">
            <div class="col-md-8">
                <div class="d-flex align-items-center">
                    <span class="text-muted mr-3">
                        <i class="fa fa-info-circle"></i>
                        <span id="selected-count">0</span> '.tr('elementi selezionati').'
                    </span>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary seleziona-tutti">
                            <i class="fa fa-check-square-o"></i> '.tr('Tutti').'
                        </button>
                        <button type="button" class="btn btn-outline-secondary deseleziona-tutti">
                            <i class="fa fa-square-o"></i> '.tr('Nessuno').'
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-right">
                <button type="button" class="btn btn-primary" onclick="crea_fattura_multipla($(this))" disabled id="btn-fattura-multipla">
                    <i class="fa fa-files-o"></i> '.tr('Crea fatture selezionate').'
                </button>
            </div>
        </div>
    </div>
</div>';

$id_modulo_pianificazione = Module::where('name', 'Contratti')->first()->id;
$plugin_pianificazione = Plugin::where('name', 'Pianificazione fatturazione')->first();

echo '
<script>

$(document).ready(function () {
    // Gestione cambio anno
    $(".select-year").on("change", function() {
        var $this = $(this);
        var currentMonth = $(".div-month .btn-primary").data("month");
        var currentYear = $this.val();
        update_month(currentMonth, currentYear);
        update_table(currentMonth, currentYear);
    });

    // Filtro di ricerca migliorato
    $(".filter-input").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        var visibleRows = 0;
        $("#tbl-rate tbody tr").filter(function() {
            var show = $(this).text().toLowerCase().indexOf(value) > -1;
            $(this).toggle(show);
            if (show) visibleRows++;
            return show;
        });


        updateSelectedCount();
    });

    // Checkbox "seleziona tutti" nell\'header
    $("#select-all-header").on("change", function() {
        var isChecked = $(this).prop("checked");
        $("#tbl-rate tbody input[type=checkbox]").each(function() {
            if ($(this).closest("tr").css("display") != "none") {
                $(this).prop("checked", isChecked);
            }
        });
        updateSelectedCount();
    });

    // Gestione selezione singola
    $(document).on("change", "#tbl-rate tbody input[type=checkbox]", function() {
        updateSelectedCount();

        // Aggiorna checkbox header
        var totalVisible = $("#tbl-rate tbody tr:visible").length;
        var totalChecked = $("#tbl-rate tbody tr:visible input[type=checkbox]:checked").length;
        $("#select-all-header").prop("checked", totalVisible > 0 && totalChecked === totalVisible);
    });

    // Pulsanti seleziona/deseleziona tutti
    $(".seleziona-tutti").on("click", function() {
        $("#tbl-rate tbody input[type=checkbox]").each(function() {
            if ($(this).closest("tr").css("display") != "none") {
                $(this).prop("checked", true);
            }
        });
        $("#select-all-header").prop("checked", true);
        updateSelectedCount();
    });

    $(".deseleziona-tutti").on("click", function() {
        $("#tbl-rate tbody input[type=checkbox]").prop("checked", false);
        $("#select-all-header").prop("checked", false);
        updateSelectedCount();
    });

    // Effetti hover per i bottoni dei mesi
    $(document).on("mouseenter", ".btn-month", function() {
        if (!$(this).hasClass("btn-primary")) {
            $(this).addClass("shadow-sm");
        }
    });

    $(document).on("mouseleave", ".btn-month", function() {
        if (!$(this).hasClass("btn-primary")) {
            $(this).removeClass("shadow-sm");
        }
    });

    // Inizializzazione
    $(".select-year").change();
    updateSelectedCount();
});

// Funzione per aggiornare il contatore degli elementi selezionati
function updateSelectedCount() {
    var selectedCount = $("#tbl-rate tbody tr:visible input[type=checkbox]:checked").length;
    $("#selected-count").text(selectedCount);

    // Abilita/disabilita pulsante fatturazione multipla
    $("#btn-fattura-multipla").prop("disabled", selectedCount === 0);

    if (selectedCount > 0) {
        $("#btn-fattura-multipla").removeClass("btn-secondary").addClass("btn-primary");
    } else {
        $("#btn-fattura-multipla").removeClass("btn-primary").addClass("btn-secondary");
    }
}
function month_click($this) {
    // Rimuovi selezione precedente
    var oldSelected = $(".div-month .btn-primary");
    oldSelected.removeClass("btn-primary");
    oldSelected.addClass("btn-outline-secondary");

    // Aggiungi selezione al nuovo bottone
    $this.removeClass("btn-outline-secondary");
    $this.addClass("btn-primary");

    // Aggiorna tabella
    var currentMonth = $this.data("month");
    var currentYear = $(".select-year").val();
    update_table(currentMonth, currentYear);
}
function update_month(currentMonth, currentYear) {
    $.ajax({
        url: "'.$plugin_pianificazione->fileurl('ajax_rate.php').'",
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
                var count = (typeof data[i] === "undefined") ? 0 : data[i];
                var badgeClass = count > 0 ? "badge-danger" : "badge-light";
                var badgeTextClass = count > 0 ? "text-white" : "text-muted";
                var meseAbbr = mesi[i].substring(0, 3);

                // Clona il template
                var $newButton = $template.clone();

                // Imposta attributi e contenuto
                $newButton.find("button").attr("data-month", i);
                $newButton.find(".text").html(meseAbbr);
                $newButton.find(".text-count")
                    .html(count)
                    .removeClass("badge-danger badge-light text-white text-muted")
                    .addClass(badgeClass + " " + badgeTextClass);

                // Imposta stato attivo/inattivo
                if (i == parseInt(currentMonth)) {
                    $newButton.find("button").removeClass("btn-outline-secondary").addClass("btn-primary");
                } else {
                    $newButton.find("button").removeClass("btn-primary").addClass("btn-outline-secondary");
                }

                $div.append($newButton.html());
            }
        },
    });
}
function update_table(currentMonth, currentYear) {
    $.ajax({
        url: "'.$plugin_pianificazione->fileurl('ajax_rate.php').'",
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
                // Calcolo indicatori visivi per scadenza
                var oggi = new Date();
                var scadenza = new Date(value.dataScadenzaRaw);
                var giorni = Math.ceil((scadenza - oggi) / (1000 * 60 * 60 * 24));
                var scaduto = scadenza < oggi;

                var rowClass = "";
                var statusIcon = "";
                var statusClass = "";
                var statusText = "";

                if (scaduto) {
                    rowClass = "table-danger";
                    statusIcon = "fa-exclamation-triangle";
                    statusClass = "text-danger";
                    statusText = \'<br><small class="text-danger"><i class="fa fa-exclamation-triangle"></i> '.tr('Scaduta').'</small>\';
                } else if (giorni <= 7) {
                    rowClass = "table-warning";
                    statusIcon = "fa-clock-o";
                    statusClass = "text-warning";
                    statusText = \'<br><small class="text-warning"><i class="fa fa-clock-o"></i> '.tr('Scade tra').' \' + giorni + \' '.tr('giorni').'</small>\';
                } else {
                    statusIcon = "fa-calendar-check-o";
                    statusClass = "text-success";
                }

                // Aggiorna template
                var $row = $template.find("tr").clone();
                $row.addClass(rowClass);
                $row.find(".seleziona input").attr("data-contrattoId", value.idContratto);
                $row.find(".seleziona input").attr("data-pianificazioneId", value.idPianificazione);

                // Icona di stato e data
                $row.find(".data-scadenza i").removeClass().addClass("fa " + statusIcon + " " + statusClass + " mr-2");
                $row.find(".data-scadenza .text").html(value.dataScadenza + statusText);
                $row.find(".data-scadenza small").html(value.contratto);

                // Ragione sociale
                $row.find(".ragione-sociale div div").html(value.ragioneSociale);

                // Importo
                $row.find(".importo .text").html(value.totale);
                $row.find(".importo small").html(value.importo);

                // Pulsante azione
                $row.find(".azione button").attr("onclick","crea_fattura(" + value.idContratto + ", " + value.idPianificazione + ")");

                $tbody.append($row);
            });

            // Reset contatori e selezioni
            $("#select-all-header").prop("checked", false);
            updateSelectedCount();
        },
    });
}
function crea_fattura(contratto, rata){
    openModal("Crea fattura", "'.$plugin_pianificazione->fileurl('crea_fattura.php').'?id_module='.$id_modulo_pianificazione.'&id_plugin='.$plugin_pianificazione->id.'&id_record=" + contratto + "&rata=" + rata);
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
    if (records.length > 0) {
        openModal(
            "Crea fattura multipla",
            "'.$plugin_pianificazione->fileurl('crea_fattura_multipla.php').'?id_module='.$id_modulo_pianificazione.
              '&id_plugin='.$plugin_pianificazione->id.'&records=" + records
        );
    }
}
// Array mesi per JavaScript
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
