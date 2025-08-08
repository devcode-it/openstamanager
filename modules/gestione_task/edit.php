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

// Determina lo stato del task in base alle date
$status_class = '';
$status_text = '';
$status_icon = '';

if (!empty($record['last_executed_at'])) {
    $last_executed = new DateTime($record['last_executed_at']);
    $now = new DateTime();
    $diff = $now->diff($last_executed);

    if ($diff->days < 1) {
        $status_class = 'success';
        $status_text = tr('Eseguito recentemente');
        $status_icon = 'fa-check-circle';
    } elseif ($diff->days < 7) {
        $status_class = 'info';
        $status_text = tr('Eseguito da %d giorni', [$diff->days]);
        $status_icon = 'fa-clock-o';
    } else {
        $status_class = 'warning';
        $status_text = tr('Eseguito da più di una settimana');
        $status_icon = 'fa-exclamation-circle';
    }
} else {
    $status_class = 'secondary';
    $status_text = tr('Mai eseguito');
    $status_icon = 'fa-question-circle';
}

echo '
<form action="" method="post" id="edit-form">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="id_record" value="'.$id_record.'">

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title text-primary">
                <i class="fa fa-calendar-check-o mr-2"></i>'.tr('Informazioni task').'
            </h3>
            <div class="card-tools">
                <span class="badge badge-'.$status_class.'">
                    <i class="fa '.$status_icon.' mr-1"></i>'.$status_text.'
                </span>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    {[ "type": "text", "label": "'.tr('Nome').'", "name": "name", "required": 1, "value": "$title$", "icon-before": "<i class=\"fa fa-tag\"></i>" ]}
                </div>
                <div class="col-md-6">
                    {[ "type": "text", "label": "'.tr('Classe').'", "name": "class", "required": 1, "value": "$class$", "icon-before": "<i class=\"fa fa-code\"></i>" ]}
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    {[ "type": "checkbox", "label": "'.tr('Stato task').'", "name": "enabled", "value": "$enabled$" ]}
                </div>
                <div class="col-md-4">
                    {[ "type": "timestamp", "label": "'.tr('Data prossima esecuzione').'", "name": "next_execution_at", "value": "$next_execution_at$", "readonly": 1, "icon-before": "<i class=\"fa fa-calendar-plus-o\"></i>" ]}
                </div>
                <div class="col-md-4">
                    {[ "type": "timestamp", "label": "'.tr('Data precedente esecuzione').'", "name": "last_executed_at", "value": "$last_executed_at$", "readonly": 1, "icon-before": "<i class=\"fa fa-calendar-check-o\"></i>" ]}
                </div>
            </div>
        </div>
    </div>

    <div class="card card-outline card-info col-md-8 mx-auto">
        <div class="card-header">
            <h3 class="card-title text-info">
                <i class="fa fa-clock-o mr-2"></i>'.tr('Pianificazione').'
            </h3>
            <div class="card-tools">
                <a href="https://crontab-generator.com/it/" target="_blank" class="btn btn-sm btn-info">
                    <i class="fa fa-external-link mr-1"></i>'.tr('Esempi').'
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    {[ "type": "text", "label": "'.tr('Espressione cron').'", "name": "expression", "required": 1, "class": "text-center", "value": "$expression$", "extra": "", "readonly": 1, "icon-before": "<i class=\"fa fa-terminal\"></i>" ]}
                </div>
            </div>';
$expression = $record['expression'];

preg_match('/(.*?) (.*?) (.*?) (.*?) (.*?)/U', (string) $record['expression'], $exp);

$minuto = $exp[1];
$ora = $exp[2];
$giorno = $exp[3];
$mese = $exp[4];
$giorno_sett = $exp[5];

