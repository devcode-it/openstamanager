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

$id_anagrafica = !empty(get('idanagrafica')) ? get('idanagrafica') : $user['idanagrafica'];

echo '
<form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">

    <!-- Fix creazione da Anagrafica -->
    <input type="hidden" name="id_record" value="">

	<div class="row">
		<div class="col-md-6">
			 {[ "type": "text", "label": "'.tr('Nome').'", "name": "nome", "required": 1 ]}
		</div>

		<div class="col-md-6">
			{[ "type": "select", "label": "'.tr('Cliente').'", "name": "idanagrafica", "required": 1, "value": "'.$id_anagrafica.'", "ajax-source": "clienti", "icon-after": "add|'.Modules::get('Anagrafiche')['id'].'|tipoanagrafica=Cliente&readonly_tipo=1" ]}
		</div>
	</div>

    <!-- Informazioni rinnovo -->
    <div class="box box-primary">
        <div class="box-header">
            <h3 class="box-title">'.tr('Informazioni per rinnovo').'</h3>
        </div>

        <div class="box-body">
            <div class="row">
                <div class="col-md-6">
                    {[ "type": "checkbox", "label": "'.tr('Rinnovabile').'", "name": "rinnovabile", "id": "rinnovabile_add", "help": "'.tr('Il contratto è rinnovabile?').'" ]}
                </div>

                <div class="col-md-6">
                    {[ "type": "checkbox", "label": "'.tr('Rinnovo automatico').'", "name": "rinnovo_automatico", "id": "rinnovo_automatico_add", "help": "'.tr('Il contratto è da rinnovare automaticamente alla scadenza').'", "disabled": 1 ]}
                </div>

                <div class="col-md-6">
                    {[ "type": "number", "label": "'.tr('Preavviso per rinnovo').'", "name": "giorni_preavviso_rinnovo", "id": "giorni_preavviso_rinnovo_add", "decimals": "2", "icon-after": "giorni", "disabled": 1 ]}
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
    input("rinnovabile").on("change", function() {
        const disabled = parseInt($(this).val()) === 0;

        input("giorni_preavviso_rinnovo").setDisabled(disabled);
        input("ore_preavviso_rinnovo").setDisabled(disabled);
        input("rinnovo_automatico").setDisabled(disabled);
    });
</script>';
