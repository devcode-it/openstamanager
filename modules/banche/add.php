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

$id_anagrafica = filter('id_anagrafica');

echo '
<form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">

	<div class="row">
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Anagrafica').'", "name": "id_anagrafica", "required": "1", "value": "$id_anagrafica$", "ajax-source": "anagrafiche", "value": "'.$id_anagrafica.'", "disabled": "'.intval(!empty($id_anagrafica)).'" ]}
        </div>

		<div class="col-md-6">
			{[ "type": "text", "label": "'.tr('Nome').'", "name": "nome", "required": "1" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-8">
			{[ "type": "text", "label": "'.tr('IBAN').'", "name": "iban", "required": "1", "class": "alphanumeric-mask", "maxlength": 32, "value": "$iban$" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "text", "label": "'.tr('BIC').'", "name": "bic", "required": "1", "class": "alphanumeric-mask", "minlength": 8, "maxlength": 11, "value": "$bic$", "help": "'.$help_codice_bic.'" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-4">
			{[ "type": "select", "label": "'.tr('Nazione').'", "name": "id_nazione", "ajax-source": "nazioni" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "text", "label": "'.tr('Codice banca nazionale (ABI)').'", "name": "bank_code", "class": "alphanumeric-mask" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "text", "label": "'.tr('Codice filiale (CAB)').'", "name": "branch_code", "class": "alphanumeric-mask" ]}
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
</form>';
?>
<script>
    
    var iban = input("iban");
    var branch_code = input("branch_code");
    var bank_code = input("bank_code");
    var id_nazione = input("id_nazione");
    var bic = input("bic");

    var components = [branch_code, bank_code, id_nazione];

    $(document).ready(function (){
        iban.trigger("keyup");
    });

    iban.on("keyup", function () {
        if (!iban.isDisabled()){
            let value = iban.get();
            for (const component of components){
                component.setDisabled(value !== "")
            }

            scomponiIban();
        }
    });

    for (const component of components){
        component.on("keyup", function () {
            let i = input(this);
            if (!i.isDisabled()) {
                iban.setDisabled(i.get() !== "")

                componiIban();
            }
        });
    }

    function scomponiIban() {
        $.ajax({
            url: globals.rootdir + '/actions.php',
            data: {
                id_module: globals.id_module,
                op: "decompose",
                iban: iban.get(),
            },
            type: 'GET',
            dataType: "json",
            success: function (response) {
                compilaCampi(response);

                if (response.id_nazione.iso2 === "IT"){
                    bic.setRequired(false);
                    var label_text = $('label[for=bic] span .text-red').text();
                    $('label[for=bic] span .text-red').text(label_text.replace('*', ' '));
                } else {
                    bic.setRequired(true);
                    var label_text = $('label[for=bic] span .text-red').text();
                    $('label[for=bic] span .text-red').text(label_text.replace(' ', '*'));
                }
            },
            error: function() {
                toastr["error"]("<?php echo tr('Formato IBAN non valido'); ?>");
            }
        });
    }

    function componiIban() {
        // Controllo su campi con valore impostato
        let continua = false;
        for (const component of components){
            continua |= !([undefined, null, ""].includes(component.get()));
        }

        if (!continua){
            return;
        }

        $.ajax({
            url: globals.rootdir + '/actions.php',
            data: {
                id_module: globals.id_module,
                op: "compose",
                branch_code: branch_code.get(),
                bank_code: bank_code.get(),
                id_nazione: id_nazione.get(),
            },
            type: 'GET',
            dataType: "json",
            success: function (response) {
                compilaCampi(response);
            },
            error: function() {
                toastr["error"]("<?php echo tr('Formato IBAN non valido'); ?>");
            }
        });
    }

    function compilaCampi(values) {
        for([key, value] of Object.entries(values)) {
            if (typeof value === 'object' && value !== null) {
                input('#modals > div #'+key).getElement().selectSetNew(value.id, value.text, value);
            } else {
                input('#modals > div #'+key).set(value);
            }
        }
    }
</script>
