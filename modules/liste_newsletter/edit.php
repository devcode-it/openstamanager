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
use Modules\ListeNewsletter\Lista;

include_once __DIR__.'/../../core.php';

$lista = Lista::find($id_record);
echo '
<form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

	<!-- DATI -->
	<div class="card card-primary">
		<div class="card-header">
			<h3 class="card-title">'.tr('Dati campagna').'</h3>
		</div>

		<div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    {[ "type": "text", "label": "'.tr('Nome').'", "name": "name", "required": 1, "value": "$title$" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    {[ "type": "textarea", "label": "'.tr('Descrizione').'", "name": "description", "required": 0, "value": "$description$" ]}
                </div>
            </div>

            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">'.tr('Generazione query dinamica').'</h3>
                </div>

                <div class="card-body">

                    <div class="row">
                        <div class="col-md-2">
                            {[ "type": "select", "label": "'.tr('Tipologia').'", "name": "tipologia", "required": 0, "values": "query=SELECT `an_tipianagrafiche`.`id`, `title` as descrizione FROM `an_tipianagrafiche` LEFT JOIN `an_tipianagrafiche_lang` ON (`an_tipianagrafiche`.`id` = `an_tipianagrafiche_lang`.`id_record` AND `an_tipianagrafiche_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') ORDER BY `title`", "help": "Seleziona una tipologia per generare la query dinamica che estrae tutte le anagrafiche di quella tipologia" ]}
                        </div>

                        <div class="col-md-2">
                            <buttton type="button" class="btn btn-primary" style="margin-top: 25px" onclick="generaQuery()">
                                <i class="fa fa-magic"></i> '.tr('Genera').'
                            </button>
                        </div>

                    </div>

                    <div class="row">
                        <div class="col-md-12">
                        '.input([
            'type' => 'textarea',
            'label' => tr('Query dinamica'),
            'name' => 'query',
            'required' => 0,
            'value' => $lista->query,
            'help' => tr("La query SQL deve restituire gli identificativi delle anagrafiche da inserire nella lista, sotto un campo di nome ''id''").'. <br>'.tr('Per esempio: _SQL_', [
                '_SQL_' => 'SELECT idanagrafica AS id, \'Modules\\\\Anagrafiche\\\\Anagrafica\' AS tipo FROM an_anagrafiche',
            ]).'. <br>'.tr('Sono supportati i seguenti oggetti: _LIST_', [
                '_LIST_' => implode(', ', [
                    slashes(Modules\Anagrafiche\Anagrafica::class),
                    slashes(Modules\Anagrafiche\Sede::class),
                    slashes(Modules\Anagrafiche\Referente::class),
                ]),
            ]).'.',
        ]).'
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>
</form>

<form action="" method="post" id="receivers-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="add_receivers">

	<!-- Destinatari -->
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">'.tr('Aggiunta destinatari').'</h3>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    {[ "type": "select", "label": "'.tr('Destinatari').'", "name": "receivers[]", "ajax-source": "destinatari_newsletter", "multiple": 1, "disabled": '.intval(!empty($lista->query)).' ]}
                </div>
            </div>

            <div class="row pull-right">
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-plus"></i> '.tr('Aggiungi').'
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>';

if ($lista) {
    $numero_destinatari = $lista->destinatari()->count();
    $destinatari_senza_mail = $lista->getNumeroDestinatariSenzaEmail();

    echo '
    <!-- Destinatari -->
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">
                '.tr('Destinatari').'
                <span> ('.$numero_destinatari.')</span> <div class="float-right d-none d-sm-inline" >
                '.(($destinatari_senza_mail > 0) ? ' <span title="'.tr('Indirizzi e-mail mancanti').'" class="tip badge badge-danger clickable">'.$destinatari_senza_mail.'</span>' : '')
        .'<span title="'.tr('Indirizzi e-mail senza consenso per newsletter').'" class="tip badge badge-warning clickable" id="numero_consenso_disabilitato"></span></div>
            </h3>
        </div>

        <div class="card-body">
            <table class="table table-hover table-sm table-bordered" id="destinatari">
                <thead>
                    <tr>
                        <th>'.tr('Ragione sociale').'</th>
                        <th>'.tr('Tipo').'</th>
                        <th>'.tr('Tipologia').'</th>
                        <th class="text-center">'.tr('E-mail').'</th>
                        <th class="text-center">'.tr('Newsletter').'</th>
                        <th class="text-center" width="60">#</th>
                    </tr>
                </thead>
            </table>

            <a class="btn btn-danger ask pull-right" data-backto="record-edit" data-op="remove_all_receivers">
                <i class="fa fa-trash"></i> '.tr('Elimina tutti').'
            </a>
        </div>
    </div>

    <a class="btn btn-danger ask" data-backto="record-list">
        <i class="fa fa-trash"></i> '.tr('Elimina').'
    </a>
    
    <script>
    globals.newsletter = {
        senza_consenso: "'.$lista->getNumeroDestinatariSenzaConsenso().'",
        table_url: "'.Module::where('name', 'Newsletter')->first()->fileurl('ajax/table.php').'?id_list='.$id_record.'",
    };

    $(document).ready(function() {
        const senza_consenso = $("#numero_consenso_disabilitato");
        if (globals.newsletter.senza_consenso > 0) {
            senza_consenso.text(globals.newsletter.senza_consenso);
        } else {
            senza_consenso.hide();
        }

        const table = $("#destinatari").DataTable({
            language: globals.translations.datatables,
            retrieve: true,
            ordering: false,
            searching: true,
            paging: true,
            order: [],
            lengthChange: false,
            processing: true,
            serverSide: true,
            ajax: {
                url: globals.newsletter.table_url,
                type: "GET",
                dataSrc: "data",
            },
            searchDelay: 500,
            pageLength: 50,
        });

        table.on("processing.dt", function (e, settings, processing) {
            if (processing) {
                $("#mini-loader").show();
            } else {
                $("#mini-loader").hide();
            }
        });
    });
    </script>';
}

?>

<script>

function generaQuery() {
    var tipologia = $("#tipologia").val();


    var query = "SELECT an_anagrafiche.idanagrafica AS id, 'Modules\\\\Anagrafiche\\\\Anagrafica' AS tipo FROM an_anagrafiche INNER JOIN an_tipianagrafiche_anagrafiche ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.id WHERE deleted_at IS NULL AND email!=''";

    if(tipologia) {
        query += " AND an_tipianagrafiche.id="+tipologia;
    }

    $('#query').val(query);
}
</script>';
