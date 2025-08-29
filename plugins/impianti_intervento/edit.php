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

$id_modulo_impianti = Module::where('name', 'Impianti')->first()->id;
// Blocco della modifica impianti se l'intervento è completato
$dati_intervento = $dbo->fetchArray('SELECT `in_statiintervento`.`is_bloccato` FROM `in_statiintervento` INNER JOIN `in_interventi` ON `in_statiintervento`.`id` = `in_interventi`.`idstatointervento` WHERE `in_interventi`.`id`='.prepare($id_record));
$is_bloccato = $dati_intervento[0]['is_bloccato'];

if ($is_bloccato) {
    $readonly = 'readonly';
    $disabled = 'disabled';
} else {
    $readonly = '';
    $disabled = '';
}

/*
 * Aggiunta impianti all'intervento
*/
// Elenco impianti collegati all'intervento
$impianti = $dbo->fetchArray('SELECT idimpianto FROM my_impianti_interventi WHERE idintervento='.prepare($id_record));
$impianti = !empty($impianti) ? array_column($impianti, 'idimpianto') : [];

// Elenco sedi
$sedi = $dbo->fetchArray('SELECT id, nomesede, citta FROM an_sedi WHERE idanagrafica='.prepare($record['idanagrafica'])." UNION SELECT 0, 'Sede legale', '' ORDER BY id");

echo '
<div class="card card-outline card-primary shadow mb-4">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fa fa-cogs text-primary mr-2"></i>
            '.tr('Gestione Impianti').'
        </h3>
    </div>
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-4">
                <label class="control-label">'.tr('Seleziona Impianto').'</label>
                <div style="margin-top: 5px;">
                    {[ "type": "select", "name": "id_impianto_add", "ajax-source": "impianti-cliente", "select-options": {"idanagrafica": '.$record['idanagrafica'].', "idsede_destinazione": '.($record['idsede_destinazione'] ?: '0').', "idintervento": '.$id_record.', "idcontratto": "'.$record['idcontratto'].'"}, "extra": "'.$readonly.'", "icon-after": "add|'.$id_modulo_impianti.'|id_anagrafica='.$record['idanagrafica'].'" ]}
                </div>
            </div>

            <div class="col-md-2">
                <button title="'.tr('Aggiungi impianto all\'attività').'" class="btn btn-primary tip" type="button" onclick="addImpianto()" '.$disabled.' style="margin-top: 20px;">
                    <i class="fa fa-plus"></i> '.tr('Aggiungi').'
                </button>
            </div>
            <div class="col-md-2">

            </div>
            <div class="col-md-4">
                <label class="control-label">'.tr('Cerca negli impianti collegati').'</label>
                <div class="input-group">
                    <input type="text" class="form-control unblockable" id="input-cerca" placeholder="'.tr('Matricola o nome').'...">
                    <div class="input-group-append">
                        <button type="button" class="btn btn-outline-primary" onclick="caricaImpianti()">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>';

// IMPIANTI
echo '
    <div class="row">
		<div class="col-md-12" id="righe-impianti"></div>
    </div>';

echo '
<script>
    function toggleDettagli(trigger) {
        const tr = $(trigger).closest("tr");
        const dettagli = tr.next();

        if (dettagli.css("display") === "none"){
            dettagli.show(500);
            $(trigger).children().removeClass("fa-plus");
            $(trigger).children().addClass("fa-minus");
        } else {
            dettagli.hide(500);
            $(trigger).children().removeClass("fa-minus");
            $(trigger).children().addClass("fa-plus");
        }
    }
</script>';

echo '
<script>$(document).ready(init)</script>

<script>

$(document).ready(function(){
    $("[data-toggle=\'tooltip\']").tooltip();

    caricaImpianti();

    // Aggiungi evento per ricerca con tasto Invio
    $("#input-cerca").on("keypress", function(e) {
        if (e.which === 13) { // Tasto Invio
            e.preventDefault();
            caricaImpianti();
        }
    });
});

function caricaImpianti() {
    let container = $("#righe-impianti");
    let search = $("#input-cerca").val();;

    localLoading(container, true);
    return $.get("'.$structure->fileurl('row-impianti.php').'?id_module='.$id_module.'&id_record='.$id_record.'&id_plugin='.$id_plugin.'&search=" + search, function(data) {
        container.html(data);
        localLoading(container, false);
    });
}

function addImpianto() {
    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "POST",
        data: {
            id_module: globals.id_module,
            id_plugin: '.$id_plugin.',
            id_record: globals.id_record,
            op: "add_impianto",
            id_impianto: input("id_impianto_add").get(),
        },
        success: function (response) {
            renderMessages();
            caricaImpianti();
        },
        error: function() {
            renderMessages();
            caricaImpianti();
        }
    });
    $("#id_impianto_add").selectReset();
}
</script>';
