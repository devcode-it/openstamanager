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

echo '
<form action="" method="post" role="form">
    <input type="hidden" name="id_parent" value="'.$id_parent.'">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="addsede">

	<!-- Fix creazione da Anagrafica -->
    <input type="hidden" name="id_record" value="">

	<div class="row">
		<div class="col-md-6">
			{[ "type": "select", "label": "'.tr('Anagrafica').'", "name": "id_anagrafica", "required": "1", "value": "'.$id_parent.'", "ajax-source": "anagrafiche", "disabled": 1 ]}
		</div>

		<div class="col-md-6">
				{[ "type": "text", "label": "'.tr('Nome sede').'", "name": "nomesede", "required": 1 ]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-6">
			{[ "type": "text", "label": "'.tr('Indirizzo').'", "name": "indirizzo", "required": 0 ]}
		</div>
		<div class="col-md-6">
			{[ "type": "text", "label": "'.tr('Città').'", "name": "citta", "required": 1  ]}
		</div>
	</div>
	<div class="row">
		<div class="col-md-2">
			{[ "type": "text", "label": "'.tr('C.A.P.').'", "name": "cap", "required": 1 ]}
		</div>

		<div class="col-md-2">
			{[ "type": "text", "label": "'.tr('Provincia').'", "name": "provincia", "maxlength": 2, "class": "text-center provincia-mask text-uppercase", "extra": "onkeyup=\"this.value = this.value.toUpperCase();\"" ]}
		</div>

		<div class="col-md-2">
			{[ "type": "text", "label": "'.tr('Km').'", "name": "km" ]}
		</div>
		<div class="col-md-3">
			{[ "type": "select", "label": "'.tr('Nazione').'", "name": "id_nazione", "ajax-source": "nazioni", "required": 1 ]}
		</div>
        <div class="col-md-3">
			{[ "type": "select", "label": "'.tr('Zona').'", "name": "idzona", "ajax-source": "zone", "placeholder": "'.tr('Nessuna zona').'", "icon-after": "add|'.Modules::get('Zone')['id'].'" ]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-3">
			{[ "type": "telefono", "label": "'.tr('Cellulare').'", "name": "cellulare" ]}
		</div>

        <div class="col-md-3">
			{[ "type": "telefono", "label": "'.tr('Telefono').'", "name": "telefono" ]}
		</div>

		<div class="col-md-3">
			{[ "type": "text", "label": "'.tr('Indirizzo email').'", "name": "email", "class": "email-mask", "validation": "email" ]}
		</div>
		<div class="col-md-3">
			{[ "type": "checkbox", "label": "'.tr('Opt-out per newsletter').'", "name": "disable_newsletter", "id": "disable_newsletter_m",  "value": "0", "help": "'.tr("Blocco per l'invio delle email.").'" ]}
		</div>
	</div>
	<div class="row">
		<div class="col-md-4">
			{[ "type": "text", "label": "'.($record['tipo_anagrafica'] == 'Ente pubblico' ? tr('Codice unico ufficio') : tr('Codice destinatario')).'", "name": "codice_destinatario", "required": 0, "class": "text-center text-uppercase alphanumeric-mask", "value": "$codice_destinatario$", "maxlength": '.($record['tipo_anagrafica'] == 'Ente pubblico' ? '6' : '7').', "help": "'.tr('<b>Attenzione</b>: per impostare il codice specificare prima \'Tipologia\' e \'Nazione\' dell\'anagrafica:<br><ul><li>Azienda (B2B) - Codice Destinatario, 7 caratteri</li><li>Ente pubblico (B2G/PA) - Codice Univoco Ufficio (www.indicepa.gov.it), 6 caratteri</li><li>Privato (B2C) - viene utilizzato il Codice Fiscale</li></ul>').'", "readonly": "'.intval($record['iso2'] ? $record['iso2'] != 'IT' : 0).'" ]}
		</div>
		<div class="col-md-4">
			{[ "type": "checkbox", "label": "'.tr('Automezzo').'", "name": "is_automezzo", "id": "is_automezzo", "value": "0", "help": "'.tr('Seleziona se questa sede rappresenta un automezzo.').'" ]}
		</div>
		<div class="col-md-4">
			{[ "type": "checkbox", "label": "'.tr('Rappresentante fiscale').'", "name": "is_rappresentante_fiscale", "value": "'.$record['is_rappresentante_fiscale'].'", "help": "'.tr("Utilizza questa sede come rappresentante fiscale per l'anagrafica.").'" ]}
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			{[ "type": "select", "multiple": "1", "label": "'.tr('Referenti').'", "name": "id_referenti[]", "ajax-source": "referenti", "select-options": {"idanagrafica": '.$id_parent.'}, "icon-after": "add|'.Modules::get('Anagrafiche')['id'].'|id_plugin='.Plugins::get('Referenti')['id'].'&id_parent='.$id_parent.'" ]}
		</div>
	</div>';

$espandi_dettagli = setting('Espandi automaticamente la sezione "Dettagli aggiuntivi"');
echo '
    <!-- DATI AGGIUNTIVI -->
    <div class="box box-info collapsable '.(empty($espandi_dettagli) ? 'collapsed-box' : '').'">
        <div class="box-header with-border">
            <h3 class="box-title">'.tr('Dettagli aggiuntivi').'</h3>
            <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse">
                    <i class="fa fa-'.(empty($espandi_dettagli) ? 'plus' : 'minus').'"></i>
                </button>
            </div>
        </div>

		<div class="box-body">
			<div class="row">
				<div class="col-md-6">
					{[ "type": "text", "label": "'.tr('Nome').'", "name": "nome", "value": "" ]}
				</div>
				<div class="col-md-6">
					{[ "type": "text", "label": "'.tr('Targa').'", "name": "targa", "maxlength": 10, "class": "alphanumeric-mask", "value": "" ]}
				</div>

			</div>
			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "'.tr('Descrizione').'", "name": "descrizione", "value": "" ]}
				</div>
			</div>
		</div>
	</div>';

echo '
	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> '.tr('Aggiungi').'</button>
		</div>
	</div>
</form>';
