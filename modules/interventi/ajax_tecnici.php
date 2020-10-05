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

$show_costi = true;
// Limitazione delle azioni dei tecnici
if ($user['gruppo'] == 'Tecnici') {
    $show_costi = !empty($user['idanagrafica']) && setting('Mostra i prezzi al tecnico');
}

// Stato dell'intervento
$rss = $dbo->fetchArray('SELECT is_completato AS flag_completato FROM in_statiintervento WHERE idstatointervento = (SELECT idstatointervento FROM in_interventi WHERE id='.prepare($id_record).')');
$is_completato = $rss[0]['flag_completato'];

// Sessioni dell'intervento
$query = 'SELECT in_interventi_tecnici.*, an_anagrafiche.ragione_sociale, an_anagrafiche.deleted_at AS anagrafica_deleted_at, in_tipiintervento.descrizione AS descrizione_tipo, in_interventi_tecnici.tipo_scontokm AS tipo_sconto_km FROM in_interventi_tecnici
INNER JOIN an_anagrafiche ON in_interventi_tecnici.idtecnico = an_anagrafiche.idanagrafica
INNER JOIN in_tipiintervento ON in_interventi_tecnici.idtipointervento = in_tipiintervento.idtipointervento
WHERE in_interventi_tecnici.idintervento='.prepare($id_record).' ORDER BY ragione_sociale ASC, in_interventi_tecnici.orario_inizio ASC, in_interventi_tecnici.id ASC';
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
        <tr>
            <th><i class="fa fa-user"></i> '.$sessione['ragione_sociale'].' '.(($sessione['anagrafica_deleted_at']) ? '<small class="text-danger"><em>('.tr('Eliminato').')</em></small>' : '').'</th>
            <th width="15%">'.tr('Orario inizio').'</th>
            <th width="15%">'.tr('Orario fine').'</th>
            <th width="12%">'.tr('Ore').'</th>
            <th width="12%">'.tr('Km').'</th>';

            if ($show_costi) {
                echo '
            <th width="10%">'.tr('Sconto ore').'</th>
            <th width="10%">'.tr('Sconto km').'</th>';
            }

            if (!$is_completato) {
                echo '
            <th width="100" class="text-center">#</th>';
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
                '.$sessione['descrizione_tipo'].'
            </td>';

        // Orario di inizio
        echo '
            <td>
                '.Translator::timestampToLocale($sessione['orario_inizio']).'
            </td>';

        // Orario di fine
        echo '
            <td>
                '.Translator::timestampToLocale($sessione['orario_fine']).'
            </td>';

        // ORE
        echo '
            <td style="border-right:1px solid #aaa;">
                '.Translator::numberToLocale($ore).'

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
                '.Translator::numberToLocale($km).'

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
            echo '
            <td style="border-right:1px solid #aaa;">
                '.tr('_TOT_ _TYPE_', [
                    '_TOT_' => Translator::numberToLocale($sessione['sconto_unitario']),
                    '_TYPE_' => ($sessione['tipo_sconto'] == 'PRC' ? '%' : currency()),
                ]).'
            </td>';
        }

        // Sconto km
        if ($show_costi) {
            echo '
            <td style="border-right:1px solid #aaa;">
                '.tr('_TOT_ _TYPE_', [
                    '_TOT_' => Translator::numberToLocale($sessione['scontokm_unitario']),
                    '_TYPE_' => ($sessione['tipo_sconto_km'] == 'PRC' ? '%' : currency()),
                ]).'
            </td>';
        }

        // Pulsante per la sessione
        if (!$is_completato) {
            echo '
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-warning tip" title="'.tr('Modifica sessione').'" onclick="modificaSessione(this)">
                    <i class="fa fa-edit"></i>
                </button>

				<button type="button" class="btn btn-sm btn-danger" id="delbtn_'.$sessione['id'].'" onclick="elimina_sessione(\''.$sessione['id'].'\');" title="Elimina riga" class="only_rw"><i class="fa fa-trash"></i></button>
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
    <div class="col-md-offset-6 col-md-4">
        {[ "type": "select", "label": "'.tr('Tecnico').'", "name": "nuovo_tecnico", "placeholder": "'.tr('Seleziona un tecnico').'", "ajax-source": "tecnici", "icon-after": "add|'.Modules::get('Anagrafiche')['id'].'|tipoanagrafica=Tecnico" ]}
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
<script src="'.base_path().'/assets/src/js/functions/functions.js"></script>
<script>$(document).ready(init)</script>

<script type="text/javascript">
async function modificaSessione(button) {
    var riga = $(button).closest("tr");
    var id = riga.data("id");

    // Salvataggio via AJAX
    let valid = await salvaForm(button, $("#edit-form"));

    if (valid) {
        // Chiusura tooltip
        if ($(button).hasClass("tooltipstered"))
            $(button).tooltipster("close");

        // Apertura modal
        openModal("'.tr('Modifica sessione').'", "'.$module->fileurl('modals/manage_sessione.php').'?id_module=" + globals.id_module + "&id_record=" + globals.id_record + "&id_sessione=" + id);
    }
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
* Rimuove la sessione di lavoro dall\'intervento.
*/
function elimina_sessione(id_sessione) {
    if (confirm("Eliminare sessione di lavoro?")) {
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
    }
}
</script>';
