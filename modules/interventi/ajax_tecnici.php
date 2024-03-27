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
use Models\Module;
use Models\User;

$show_costi = true;
// Limitazione delle azioni dei tecnici
if ($user['gruppo'] == 'Tecnici') {
    $show_costi = !empty($user['idanagrafica']) && setting('Mostra i prezzi al tecnico');
}

// Stato dell'intervento
$rss = $dbo->fetchArray('SELECT `is_completato` AS flag_completato FROM `in_statiintervento` INNER JOIN `in_interventi` ON `in_statiintervento`.`id` = `in_interventi`.`idstatointervento` WHERE `in_interventi`.`id`='.prepare($id_record).')');
$is_completato = $rss[0]['flag_completato'];

// Sessioni dell'intervento
$query = 'SELECT
        `in_interventi_tecnici`.*,
        (`in_interventi_tecnici`.`prezzo_ore_unitario` * `in_interventi_tecnici`.`ore` - `in_interventi_tecnici`.`sconto`) AS prezzo_ore_consuntivo,
        (`in_interventi_tecnici`.`prezzo_km_unitario` * `in_interventi_tecnici`.`km` - `in_interventi_tecnici`.`scontokm`) AS prezzo_km_consuntivo,
        (`in_interventi_tecnici`.`prezzo_ore_unitario_tecnico` * `in_interventi_tecnici`.`ore`) AS prezzo_ore_consuntivo,
        (`in_interventi_tecnici`.`prezzo_km_unitario_tecnico` * `in_interventi_tecnici`.`km`) AS prezzo_km_consuntivo,
        `an_anagrafiche`.`ragione_sociale`,
        `an_anagrafiche`.`deleted_at` AS anagrafica_deleted_at,
        `in_tipiintervento`.`deleted_at` AS tipo_deleted_at,
        `in_tipiintervento_lang`.`name` AS descrizione_tipo,
        `in_interventi_tecnici`.`tipo_scontokm` AS tipo_sconto_km,
        `user`.`id` AS id_user
    FROM
        `in_interventi_tecnici` 
        INNER JOIN `an_anagrafiche` ON `in_interventi_tecnici`.`idtecnico` = `an_anagrafiche`.`idanagrafica`
        LEFT JOIN (SELECT `zz_users`.`idanagrafica`, `zz_users`.`id` FROM `zz_users` GROUP BY `zz_users`.`idanagrafica`) AS user ON `user`.`idanagrafica` = `an_anagrafiche`.`idanagrafica` 
        INNER JOIN `in_tipiintervento` ON `in_interventi_tecnici`.`idtipointervento` = `in_tipiintervento`.`id`
        LEFT JOIN `in_tipiintervento_lang` ON (`in_tipiintervento`.`id` = `in_tipiintervento_lang`.`id_record` AND `in_tipiintervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
    WHERE
        `in_interventi_tecnici`.`idintervento`='.prepare($id_record).'
    ORDER BY
        `ragione_sociale` ASC,
        `in_interventi_tecnici`.`orario_inizio` ASC,
        `in_interventi_tecnici`.`id` ASC';
$sessioni = $dbo->fetchArray($query);

$prev_tecnico = '';
if (!empty($sessioni)) {
    foreach ($sessioni as $key => $sessione) {
        // Intestazione tecnico
        if ($prev_tecnico != $sessione['ragione_sociale']) {
            $prev_tecnico = $sessione['ragione_sociale'];

            echo '
<div class="table-responsive">
    <table class="table table-striped table-hover table-condensed">
        <tr><th>';

            if ($sessione['id_user']) {
                $user = User::where('idanagrafica', $sessione['idtecnico'])->orderByRaw('CASE WHEN idgruppo = 2 THEN -1 ELSE idgruppo END')->first();
                echo '
                <img class="attachment-img tip" src="'.$user->photo.'" title="'.$user->nome_completo.'">';
            } else {
                echo '
                <i class="fa fa-user-circle-o attachment-img tip" title="'.$sessione['ragione_sociale'].'"></i>';
            }

            echo '
            '.$sessione['ragione_sociale'].' '.(($sessione['anagrafica_deleted_at']) ? '<small class="text-danger"><em>('.tr('Eliminato').')</em></small>' : '').'</th>
            <th width="15%">'.tr('Orario inizio').'</th>
            <th width="15%">'.tr('Orario fine').'</th>
            <th width="2%"> </th>
            <th width="10%">'.tr('Ore').'</th>
            <th width="12%">'.tr('Km').'</th>';

            if ($show_costi) {
                echo '
            <th width="10%">'.tr('Sconto ore').'</th>
            <th width="10%">'.tr('Sconto km').'</th>';
            }

            if (!$is_completato) {
                echo '
            <th width="100" class="text-center">&nbsp;</th>';
            }

            echo '
        </tr>';
        }

        // Lettura costi unitari salvati al momento dell'intervento
        $sconto = $sessione['sconto'];
        $scontokm = $sessione['scontokm'];

        $costo_ore_unitario = $sessione['prezzo_ore_unitario'];
        $costo_km_unitario = $sessione['prezzo_km_unitario'];
        $costo_dirittochiamata = $sessione['prezzo_dirittochiamata'];

        $costo_ore_unitario_tecnico = $sessione['prezzo_ore_unitario_tecnico'];
        $costo_km_unitario_tecnico = $sessione['prezzo_km_unitario_tecnico'];
        $costo_dirittochiamata_tecnico = $sessione['prezzo_dirittochiamata_tecnico'];

        $costo_km_consuntivo_tecnico = $sessione['prezzo_km_consuntivo_tecnico'];
        $costo_ore_consuntivo_tecnico = $sessione['prezzo_ore_consuntivo_tecnico'];
        $costo_km_consuntivo = $sessione['prezzo_km_consuntivo'];
        $costo_ore_consuntivo = $sessione['prezzo_ore_consuntivo'];

        $ore = $sessione['ore'];
        $km = $sessione['km'];

        // Tipologia
        echo '
        <tr data-id="'.$sessione['id'].'">
            <td>
                '.$sessione['descrizione_tipo'].' '.(($sessione['tipo_deleted_at']) ? '<small class="text-danger"><em>('.tr('Eliminato').')</em></small>' : '').'
            </td>';

        // Orario di inizio
        echo '
            <td>
            {[ "type": "timestamp", "name": "data_inizio_'.$sessione['id'].'", "required": 1, "value": "'.$sessione['orario_inizio'].'", "disabled": "'.$block_edit.'" ]}
            </td>';

        // Orario di fine
        echo '
            <td>
            {[ "type": "timestamp", "name": "data_fine_'.$sessione['id'].'", "required": 1, "value": "'.$sessione['orario_fine'].'",  "disabled": "'.$block_edit.'" ]}
            </td>';

        // ORE
        echo '
        <td style="border-right:1px solid #aaa;">'.((Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $sessione['orario_inizio'])->eq(Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $sessione['orario_fine'])) || Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $sessione['orario_inizio'])->gt(Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $sessione['orario_fine']))) ? '<i  title="'.tr('Questa sessione non ha una durata valida.').'" class="fa fa-warning tip text-warning"></i>' : '').' 
        </td>
        <td style="border-right:1px solid #aaa;"> 
            {[ "type": "number", "name": "ore_'.$sessione['id'].'", "required": 1, "value": "'.numberFormat($ore, 'qta').'", "disabled": "1" ]}

                <div class="extra hide">
                    <table class="table table-condensed table-bordered">
                        <tr>
                            <th class="text-danger">'.tr('Costo').':</th>
                            <td class="text-right">
                                '.Translator::numberToLocale($costo_ore_consuntivo_tecnico)."
                                <small class='help-block'>".Translator::numberToLocale($costo_ore_unitario_tecnico).'x'.Translator::numberToLocale($ore).'<br>+'.Translator::numberToLocale($costo_dirittochiamata_tecnico).'</small>
                            </td>
                        </tr>
                        <tr>
                            <th>'.tr('Addebito').':</th>
                            <td class="text-right">
                                '.Translator::numberToLocale($costo_ore_consuntivo).'
                                <small class="help-block">'.Translator::numberToLocale($costo_ore_unitario).'x'.Translator::numberToLocale($ore).'<br>+'.Translator::numberToLocale($costo_dirittochiamata).'</small>
                            </td>
                        </tr>
                        <tr>
                            <th>'.tr('Scontato').':</th>
                            <td class="text-right">'.Translator::numberToLocale($costo_ore_consuntivo - $sconto).'</td>
                        </tr>
                    </table>
                </div>
            </td>';

        // KM
        echo '
            <td style="border-right:1px solid #aaa;">
                {[ "type": "number", "name": "sessione_km_'.$sessione['id'].'", "required": 1, "value": "'.numberFormat($sessione['km'], 'qta').'", "onchange": "aggiornaSessioneInline($(this).closest(\'tr\').data(\'id\'))", "disabled": "'.$block_edit.'" ]}

                <div class="extra hide">
                    <table class="table table-condensed table-bordered">
                    <tr>
                        <th class="text-danger">'.tr('Costo').':</th>
                        <td class="text-right">
                            '.Translator::numberToLocale($costo_km_consuntivo_tecnico).'
                            <small class="help-block">
                                '.Translator::numberToLocale($costo_km_unitario_tecnico).'x'.Translator::numberToLocale($km).'
                            </small><br>
                        </td>
                    </tr>
                    <tr>
                        <th>'.tr('Addebito').':</th>
                        <td class="text-right">
                            '.Translator::numberToLocale($costo_km_consuntivo).'
                            <small class="help-block">
                                '.Translator::numberToLocale($costo_km_unitario).'x'.Translator::numberToLocale($km).'
                            </small><br>
                        </td>
                    </tr>
                    <tr>
                        <th>'.tr('Scontato').':</th>
                        <td class="text-right">'.Translator::numberToLocale($costo_km_consuntivo - $scontokm).'</td>
                    </tr>
                    </table>
                </div>
            </td>';

        // Sconto ore
        if ($show_costi) {
            $tipo_sconto = (setting('Tipo di sconto predefinito') == '%' ? 'PRC' : 'UNT');
            echo '
            <td style="border-right:1px solid #aaa;">
                {[ "type": "number", "name": "sconto_unitario_'.$sessione['id'].'", "value": "'.Translator::numberToLocale($sessione['sconto_unitario']).'", "onchange": "aggiornaSessioneInline($(this).closest(\'tr\').data(\'id\'))", "icon-after": "choice|untprc|'.($sessione['tipo_sconto'] ? $sessione['tipo_sconto'] : $tipo_sconto).'", "disabled": "'.$block_edit.'" ]}
            </td>';
        }

        // Sconto km
        if ($show_costi) {
            echo '
            <td style="border-right:1px solid #aaa;">
                {[ "type": "number", "name": "scontokm_unitario_'.$sessione['id'].'", "value": "'.Translator::numberToLocale($sessione['scontokm_unitario']).'", "onchange": "aggiornaSessioneInline($(this).closest(\'tr\').data(\'id\'))", "icon-after": "choice|untprc|'.($sessione['tipo_sconto_km'] ? $sessione['tipo_sconto_km'] : $tipo_sconto).'", "disabled": "'.$block_edit.'" ]}
            </td>';
        }

        // Pulsante per la sessione
        if (!$is_completato) {
            echo '
            <td class="text-center">
                <button type="button" class="btn btn-xs btn-primary tip"  title="'.tr('Salva e duplica sessione').'" onclick="copySessione(this)">
                    <i class="fa fa-files-o"></i>
                </button>

                <button type="button" class="btn btn-xs btn-warning tip" title="'.tr('Salva e modifica sessione').'" onclick="modificaSessione(this)">
                    <i class="fa fa-edit"></i>
                </button>

				<button type="button" class="btn btn-xs btn-danger tip" id="delbtn_'.$sessione['id'].'" onclick="elimina_sessione(\''.$sessione['id'].'\');" title="'.tr('Elimina sessione').'" class="only_rw"><i class="fa fa-trash"></i></button>
            </td>';
        }

        echo '
        </tr>';

        // Intestazione tecnico
        if (!isset($sessioni[$key + 1]['ragione_sociale']) || $sessione['ragione_sociale'] != $sessioni[$key + 1]['ragione_sociale']) {
            echo '
    </table>
</div>';
        }
    }
} else {
    echo '
<div class="alert alert-info">
    <i class="fa fa-info-circle"></i> '.tr('Nessun tecnico assegnato').'.
</div>';
}

echo '
<div id="info-conflitti"></div>';

if (!$is_completato) {
    echo '
<!-- AGGIUNTA TECNICO -->
<div class="row">
    <div class="col-md-2">
        <label>&nbsp;</label>
        <button type="button" class="btn btn-default btn-block" onclick="add_sessioni($(this))">
            <i class="fa fa-users"></i> '.tr('Inserimento massivo').'
        </button>
    </div>

    <div class="col-md-offset-4 col-md-4">
        {[ "type": "select", "label": "'.tr('Tecnico').'", "name": "nuovo_tecnico", "placeholder": "'.tr('Seleziona un tecnico').'", "ajax-source": "tecnici", "icon-after": "add|'.(new Module())->getByField('name', 'Anagrafiche', \Models\Locale::getPredefined()->id).'|tipoanagrafica=Tecnico&readonly_tipo=1" ]}
    </div>

    <div class="col-md-2">
        <label>&nbsp;</label>
        <button type="button" class="btn btn-primary btn-block" onclick="if($(\'#nuovo_tecnico\').val()){ add_tecnici($(\'#nuovo_tecnico\').val()); }else{ swal(\''.tr('Attenzione').'\', \''.tr('Seleziona il tecnico da aggiungere').'.\', \'warning\'); $(\'#nuovo_tecnico\').focus(); }">
            <i class="fa fa-plus"></i> '.tr('Aggiungi').'
        </button>
    </div>
</div>';
}

echo '
<script src="'.base_path().'/assets/dist/js/functions.min.js"></script>
<script>$(document).ready(init)</script>

<script type="text/javascript">
async function modificaSessione(button) {
    var riga = $(button).closest("tr");
    var id = riga.data("id");

    // Salvataggio via AJAX
    await salvaForm("#edit-form", {}, button);

    // Chiusura tooltip
    if ($(button).hasClass("tooltipstered"))
        $(button).tooltipster("close");

    // Apertura modal
    openModal("'.tr('Modifica sessione').'", "'.$module->fileurl('modals/manage_sessione.php').'?id_module=" + globals.id_module + "&id_record=" + globals.id_record + "&id_sessione=" + id);
}

function calcolaConflittiTecnici() {
    let tecnici = [input("nuovo_tecnico").get()];
    let inizio = moment().startOf("hour");

    return $("#info-conflitti").load("'.$module->fileurl('occupazione_tecnici.php').'", {
        "id_module": globals.id_module,
        "id_record": globals.id_record,
        "tecnici[]": tecnici,
        "inizio": inizio.format("YYYY-MM-DD HH:mm:ss"),
        "fine": inizio.add(1, "hours").format("YYYY-MM-DD HH:mm:ss"),
    });
}

input("nuovo_tecnico").change(function() {
    calcolaConflittiTecnici();
});

$(document).ready(function() {
    calcolaConflittiTecnici();
    ';
if (empty($sessioni)) {
    echo '
    $(".btn-details").attr("disabled", true);
    $(".btn-details").addClass("disabled");
    $("#showall_dettagli").removeClass("hide");
    $("#dontshowall_dettagli").addClass("hide");';
} else {
    echo '
    $(".btn-details").attr("disabled", false);
    $(".btn-details").removeClass("disabled");';
}

echo '
    $("[id^=data_inizio_]").on("dp.change", function (e) {
        let data_fine = $("#data_fine_" + $(this).closest("tr").data("id"));
        if(data_fine.data("DateTimePicker").date() < e.date){
            data_fine.data("DateTimePicker").date(e.date);
        }
    });

    $("[id^=data_fine_]").on("dp.change", function (e) {
        let data_inizio = $("#data_inizio_" + $(this).closest("tr").data("id"));
        if(data_inizio.data("DateTimePicker").date() > e.date){
            data_inizio.data("DateTimePicker").date(e.date);
        }
    });
});

/*
* Aggiunge una nuova riga per la sessione di lavoro in base al tecnico selezionato.
*/
function add_tecnici(id_tecnico) {
    $.ajax({
        url: globals.rootdir + "/actions.php",
        beforeSubmit: function(arr, $form, options) {
            return $form.parsley().validate();
        },
        data: {
            id_module: globals.id_module,
            id_record: globals.id_record,
            op: "add_sessione",
            id_tecnico: id_tecnico,
        },
        type: "post",
        success: function() {
            caricaTecnici();
            caricaCosti();

            calcolaConflittiTecnici();
        }
    });
}

/*
* Aggiunge sessioni massivamente
*/
async function add_sessioni(button) {
    // Salvataggio via AJAX
    await salvaForm("#edit-form", {}, button);

    // Chiusura tooltip
    if ($(button).hasClass("tooltipstered"))
        $(button).tooltipster("close");

    // Apertura modal
    openModal("'.tr('Aggiungi sessioni').'", "'.$module->fileurl('modals/add_sessioni.php').'?id_module=" + globals.id_module + "&id_record=" + globals.id_record);
}

/*
* Rimuove la sessione di lavoro dall\'intervento.
*/
function elimina_sessione(id_sessione) {

    swal({
        title: "'.tr('Eliminare la sessione di lavoro?').'",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "'.tr('Elimina').'"
    }).then(function (result) {
        $.ajax({
            url: globals.rootdir + "/actions.php",
            data: {
                id_module: globals.id_module,
                id_record: globals.id_record,
                op: "delete_sessione",
                id_sessione: id_sessione,
            },
            type: "post",
            success: function() {
                caricaTecnici();
                caricaCosti();
                calcolaConflittiTecnici();
            }
        });
    }).catch(swal.noop);

}

async function copySessione(button) {
    var riga = $(button).closest("tr");
    var id = riga.data("id");

    // Salvataggio via AJAX
    await salvaForm("#edit-form", {}, button);

    // Chiusura tooltip
    if ($(button).hasClass("tooltipstered"))
        $(button).tooltipster("close");

    // Apertura modal
    openModal("'.tr('Copia sessione').'", "'.$module->fileurl('modals/copy_sessione.php').'?id_module=" + globals.id_module + "&id_record=" + globals.id_record + "&id_sessione=" + id);
}

$("#tecnici .tipo_icon_after").on("change", function() {
    aggiornaSessioneInline($(this).closest("tr").data("id"));
});

$("[id^=data_inizio_], [id^=data_fine_]").on("dp.hide", function (e) {
    aggiornaSessioneInline($(this).closest("tr").data("id"));
});

function caricaTecnici() {
    let container = $("#tecnici");

    localLoading(container, true);
    $.get("'.$structure->fileurl('ajax_tecnici.php').'?id_module='.$id_module.'&id_record='.$id_record.'", function(data) {
        container.html(data);
        localLoading(container, false);
    });
}

function aggiornaSessioneInline(id) {
    var id_sessione = id;
    var data_inizio = $("#data_inizio_" + id_sessione).val();
    var data_fine = $("#data_fine_" + id_sessione).val();
    var km = $("#sessione_km_" + id_sessione).val();
    var sconto_unitario = $("#sconto_unitario_" + id_sessione).val();
    var tipo_sconto = $("[id^=tipo_sconto_unitario_" + id_sessione + "]").val()
    var scontokm_unitario = $("#scontokm_unitario_" + id_sessione).val();
    var tipo_sconto_km =$("[id^=tipo_scontokm_unitario_" + id_sessione + "]").val()

    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "POST",
        data: {
            id_module: globals.id_module,
            id_record: globals.id_record,
            op: "update_inline_sessione",
            id_sessione: id_sessione,
            data_inizio: data_inizio,
            data_fine: data_fine,
            km: km,
            sconto_unitario: sconto_unitario,
            tipo_sconto: tipo_sconto,
            scontokm_unitario: scontokm_unitario,
            tipo_sconto_km: tipo_sconto_km,
        },
        success: function(response) {
            caricaTecnici();
            caricaCosti();
            renderMessages();
        },
        error: function(xhr, status, error) {
            caricaCosti();
            renderMessages();
        }
    });
}
</script>';
