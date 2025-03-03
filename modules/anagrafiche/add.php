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

use Modules\Anagrafiche\Nazione;
use Modules\Anagrafiche\Tipo;

include_once __DIR__.'/../../core.php';

$id_nazione_italia = (new Nazione())->getByField('title', 'Italia', Models\Locale::getPredefined()->id);

if ($idtipoanagrafica) {
    $tipo = Tipo::where('name', $idtipoanagrafica)->first();
} else {
    $tipo = Tipo::where('name', get('tipoanagrafica'))->first() ?: get('tipoanagrafica');
    $idtipoanagrafica = $tipo->id;
}

$id_tipo_azienda = Tipo::where('name', 'Azienda')->first()->id;

echo '
<form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">

	<div class="row">
		<div class="col-md-6">
			{[ "type": "text", "label": "'.tr('Denominazione').'", "name": "ragione_sociale", "id": "ragione_sociale_add", "required": 1 ]}
		</div>

		<div class="col-md-6">
			{[ "type": "select", "label": "'.tr('Tipo di anagrafica').'", "name": "idtipoanagrafica[]", "id": "idtipoanagrafica_add", "multiple": "1", "required": 1, "values": "query=SELECT `an_tipianagrafiche`.`id`, `title` as descrizione FROM `an_tipianagrafiche` LEFT JOIN `an_tipianagrafiche_lang` ON (`an_tipianagrafiche`.`id` = `an_tipianagrafiche_lang`.`id_record` AND `an_tipianagrafiche_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `an_tipianagrafiche`.`id` NOT IN (SELECT DISTINCT(`x`.`idtipoanagrafica`) FROM `an_tipianagrafiche_anagrafiche` x INNER JOIN `an_tipianagrafiche` t ON `x`.`idtipoanagrafica` = `t`.`id` LEFT JOIN `an_tipianagrafiche_lang` ON (`t`.`id` = `an_tipianagrafiche_lang`.`id_record` AND `an_tipianagrafiche_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica` = `x`.`idanagrafica` WHERE `an_tipianagrafiche`.`id` = '.prepare($id_tipo_azienda).' AND `deleted_at` IS NULL) ORDER BY `title`", "value": "'.($idtipoanagrafica ?? null).'", "readonly": '.(!empty(get('readonly_tipo')) ? 1 : 0).' ]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-6">
			{[ "type": "text", "label": "'.tr('Cognome').'", "name": "cognome", "id": "cognome_add" ]}
		</div>

		<div class="col-md-6">
			{[ "type": "text", "label": "'.tr('Nome').'", "name": "nome", "id": "nome_add" ]}
		</div>
	</div>';

echo '
	<br>
    <div class="card card-info collapsed-card">
		<div class="card-header">
			<h3 class="card-title">'.tr('Dati anagrafici').'</h3>
			<div class="card-tools pull-right">
				<button type="button" class="btn btn-tool" data-card-widget="collapse">
					<i class="fa fa-plus"></i>
				</button>
			</div>
		</div>
		<div class="card-body collapse">
			<div class="row">
				<div class="col-md-4">
					{[ "type": "text", "label": "'.tr('Partita IVA').'", "maxlength": 16, "name": "piva", "id": "piva_add", "class": "text-center alphanumeric-mask", "validation": "partita_iva"]}
				</div>

				<div class="col-md-4">
					{[ "type": "text", "label": "'.tr('Codice fiscale').'", "maxlength": 16, "name": "codice_fiscale", "class": "text-center alphanumeric-mask", "validation": "codice_fiscale" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "select", "label": "'.tr('Tipologia').'", "name": "tipo", "id": "tipo_add", "values": "list=\"Azienda\": \"'.tr('Azienda').'\", \"Ente pubblico\": \"'.tr('Ente pubblico').'\", \"Privato\": \"'.tr('Privato').'\"", "placeholder":"'.tr('Non specificato').'" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-4">
					{[ "type": "text", "label": "'.tr('Indirizzo').'", "name": "indirizzo" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "text", "label": "'.tr('C.A.P.').'", "name": "cap", "maxlength": 6, "class": "text-center" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "text", "label": "'.tr('Città').'", "name": "citta", "class": "text-center" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "text", "label": "'.tr('Provincia').'", "name": "provincia", "maxlength": 2, "class": "text-center", "extra": "onkeyup=\"this.value = this.value.toUpperCase();\"" ]}
				</div>
			</div>

			<div class="row">

				<div class="col-md-4">
                    {[ "type": "select", "label": "'.tr('Nazione').'", "name": "id_nazione", "id": "id_nazione_add", "values": "query=SELECT `an_nazioni`.`id` AS id, CONCAT_WS(\' - \', `iso2`, `an_nazioni_lang`.`title`) AS descrizione, `iso2` FROM `an_nazioni` LEFT JOIN `an_nazioni_lang` ON (`an_nazioni`.`id` = `an_nazioni_lang`.`id_record` AND `an_nazioni_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') ORDER BY CASE WHEN `iso2`=\'IT\' THEN -1 ELSE `iso2` END", "value": "'.$id_nazione_italia.'" ]}
                </div>

				<div class="col-md-4">
					{[ "type": "telefono", "label": "'.tr('Telefono').'", "name": "telefono", "class": "text-center" ]}
				</div>
				<div class="col-md-4">
					{[ "type": "telefono", "label": "'.tr('Cellulare').'", "name": "cellulare", "class": "text-center" ]}
				</div>

			</div>

			<div class="row">

				<div class="col-md-4">
					{[ "type": "email", "label": "'.tr('Email').'", "name": "email", "placeholder":"casella@dominio.ext", "validation": "email" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "email", "label": "'.tr('PEC').'", "name": "pec", "placeholder":"pec@dominio.ext" ]}
				</div>';

$help_codice_destinatario = tr("Per impostare il codice specificare prima '<b>Tipologia</b>' e '<b>Nazione</b>' dell'anagrafica").':<br><br><ul><li>'.tr('Azienda (B2B) - Codice Destinatario, 7 caratteri').'</li><li>'.tr('Ente pubblico (B2G/PA) - Codice Univoco Ufficio (www.indicepa.gov.it), 6 caratteri').'</li><li>'.tr('Privato (B2C) - viene utilizzato il Codice Fiscale').'</li></ul>Se non si conosce il codice destinatario lasciare vuoto il campo. Verrà applicato in automatico quello previsto di default dal sistema (\'0000000\', \'999999\', \'XXXXXXX\').';

echo '
				<div class="col-md-4">
					{[ "type": "text", "label": "'.tr('Codice destinatario').'", "name": "codice_destinatario", "class": "text-center text-uppercase alphanumeric-mask", "maxlength": "7", "extra": "", "help": "'.tr($help_codice_destinatario).'", "readonly": "1" ]}
				</div>
			</div>
		</div>
	</div>';

echo '
	<!-- PULSANTI -->
    <div class="modal-footer">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> '.tr('Aggiungi').'</button>
		</div>
	</div>
</form>';
?>

<script>
    var nome = input("nome");
    var cognome = input("cognome");
    var ragione_sociale = input("ragione_sociale");
    var id_nazione = input("id_nazione");

    // Abilito solo ragione sociale oppure solo nome-cognome in base a cosa compilo
    nome.on("keyup", function () {
        if (nome.get()) {
            ragione_sociale.disable();
        } else if (!cognome.get()) {
            ragione_sociale.enable();
        }
    });

    cognome.on("keyup", function () {
        if (cognome.get()) {
            ragione_sociale.disable();
        } else if (!nome.get()) {
            ragione_sociale.enable();
        }
    });

    ragione_sociale.on("keyup", function () {
        let disable = ragione_sociale.get() !== "";

        nome.setDisabled(disable);
        cognome.setDisabled(disable);
    });

    id_nazione.change(function() {
		if (id_nazione.get() !== null) {
			if ((id_nazione.getData().iso2 === 'IT') || (id_nazione.getData().iso2 === 'SM')) {
				input("codice_destinatario").enable();
			}else{
				input("codice_destinatario").disable();
			}

			// Aggiunta nazione come parametro aggiuntivo per la validazione partita iva
			$("#piva_add").data("additional", $(this).selectData().iso2);
		}
	});

	$(document).ready( function(){
		id_nazione.trigger('change');
	});
</script>
