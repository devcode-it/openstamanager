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
use Modules\Contratti\Stato;

$id_anagrafica = !empty(get('idanagrafica')) ? get('idanagrafica') : '';

$stati = get('pianificabile') ? 'SELECT `co_staticontratti`.`id`, `title` AS descrizione FROM `co_staticontratti` LEFT JOIN `co_staticontratti_lang` ON (`co_staticontratti`.`id` = `co_staticontratti_lang`.`id_record` AND `co_staticontratti_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `is_pianificabile`=1' : 'SELECT `co_staticontratti`.`id`, `title` AS descrizione FROM `co_staticontratti` LEFT JOIN `co_staticontratti_lang` ON (`co_staticontratti`.`id` = `co_staticontratti_lang`.`id_record` AND `co_staticontratti_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')';

$stato = Stato::where('name', 'Bozza')->first()->id;

echo '
<form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">

    <!-- Fix creazione da Anagrafica -->
    <input type="hidden" name="id_record" value="0">

	<div class="row">
		<div class="col-md-6">
			 {[ "type": "text", "label": "'.tr('Nome').'", "name": "nome", "required": 1 ]}
		</div>

		<div class="col-md-6">
			{[ "type": "select", "label": "'.tr('Cliente').'", "name": "idanagrafica", "required": 1, "value": "'.$id_anagrafica.'", "ajax-source": "clienti", "icon-after": "add|'.Module::where('name', 'Anagrafiche')->first()->id.'|tipoanagrafica=Cliente&readonly_tipo=1", "readonly": "'.((empty(get('idanagrafica'))) ? 0 : 1).'" ]}
		</div>
	</div>

    <div class="row">
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Stato').'", "name": "idstato", "required": 1, "value": "'.$stato.'", "values": "query='.$stati.'" ]}
        </div>

        <div class="col-md-6">
			{[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "ajax-source": "segmenti", "select-options": '.json_encode(['id_module' => $id_module, 'is_sezionale' => 1]).', "value": "'.$_SESSION['module_'.$id_module]['id_segment'].'" ]}
		</div>
    </div>

    <div class="row">
		<div class="col-md-6">
			{[ "type": "select", "label": "'.tr('Categoria').'", "name": "id_categoria_add", "required": 0, "ajax-source": "categorie_contratti", "icon-after": "add|'.Module::where('name', 'Categorie contratti')->first()->id.'" ]}
		</div>

		<div class="col-md-6">
			{[ "type": "select", "label": "'.tr('Sottocategoria').'", "name": "id_sottocategoria_add", "ajax-source": "sottocategorie_contratti", "icon-after": "add|'.Module::where('name', 'Categorie contratti')->first()->id.'||hide" ]}
		</div>
    </div>

    <div class="row">
        <div class="col-md-4">
            {[ "type": "date", "label": "'.tr('Data accettazione').'", "name": "data_accettazione" ]}
        </div>

        <div class="col-md-4">
            {[ "type": "date", "label": "'.tr('Data conclusione').'", "name": "data_conclusione" ]}
        </div>

        <div class="col-md-4">
            {[ "type": "number", "label": "'.tr('Validità contratto').'", "name": "validita", "decimals": "0", "icon-after": "choice|period|'.$record['tipo_validita'].'", "help": "'.tr('Il campo Validità contratto viene utilizzato per il calcolo della Data di conclusione del contratto').'" ]}
        </div>
    </div>

    <!-- Informazioni rinnovo -->
    <div class="card card-primary collapsable collapsed-card">
        <div class="card-header">
            <h3 class="card-title">'.tr('Informazioni per rinnovo').'</h3>
            <div class="card-tools pull-right">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fa fa-plus"></i>
                </button>
            </div>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    {[ "type": "checkbox", "label": "'.tr('Rinnovabile').'", "name": "rinnovabile_add", "value": "'.setting('Crea contratto rinnovabile di default').'", "id": "rinnovabile_add", "help": "'.tr('Il contratto è rinnovabile?').'" ]}
                </div>

                <div class="col-md-6">
                    {[ "type": "checkbox", "label": "'.tr('Tacito rinnovo').'", "name": "rinnovo_automatico_add", "id": "rinnovo_automatico_add", "help": "'.tr('Il contratto è da rinnovare automaticamente alla scadenza').'", "disabled": 1 ]}
                </div>

                <div class="col-md-6">
                    {[ "type": "number", "label": "'.tr('Preavviso per rinnovo').'", "name": "giorni_preavviso_rinnovo", "id": "giorni_preavviso_rinnovo_add", "decimals": "2", "value": "'.setting('Giorni di preavviso di default').'", "icon-after": "giorni", "disabled": 1 ]}
                </div>

                <div class="col-md-6">
                    {[ "type": "number", "label": "'.tr('Ore rimanenti rinnovo').'", "name": "ore_preavviso_rinnovo", "id": "ore_preavviso_rinnovo_add", "decimals": "2", "icon-after": "ore", "disabled": 1, "help": "'.tr('Ore residue nel contratto prima di visualizzare una avviso per un eventuale rinnovo anticipato.').'" ]}
                </div>
            </div>
        </div>
    </div>

	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary">
			    <i class="fa fa-plus"></i> '.tr('Aggiungi').'
			</button>
		</div>
	</div>
</form>

<script type="text/javascript">
    input("rinnovabile_add").on("change", function() {
        const disabled = parseInt($(this).val()) === 0;

        input("giorni_preavviso_rinnovo").setDisabled(disabled);
        input("ore_preavviso_rinnovo").setDisabled(disabled);
        input("rinnovo_automatico_add").setDisabled(disabled);
    });

    $("#data_conclusione").on("dp.change", function (e) {
        let data_accettazione = $("#data_accettazione");
        data_accettazione.data("DateTimePicker").maxDate(e.date);

        if(data_accettazione.data("DateTimePicker").date() > e.date){
            data_accettazione.data("DateTimePicker").date(e.date);
        }
    });

    $("#id_categoria").change(function() {
		updateSelectOption("id_categoria", $(this).val());

		$("#id_sottocategoria").val(null).trigger("change");
	});
</script>';
