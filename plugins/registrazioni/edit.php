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

use Models\Module;

if ($id_module == Module::where('name', 'Fatture di acquisto')->first()->id) {
    $conti = 'conti-acquisti';
} else {
    $conti = 'conti-vendite';
}
$optionsConti = AJAX::select($conti, [], null, 0, 10000);

$conti_cespiti = $dbo->fetchArray('SELECT co_pianodeiconti3.id, CONCAT(co_pianodeiconti2.numero, ".", co_pianodeiconti3.numero, " - ", co_pianodeiconti3.descrizione) AS descrizione FROM co_pianodeiconti3 INNER JOIN co_pianodeiconti2 ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id WHERE idpianodeiconti2='.prepare(setting('Conto predefinito per i cespiti')));
$optionsConti_cespiti['results'] = $conti_cespiti;

echo '
<form action="" method="post" role="form">
    <input type="hidden" name="id_module" value="'.$id_module.'">
    <input type="hidden" name="id_plugin" value="'.$id_plugin.'">
    <input type="hidden" name="id_record" value="'.$id_record.'">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="change-conto">

    <div class="row">
        <div class="col-md-12 pull-right">
            <button type="button" class="btn btn-info btn-sm pull-right" onclick="copy()"><i class="fa fa-copy"></i> '.tr('Copia conto dalla prima riga valorizzata').'</button>
        </div>
    </div>
    <br>
    <div class="table-responsive">
        <table class="table table-striped table-hover table-sm table-bordered">
            <thead>
                <tr>
                    <th width="35" class="text-center" >'.tr('#').'</th>
                    <th>'.tr('Descrizione').'</th>
                    <th class="text-center" width="100">'.tr('Q.tà').'</th>
                    <th class="text-center" width="140">'.tr('Prezzo unitario').'</th>
                    <th width="450">'.tr('Conto').'</th>';
                if ($dir == 'uscita') {
                    echo '
                    <th width="200">'.tr('Cespite').'</th>';
                }
                echo '
                </tr>
            </thead>
            <tbody class="sortable">';

// Righe documento
if (!empty($fattura)) {
    $righe = $fattura->getRighe();
    $num = 0;
    foreach ($righe as $riga) {
        ++$num;

        if (!$riga->isDescrizione()) {
            echo '
                <tr>
                    <td class="text-center">
                        '.$num.'
                    </td>

                    <td>';

            if ($riga->isArticolo()) {
                echo Modules::link('Articoli', $riga->idarticolo, $riga->codice.' - '.$riga->descrizione);
            } else {
                echo nl2br((string) $riga->descrizione);
            }

            echo '
                    </td>';

            // Quantità e unità di misura
            echo '
                    <td class="text-center">
                        '.numberFormat($riga->qta, 'qta').' '.$riga->um.'
                    </td>';

            // Prezzi unitari
            echo '
                    <td class="text-right">
                        '.moneyFormat($riga->prezzo_unitario_corrente);

            if ($dir == 'entrata' && $riga->costo_unitario != 0) {
                echo '
                        <br><small class="text-muted">
                            '.tr('Acquisto').': '.moneyFormat($riga->costo_unitario).'
                        </small>';
            }

            if (abs($riga->sconto_unitario) > 0) {
                $text = discountInfo($riga);

                echo '
                        <br> <span class="right badge badge-danger">'.$text.'</small>';
            }

            echo '
                    </td>

                    <td>
                        <div id="select-conto-standard-'.$riga['id'].'" '.($riga->is_cespite ? 'style="display:none;"' : '').'>
                            {[ "type": "select", "name": "idconto['.$riga['id'].']", "required": "'.($riga->is_cespite ? 0 : 1).'", "value": "'.$riga->id_conto.'", "values": '.json_encode($optionsConti['results']).', "class": "unblockable" ]}
                        </div>
                        <div id="select-conto-cespite-'.$riga['id'].'" '.(!$riga->is_cespite ? 'style="display:none;"' : '').'>
                            {[ "type": "select", "name": "idconto_cespiti['.$riga['id'].']", "required": "'.($riga->is_cespite ? 1 : 0).'", "value": "'.$riga->id_conto.'", "values": '.json_encode($optionsConti_cespiti['results']).', "class": "unblockable" ]}
                        </div>
                    </td>';
                if ($dir == 'uscita') {
                    $has_ammortamento_cespite = $dbo->selectOne('co_righe_ammortamenti', '*', ['id_riga' => $riga->id])['id'];
                    echo '
                    <td>
                        {[ "type": "checkbox", "name": "is_cespite['.$riga['id'].']", "value": "'.$riga->is_cespite.'", "values": "Sì,No", "class": "'.($has_ammortamento_cespite ? '' : 'unblockable').'", "disabled": "'.($has_ammortamento_cespite ? 1 : 0).'" ]}
                    </td>';
                }
                echo '
                </tr>';
        }
    }
}

echo '
            </tbody>
        </table>
    </div>
    <div class="row">
        <div class="col-md-12 text-right">
            <button type="submit" class="btn btn-success">
                <i class="fa fa-check"></i> '.tr('Salva').'
            </button>
        </div>
    </div>
</form>

<script>
function copy() {
    let conti = $("select[name^=idconto]");

    // Individuazione del primo conto selezionato
    let conto_selezionato = null;
    for (const conto of conti) {
        const data = $(conto).selectData();
        if (data && data.id) {
            conto_selezionato = data;
            break;
        }
    }

    // Selezione generale per il conto
    if (conto_selezionato) {
        conti.each(function() {
            $(this).selectSetNew(conto_selezionato.id, conto_selezionato.text, conto_selezionato);
        });
    }
}