echo '
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card card-body bg-light mb-3">
                        <h5 class="text-primary mb-2"><i class="fa fa-language mr-2"></i>'.tr('Traduzione espressione cron').'</h5>
                        <p class="mb-0" id="cron-translation"></p>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr class="bg-light">
                                    <th class="text-center" width="40%">'.tr('Componente').'</th>
                                    <th class="text-center" width="30%">'.tr('Valore attuale').'</th>
                                    <th class="text-center" width="30%">'.tr('Preimpostazioni').'</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fa fa-clock-o text-info mr-2"></i>
                                            <strong>'.tr('Minuto').'</strong>
                                        </div>
                                    </td>
                                    <td>
                                        {[ "type": "text", "name": "minuto", "required": 1, "class": "text-center", "value": "'.$minuto.'", "readonly": 1, "extra": "style=\"border: none; background: transparent;\"" ]}
                                    </td>
                                    <td>
                                        {[ "type": "select", "name": "minuti", "value": "'.$minuto.'", "values":"list=\"*\": \"'.tr('Una volta al minuto (*)').'\",\"*/5\": \"'.tr('Ogni cinque minuti (*/5)').'\",\"0,30\": \"'.tr('Ogni trenta minuti (0,30)').'\",\"5\": \"'.tr('Al minuto 5 (5)').'\",\" \": \"'.tr('Personalizzato').'\"" ]}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fa fa-hourglass-half text-info mr-2"></i>
                                            <strong>'.tr('Ora').'</strong>
                                        </div>
                                    </td>
                                    <td>
                                        {[ "type": "text", "name": "ora", "required": 1, "class": "text-center", "value": "'.$ora.'", "readonly": 1, "extra": "style=\"border: none; background: transparent;\"" ]}
                                    </td>
                                    <td>
                                        {[ "type": "select", "name": "ore", "value": "'.$ora.'", "values":"list=\"*\": \"'.tr('Ogni ora (*)').'\",\"*/2\": \"'.tr('Ogni due ore (*/2)').'\",\"*/4\": \"'.tr('Ogni quattro ore (*/4)').'\",\"0,12\": \"'.tr('Ogni 12 ore (0,12)').'\",\"5\": \"'.tr('5:00 a.m. (5)').'\",\"17\": \"'.tr('5:00 p.m. (17)').'\",\" \": \"'.tr('Personalizzato').'\"" ]}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fa fa-calendar-o text-info mr-2"></i>
                                            <strong>'.tr('Giorno').'</strong>
                                        </div>
                                    </td>
                                    <td>
                                        {[ "type": "text", "name": "giorno", "required": 1, "class": "text-center", "value": "'.$giorno.'", "readonly": 1, "extra": "style=\"border: none; background: transparent;\"" ]}
                                    </td>
                                    <td>
                                        {[ "type": "select", "name": "giorni", "value": "'.$giorno.'", "values":"list=\"*\": \"'.tr('Ogni giorno (*)').'\",\"*/2\": \"'.tr('Ogni due giorni (*/2)').'\",\"1,15\": \"'.tr('Il 1° e 15 del mese (1,15)').'\",\"8\": \"'.tr('Il giorno 8 (8)').'\",\" \": \"'.tr('Personalizzato').'\"" ]}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fa fa-calendar text-info mr-2"></i>
                                            <strong>'.tr('Mese').'</strong>
                                        </div>
                                    </td>
                                    <td>
                                        {[ "type": "text", "name": "mese", "required": 1, "class": "text-center", "value": "'.$mese.'", "readonly": 1, "extra": "style=\"border: none; background: transparent;\"" ]}
                                    </td>
                                    <td>
                                        {[ "type": "select", "name": "mesi", "value": "'.$mese.'", "values":"list=\"*\": \"'.tr('Ogni mese (*)').'\",\"*/2\": \"'.tr('Ogni due mesi (*/2)').'\",\"1,7\": \"'.tr('Ogni 6 mesi (1,7)').'\",\"8\": \"'.tr('Agosto (8)').'\",\" \": \"'.tr('Personalizzato').'\"" ]}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fa fa-calendar-check-o text-info mr-2"></i>
                                            <strong>'.tr('Giorno della settimana').'</strong>
                                        </div>
                                    </td>
                                    <td>
                                        {[ "type": "text", "name": "giorno_sett", "required": 1, "class": "text-center", "value": "'.$giorno_sett.'", "readonly": 1, "extra": "style=\"border: none; background: transparent;\"" ]}
                                    </td>
                                    <td>
                                        {[ "type": "select", "name": "giorni_sett", "value": "'.$giorno_sett.'", "values":"list=\"*\": \"'.tr('Ogni giorno (*)').'\",\"1-5\": \"'.tr('Giorni feriali (1-5)').'\",\"0,6\": \"'.tr('Weekend (0,6)').'\",\"1,3,5\": \"'.tr('Lun, Mer, Ven (1,3,5)').'\",\" \": \"'.tr('Personalizzato').'\"" ]}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>';
?>

