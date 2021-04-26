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

?><form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

	<!-- DATI -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Dati'); ?></h3>
		</div>

		<div class="panel-body">
			<div class="row">
                <div class="col-md-3">
                    {[ "type": "select", "label": "<?php echo tr('Anagrafica'); ?>", "name": "id_anagrafica", "required": "1", "value": "$id_anagrafica$", "ajax-source": "anagrafiche", "disabled": 1 ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "checkbox", "label": "<?php echo tr('Predefinito'); ?>", "name": "predefined", "value": "$predefined$", "disabled": "<?php echo intval($record['predefined']); ?>" ]}
                </div>

                <div class="col-md-6">
                    {[ "type": "text", "label": "<?php echo tr('Nome'); ?>", "name": "nome", "required": "1", "value": "$nome$" ]}
                </div>
            </div>

            <div class="row">
				<div class="col-md-6">
					{[ "type": "text", "label": "<?php echo tr('Filiale'); ?>", "name": "filiale", "value": "$filiale$" ]}
                </div>

				<div class="col-md-6">
					{[ "type": "select", "label": "<?php echo tr('Conto predefinito'); ?>", "name": "id_pianodeiconti3", "value": "$id_pianodeiconti3$", "values": "query=SELECT id, descrizione  FROM co_pianodeiconti3 WHERE idpianodeiconti2 = 1" ]}
                </div>
			</div>

			<div class="row">
				<div class="col-md-8">
					{[ "type": "text", "label": "<?php echo tr('IBAN'); ?>", "name": "iban", "required": "1", "class": "alphanumeric-mask", "maxlength": 32, "value": "$iban$" ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "text", "label": "<?php echo tr('BIC'); ?>", "name": "bic", "required": "1", "class": "alphanumeric-mask", "minlength": 8, "maxlength": 11, "value": "$bic$", "help": "<?php echo $help_codice_bic; ?>" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    {[ "type": "text", "label": "<?php echo tr('ID Creditore SEPA'); ?>", "name": "creditor_id", "class": "alphanumeric-mask", "value": "$creditor_id$", "help": "<?php echo tr("Codice identificativo per l'azienda nell'area SEPA. Nel caso di aziende aderenti alla procedura Allineamento Elettronico Archivio per le quali non risulta reperibile in CF/PIVA viene generato un codice identificativo non significativo (NOTPROVIDEDXXXXXXXXXXXX)."); ?>" ]}
                </div>

                <div class="col-md-6">
                    {[ "type": "text", "label": "<?php echo tr('Codice SIA azienda'); ?>", "name": "codice_sia", "class": "alphanumeric-mask", "maxlength": 5, "value": "$codice_sia$", "help":"<?php echo tr('Società Interbancaria per l\'Automazione. Questo campo è necessario per la generazione delle Ri.Ba.<br>E\' composto da 5 caratteri alfanumerici.'); ?>" ]}
                </div>
            </div>

			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Note'); ?>", "name": "note", "value": "$note$" ]}
				</div>
			</div>
		</div>
	</div>
</form>

<!-- Composizione IBAN -->
<div class="box box-info">
    <div class="box-header">
        <h3 class="box-title"><?php echo tr('Composizione IBAN'); ?></h3>
    </div>

    <div class="box-body">
        <div class="row">
            <div class="col-md-4">
                {[ "type": "select", "label": "<?php echo tr('Nazione'); ?>", "name": "id_nazione", "value": "$id_nazione$", "ajax-source": "nazioni" ]}
            </div>

            <div class="col-md-4">
                {[ "type": "text", "label": "<?php echo tr('Codice banca nazionale (ABI)'); ?>", "name": "branch_code", "class": "alphanumeric-mask", "value": "$branch_code$" ]}
            </div>

            <div class="col-md-4">
                {[ "type": "text", "label": "<?php echo tr('Codice filiale (CAB)'); ?>", "name": "bank_code", "class": "alphanumeric-mask", "value": "$bank_code$" ]}
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                {[ "type": "text", "label": "<?php echo tr('Numero account'); ?>", "name": "account_number", "class": "alphanumeric-mask", "value": "$account_number$"]}
            </div>

            <div class="col-md-4">
                {[ "type": "text", "label": "<?php echo tr('Cifre di controllo (CIN europeo)'); ?>", "name": "check_digits", "class": "alphanumeric-mask", "value": "$check_digits$" ]}
            </div>

            <div class="col-md-4">
                {[ "type": "text", "label": "<?php echo tr('Cifre di verifica nazionale (CIN nazionale)'); ?>", "name": "national_check_digits", "class": "alphanumeric-mask", "value": "$national_check_digits$" ]}
            </div>
        </div>
    </div>
</div>

<?php
// Collegamenti diretti (numerici)
$numero_documenti = $dbo->fetchNum('SELECT idanagrafica FROM an_anagrafiche WHERE idbanca_vendite='.prepare($id_record).'
UNION SELECT idanagrafica FROM an_anagrafiche WHERE idbanca_acquisti='.prepare($id_record).'
UNION SELECT idanagrafica FROM co_documenti WHERE id_banca_azienda = '.prepare($id_record).' OR id_banca_controparte = '.prepare($id_record));

if (!empty($numero_documenti)) {
    echo '
<div class="alert alert-danger">
    '.tr('Ci sono _NUM_ documenti collegati', [
        '_NUM_' => $numero_documenti,
    ]).'.
</div>';
}
?>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>

<script>
    var iban = input("iban");

    var branch_code = input("branch_code");
    var bank_code = input("bank_code");
    var account_number = input("account_number");
    var check_digits = input("check_digits");
    var national_check_digits = input("national_check_digits");
    var id_nazione = input("id_nazione");

    var components = [branch_code, bank_code, account_number, check_digits, national_check_digits, id_nazione];

    $(document).ready(function (){
        iban.trigger("change");
    });

    iban.change(function () {
        if (!iban.isDisabled()){
            let value = iban.get();
            for (const component of components){
                component.setDisabled(value !== "")
            }

            scomponiIban();
        }
    });

    for (const component of components){
        component.change(function () {
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
                account_number: account_number.get(),
                check_digits: check_digits.get(),
                national_check_digits: national_check_digits.get(),
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
                input(key).getElement().selectSetNew(value.id, value.text, value);
            } else {
                input(key).set(value);
            }
        }
    }
</script>
