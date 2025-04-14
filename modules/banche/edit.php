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

$ibanapi_key = setting('Api key ibanapi.com');
$endpoint = setting('Endpoint ibanapi.com');

?><form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">

	<!-- DATI -->
	<div class="card card-primary">
		<div class="card-header">
			<h3 class="card-title"><?php echo tr('Dati'); ?></h3>
		</div>

		<div class="card-body">
			<div class="row">
                <div class="col-md-3">
                    <?php echo Modules::link('Anagrafiche', $record['id_anagrafica'], null, null, 'class="pull-right"'); ?>
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
					{[ "type": "select", "label": "<?php echo tr('Conto predefinito'); ?>", "name": "id_pianodeiconti3", "value": "$id_pianodeiconti3$", "ajax-source": "conti", "help": "<?php echo tr('Impostando un conto predefinito verrà pre-selezionata questa banca durante la scelta del tipo di pagamento in fattura.').'<br>'.tr('Anche il tipo di pagamento dovrà avere configurato lo stesso conto.'); ?>" ]}
                </div>
			</div>

			<div class="row">
                <div class="col-md-8">
                    {[ "type": "text", "label": "<?php echo tr('IBAN'); ?>", "name": "iban", "required": "1", "class": "alphanumeric-mask", "maxlength": 32, "value": "$iban$", "icon-after": "<?=(!empty($ibanapi_key) && !empty($endpoint)?'<a class=\'fa fa-search clickable\' id=\'check-iban\'></a>':'<span class=\'tip\' title=\'Da impostazioni sezione API è possibile attivare la verifica iban tramite ibanapi.com\'><i class=\'fa fa-search text-danger clickable\'></i></span>')?>" ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "text", "label": "<?php echo tr('BIC'); ?>", "name": "bic", "class": "alphanumeric-mask", "minlength": 8, "maxlength": 11, "value": "$bic$", "help": "<?php echo $help_codice_bic; ?>" ]}
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
<div class="card card-info">
    <div class="card-header">
        <h3 class="card-title"><?php echo tr('Composizione IBAN'); ?></h3>
    </div>

    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                {[ "type": "select", "label": "<?php echo tr('Nazione'); ?>", "name": "id_nazione", "value": "$id_nazione$", "ajax-source": "nazioni" ]}
            </div>

            <div class="col-md-4">
                {[ "type": "text", "label": "<?php echo tr('Codice banca nazionale (ABI)'); ?>", "name": "bank_code", "class": "alphanumeric-mask", "value": "$bank_code$" ]}
            </div>

            <div class="col-md-4">
                {[ "type": "text", "label": "<?php echo tr('Codice filiale (CAB)'); ?>", "name": "branch_code", "class": "alphanumeric-mask", "value": "$branch_code$" ]}
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
    var bic = input("bic");
    var id_module = <?php echo $id_module; ?>;

    var components = [branch_code, bank_code, account_number, check_digits, national_check_digits, id_nazione];

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
    
    // Funzione per verificare l'IBAN tramite ibanapi.com
    function checkIban() {
        let value = iban.get();
        if (value.length < 15) {
            swal("<?php echo tr('Errore'); ?>", "<?php echo tr('Inserire un IBAN valido'); ?>", "error");
            return;
        }
        
        // Verifica il credito residuo e la data di scadenza
        $.ajax({
            url: globals.rootdir + '/actions.php',
            data: {
                id_module: id_module,
                op: "check_balance",
                api_key: "<?php echo $ibanapi_key; ?>"
            },
            type: 'GET',
            dataType: "json",
            success: function(balance) {
                if (new Date(balance.data.expiry_date) < new Date()) {
                    swal("<?php echo tr('Errore'); ?>", "<?php echo tr('La chiave API è scaduta'); ?>", "error");
                    return;
                }
                
                // Determina quale tipo di verifica utilizzare in base al credito residuo
                let verificationType = '';
                if (balance.data.bank_balance > 0) {
                    verificationType = 'bank';
                } else if (balance.data.basic_balance > 0) {
                    verificationType = 'basic';
                } else {
                    swal("<?php echo tr('Errore'); ?>", "<?php echo tr('Credito insufficiente per la verifica IBAN'); ?>", "error");
                    return;
                }
                
                // Verifica l'IBAN
                $.ajax({
                    url: globals.rootdir + '/actions.php',
                    data: {
                        id_module: id_module,
                        op: "verify_iban",
                        iban: value,
                        type: verificationType,
                        api_key: "<?php echo $ibanapi_key; ?>"
                    },
                    type: 'GET',
                    dataType: "json",
                    success: function(response) {
                        // Verifica se l'IBAN è valido (result: 200 indica validità)
                        if (response.result === 200) {
                            // Compila i campi se disponibili
                            if (verificationType === 'bank' && response.data && response.data.bank) {
                                if (response.data.bank.bic) {
                                    $('#bic').val(response.data.bank.bic);
                                }
                                if (response.data.bank.bank_name) {
                                    $('#nome').val(response.data.bank.bank_name);
                                }
                            }
                            
                            // Formatta le informazioni per l'alert
                            let infoHtml = "<p><strong><?php echo tr('IBAN valido'); ?></strong></p>";
                            
                            // Informazioni sul paese
                            if (response.data) {
                                infoHtml += "<p><strong><?php echo tr('Informazioni paese'); ?>:</strong></p>";
                                infoHtml += "<p><?php echo tr('Paese'); ?>: " + response.data.country_code + " - " + response.data.country_name + "</p>";
                                infoHtml += "<p><?php echo tr('Valuta'); ?>: " + response.data.currency_code + "</p>";
                                infoHtml += "<p>SEPA: " + response.data.sepa_member + "</p>";
                            }
                            
                            // Informazioni sulla banca
                            if (response.data && response.data.bank) {
                                infoHtml += "<p><strong><?php echo tr('Informazioni banca'); ?>:</strong></p>";
                                infoHtml += "<p><?php echo tr('Nome banca'); ?>: " + response.data.bank.bank_name + "</p>";
                                
                                if (response.data.bank.bic) {
                                    infoHtml += "<p>BIC: " + response.data.bank.bic + "</p>";
                                }
                                
                                if (response.data.bank.address) {
                                    infoHtml += "<p><?php echo tr('Indirizzo'); ?>: " + response.data.bank.address + "</p>";
                                }
                                
                                if (response.data.bank.city) {
                                    let location = response.data.bank.city;
                                    if (response.data.bank.zip) {
                                        location += " - " + response.data.bank.zip;
                                    }
                                    if (response.data.bank.state) {
                                        location += " (" + response.data.bank.state + ")";
                                    }
                                    infoHtml += "<p><?php echo tr('Località'); ?>: " + location + "</p>";
                                }
                            }

                            if(response.data.sepa){
                                infoHtml += "<p><strong><?php echo tr('Informazioni sepa'); ?>:</strong></p>";
                                infoHtml += "<div class='row'>";
                                
                                // Funzione per creare un badge SEPA
                                function createSepaBadge(value, label) {
                                    var status = value === 'Yes';
                                    var badgeClass = status ? 'success' : 'danger';
                                    return "<div class='col-md-6 mb-2'>"
                                        + "<div class='card h-100'>"
                                        + "<div class='card-body p-1 d-flex align-items-center'>"
                                        + "<span class='small' style='font-size:9pt'>" + label + "</span>"
                                        + "<span class='badge badge-" + badgeClass + " ml-auto small' style='font-size: 0.75rem;'>" + (status ? '<?php echo tr("Attivo"); ?>' : '<?php echo tr("Non attivo"); ?>') + "</span>"
                                        + "</div></div></div>";
                                }
                                
                                // Crea i badge per ogni servizio SEPA
                                infoHtml += createSepaBadge(response.data.sepa.sepa_credit_transfer, "SEPA Credit Transfer");
                                infoHtml += createSepaBadge(response.data.sepa.sepa_credit_transfer_inst, "SEPA Credit Transfer Instant");
                                infoHtml += createSepaBadge(response.data.sepa.sepa_direct_debit, "SEPA Direct Debit");
                                infoHtml += createSepaBadge(response.data.sepa.sepa_sdd_core, "SEPA Direct Debit Core");
                                infoHtml += createSepaBadge(response.data.sepa.sepa_b2b, "SEPA B2B");
                                infoHtml += createSepaBadge(response.data.sepa.sepa_card_clearing, "SEPA Card Clearing");
                                
                                infoHtml += "</div></div>";
                            }
                            
                            swal({
                                title: "<?php echo tr('Verifica completata'); ?>",
                                html: infoHtml,
                                type: "success"
                            });
                            
                            // Aggiorna i campi del form
                            scomponiIban();
                        } else {
                            swal("<?php echo tr('IBAN non valido'); ?>", response.message || "<?php echo tr('Formato IBAN non valido'); ?>", "error");
                        }
                    },
                    error: function() {
                        swal("<?php echo tr('Errore'); ?>", "<?php echo tr('Errore durante la verifica dell\'IBAN'); ?>", "error");
                    }
                });
            },
            error: function() {
                swal("<?php echo tr('Errore'); ?>", "<?php echo tr('Errore durante la verifica del credito'); ?>", "error");
            }
        });
    }
    
    // Aggiungi l'evento click al pulsante di verifica IBAN
    $("#check-iban").on("click", function(e) {
        e.preventDefault();
        checkIban();
    });
</script>