// Funzione per validare i conti prima del submit
function validateConti() {
    let valid = true;
    let errors = [];

    try {
        // Controlla ogni riga
        $("input[name^=\'is_cespite\']").each(function() {
            const id = $(this).attr("name").match(/\[(.*?)\]/)[1];
            const is_cespite = $(this).val() == 1;

            let conto_selezionato = null;

            if (is_cespite) {
                // Verifica conto cespite
                const select_cespite = $("#select-conto-cespite-" + id).find("select");
                if (select_cespite.length > 0) {
                    conto_selezionato = select_cespite.val();

                    if (!conto_selezionato || conto_selezionato == \'\') {
                        valid = false;
                        errors.push(\'Riga \' + id + \': selezionare un conto cespite\');
                        select_cespite.addClass(\'parsley-error\');
                    } else {
                        select_cespite.removeClass(\'parsley-error\');
                    }
                }
            } else {
                // Verifica conto standard
                const select_standard = $("#select-conto-standard-" + id).find("select");
                if (select_standard.length > 0) {
                    conto_selezionato = select_standard.val();

                    if (!conto_selezionato || conto_selezionato == \'\') {
                        valid = false;
                        errors.push(\'Riga \' + id + \': selezionare un conto\');
                        select_standard.addClass(\'parsley-error\');
                    } else {
                        select_standard.removeClass(\'parsley-error\');
                    }
                }
            }
        });

        if (!valid && errors.length > 0) {
            swal({
                type: "error",
                title: "<?php echo tr(\'Errori di validazione\'); ?>",
                html: "<?php echo tr(\'Correggere i seguenti errori:\'); ?><br><ul><li>" + errors.join("</li><li>") + "</li></ul>"
            });
        }
    } catch (e) {
        console.error(\'Errore nella validazione dei conti:\', e);
        // In caso di errore, permetti il submit normale
        valid = true;
    }

    return valid;
}

// Gestione del reset del conto quando si cambia lo stato del cespite
$(document).ready(function() {
    // Verifica che Parsley sia disponibile
    if (typeof window.Parsley === \'undefined\') {
        return;
    }

    // Aggiungi validazione personalizzata solo se siamo nel plugin registrazioni
    if (window.location.href.indexOf(\'id_plugin=<?php echo $id_plugin; ?>\') > -1 &&
        $(\'input[name="op"][value="change-conto"]\').length > 0) {

        // Override del submit del form SOLO per il plugin registrazioni
        $(\'form[action=""]\').off(\'submit.registrazioni\').on(\'submit.registrazioni\', function(e) {
            // Prima validazione Parsley standard
            try {
                const parsleyInstance = $(this).parsley();
                if (parsleyInstance && typeof parsleyInstance.validate === \'function\') {
                    if (!parsleyInstance.validate()) {
                        return false;
                    }
                }
            } catch (e) {
            }

            // Poi validazione personalizzata per i conti
            if (!validateConti()) {
                return false;
            }

            // Se tutto è valido, rimuovi il listener e procedi con il submit
            $(this).off(\'submit.registrazioni\');

            // Previeni il loop infinito
            e.preventDefault();
            e.stopPropagation();

            // Submit manuale
            this.submit();
            return false;
        });
    }

    $("input[name^=\'is_cespite\']").change(function() {
        const id = $(this).attr("name").match(/\[(.*?)\]/)[1];
        const is_cespite = $(this).val() == 1;

        // Rimuovi eventuali errori precedenti
        $("#select-conto-standard-" + id).find("select").removeClass(\'parsley-error\');
        $("#select-conto-cespite-" + id).find("select").removeClass(\'parsley-error\');

        // Mostra/nascondi i selettori appropriati
        if (is_cespite) {
            $("#select-conto-standard-" + id).hide();
            $("#select-conto-standard-" + id).find("select").attr("required", false);
            $("#select-conto-cespite-" + id).show();
            $("#select-conto-cespite-" + id).find("select").attr("required", true);

            // Verifica se ci sono conti cespiti disponibili
            const conti_cespiti_disponibili = $("#select-conto-cespite-" + id).find("select option").length;
            if (conti_cespiti_disponibili <= 1) { // Solo opzione vuota
                swal({
                    type: "warning",
                    title: "<?php echo tr(\'Attenzione\'); ?>",
                    text: "<?php echo tr(\'Non ci sono conti cespiti configurati nel sistema. Configurare prima i conti cespiti nelle impostazioni.\'); ?>"
                });
                // Ripristina lo stato precedente
                $(this).val(0).trigger(\'change\');
                return false;
            }
        } else {
            $("#select-conto-standard-" + id).show();
            $("#select-conto-standard-" + id).find("select").attr("required", true);
            $("#select-conto-cespite-" + id).hide();
            $("#select-conto-cespite-" + id).find("select").attr("required", false);
        }

        // Aggiorna la validazione Parsley
        try {
            const form = $(\'form[action=""]\');
            if (form.length > 0 && form.parsley && typeof form.parsley === \'function\') {
                const parsleyInstance = form.parsley();
                if (parsleyInstance && typeof parsleyInstance.refresh === \'function\') {
                    parsleyInstance.refresh();
                }
            }
        } catch (e) {
        } 
    });

    // Gestione del cambio di selezione nei conti per rimuovere errori
    $("select[name^=\'idconto\']").change(function() {
        $(this).removeClass(\'parsley-error\');
    });
});
</script>';