<script>
$(document).ready(function() {
    $("[data-toggle=\"tooltip\"]").tooltip();

    // Evidenzia la riga della tabella al passaggio del mouse
    $(".table-hover tbody tr").hover(
        function() {
            $(this).addClass("bg-light");
        },
        function() {
            $(this).removeClass("bg-light");
        }
    );

    // Alla modifica di un campo select aggiorna il corrispondente campo input
    function updateField() {
        var fieldData = [
            { select: "minuti", input: "minuto" },
            { select: "ore", input: "ora" },
            { select: "giorni", input: "giorno" },
            { select: "mesi", input: "mese" },
            { select: "giorni_sett", input: "giorno_sett" }
        ];

        fieldData.forEach(function(field) {
            var $select = $("select[name=\"" + field.select + "\"]");
            var $input = $("input[name=\"" + field.input + "\"]");
            var value = $select.val();

            if (value == " ") {
                $input.removeAttr("readonly");
                $input.css("background-color", "#f8f9fa");
                $input.css("border", "1px solid #ced4da");
            } else if (value == "") {
                $select.selectSet(" ");
            } else {
                $input.attr("readonly", "readonly");
                $input.val(value);
                $input.css("background-color", "transparent");
                $input.css("border", "none");
            }
        });
    }

    function updateExpression() {
        var $minuto = $("input[name=\"minuto\"]").val();
        var $ora = $("input[name=\"ora\"]").val();
        var $giorno = $("input[name=\"giorno\"]").val();
        var $mese = $("input[name=\"mese\"]").val();
        var $giorno_sett = $("input[name=\"giorno_sett\"]").val();

        var $expression = $minuto + " " + $ora + " " + $giorno + " " + $mese + " " + $giorno_sett;
        $("input[name=\"expression\"]").val($expression);

        updateCronTranslation($minuto, $ora, $giorno, $mese, $giorno_sett);
    }

    function updateCronTranslation(minuto, ora, giorno, mese, giorno_sett) {
        var translation = "";

        // Traduzione minuto
        if (minuto == "*") {
            translation += "Ogni minuto";
        } else if (minuto.startsWith("*/")) {
            var interval = minuto.substring(2);
            translation += "Ogni " + interval + " minuti";
        } else if (minuto.includes(",")) {
            translation += "Ai minuti " + minuto;
        } else {
            translation += "Al minuto " + minuto;
        }

        // Traduzione ora
        if (ora == "*") {
            translation += " di ogni ora";
        } else if (ora.startsWith("*/")) {
            var interval = ora.substring(2);
            translation += " ogni " + interval + " ore";
        } else if (ora.includes(",")) {
            translation += " alle ore " + ora;
        } else {
            translation += " alle ore " + ora;
        }

        // Traduzione giorno
        if (giorno == "*") {
            translation += " di ogni giorno";
        } else if (giorno.startsWith("*/")) {
            var interval = giorno.substring(2);
            translation += " ogni " + interval + " giorni";
        } else if (giorno.includes(",")) {
            translation += " nei giorni " + giorno + " del mese";
        } else {
            translation += " il giorno " + giorno + " del mese";
        }

        // Traduzione mese
        var mesiNomi = {
            "1": "Gennaio", "2": "Febbraio", "3": "Marzo", "4": "Aprile",
            "5": "Maggio", "6": "Giugno", "7": "Luglio", "8": "Agosto",
            "9": "Settembre", "10": "Ottobre", "11": "Novembre", "12": "Dicembre"
        };

        if (mese == "*") {
            translation += " di ogni mese";
        } else if (mese.startsWith("*/")) {
            var interval = mese.substring(2);
            translation += " ogni " + interval + " mesi";
        } else if (mese.includes(",")) {
            var mesiList = mese.split(",");
            var mesiTradotti = mesiList.map(function(m) {
                return mesiNomi[m] || m;
            });
            translation += " nei mesi di " + mesiTradotti.join(", ");
        } else {
            translation += " nel mese di " + (mesiNomi[mese] || mese);
        }

        // Traduzione giorno della settimana
        var giorniNomi = {
            "0": "Domenica", "1": "Lunedì", "2": "Martedì", "3": "Mercoledì",
            "4": "Giovedì", "5": "Venerdì", "6": "Sabato"
        };

        if (giorno_sett == "*") {
            // Non aggiungiamo nulla se è ogni giorno
        } else if (giorno_sett.includes("-")) {
            var range = giorno_sett.split("-");
            translation += " da " + (giorniNomi[range[0]] || range[0]) + " a " + (giorniNomi[range[1]] || range[1]);
        } else if (giorno_sett.includes(",")) {
            var giorniList = giorno_sett.split(",");
            var giorniTradotti = giorniList.map(function(g) {
                return giorniNomi[g] || g;
            });
            translation += " nei giorni " + giorniTradotti.join(", ");
        } else {
            translation += " di " + (giorniNomi[giorno_sett] || giorno_sett);
        }

        $("#cron-translation").text(translation);
    }

    // Collega gli eventi ai campi select e input
    $("select[name=\"minuti\"], select[name=\"ore\"], select[name=\"giorni\"], select[name=\"mesi\"], select[name=\"giorni_sett\"]").on("change", function() {
        updateField();
        updateExpression();
    });

    $("input[name=\"minuto\"], input[name=\"ora\"], input[name=\"giorno\"], input[name=\"mese\"], input[name=\"giorno_sett\"]").on("change", updateExpression);

    // Inizializza i campi select con il valore corretto
    var fieldData = [
        { select: "minuti", input: "minuto" },
        { select: "ore", input: "ora" },
        { select: "giorni", input: "giorno" },
        { select: "mesi", input: "mese" },
        { select: "giorni_sett", input: "giorno_sett" }
    ];

    fieldData.forEach(function(field) {
        var $select = $("select[name=\"" + field.select + "\"]");
        var $input = $("input[name=\"" + field.input + "\"]");
        var value = $select.val();

        if (value == "") {
            $select.selectSet(" ");
        }
    });

    // Inizializza la traduzione dell espressione cron
    var $minuto = $("input[name=\"minuto\"]").val();
    var $ora = $("input[name=\"ora\"]").val();
    var $giorno = $("input[name=\"giorno\"]").val();
    var $mese = $("input[name=\"mese\"]").val();
    var $giorno_sett = $("input[name=\"giorno_sett\"]").val();
    updateCronTranslation($minuto, $ora, $giorno, $mese, $giorno_sett);
});
</script>