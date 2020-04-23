<?php

include_once __DIR__.'/../../core.php';

if (get('tipoanagrafica') != '') {
    $rs = $dbo->fetchArray('SELECT idtipoanagrafica FROM an_tipianagrafiche WHERE descrizione='.prepare(get('tipoanagrafica')));
    $idtipoanagrafica = $rs[0]['idtipoanagrafica'];
}

echo '
<form action="" method="post" id="add-form">
	<input type="hidden" name="op" value="add">
	<input type="hidden" name="backto" value="record-edit">

	<div class="row">
		<div class="col-md-6">
			{[ "type": "text", "label": "'.tr('Denominazione').'", "name": "ragione_sociale", "required": 1 ]}
		</div>

		<div class="col-md-6">
			{[ "type": "select", "label": "'.tr('Tipo di anagrafica').'", "name": "idtipoanagrafica[]", "multiple": "1", "required": 1, "values": "query=SELECT idtipoanagrafica AS id, descrizione FROM an_tipianagrafiche WHERE idtipoanagrafica NOT IN (SELECT DISTINCT(x.idtipoanagrafica) FROM an_tipianagrafiche_anagrafiche x INNER JOIN an_tipianagrafiche t ON x.idtipoanagrafica = t.idtipoanagrafica INNER JOIN an_anagrafiche ON an_anagrafiche.idanagrafica = x.idanagrafica WHERE t.descrizione = \'Azienda\' AND deleted_at IS NULL) ORDER BY descrizione", "value": "'.(isset($idtipoanagrafica) ? $idtipoanagrafica : null).'", "readonly": '.(!empty(get('readonly_tipo')) ? 1 : 0).' ]}
		</div>
	</div>

	<div class="row">

		<div class="col-md-6">
			{[ "type": "text", "label": "'.tr('Cognome').'", "name": "cognome", "required": 0 ]}
		</div>

		<div class="col-md-6">
			{[ "type": "text", "label": "'.tr('Nome').'", "name": "nome", "id": "nome_", "required": 0 ]}
		</div>

	</div>';

echo '
    <div class="box box-info collapsed-box">
	    <div class="box-header with-border">
	        <h3 class="box-title">'.tr('Dati anagrafici').'</h3>
	        <div class="box-tools pull-right">
	            <button type="button" class="btn btn-box-tool" data-widget="collapse">
	                <i class="fa fa-plus"></i>
	            </button>
	        </div>
	    </div>
	    <div class="box-body">
			<div class="row">
				<div class="col-md-4">
					{[ "type": "text", "label": "'.tr('Partita IVA').'", "maxlength": 13, "name": "piva", "class": "text-center alphanumeric-mask", "validation": "partita_iva"]}
				</div>

				<div class="col-md-4">
					{[ "type": "text", "label": "'.tr('Codice fiscale').'", "maxlength": 16, "name": "codice_fiscale", "class": "text-center alphanumeric-mask", "validation": "codice_fiscale" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "select", "label": "'.tr('Tipologia').'", "name": "tipo", "values": "list=\"\": \"'.tr('Non specificato').'\", \"Azienda\": \"'.tr('Azienda').'\", \"Privato\": \"'.tr('Privato').'\", \"Ente pubblico\": \"'.tr('Ente pubblico').'\"" ]}
				</div>


			</div>

			<div class="row">
				<div class="col-md-4">
					{[ "type": "text", "label": "'.tr('Indirizzo').'", "name": "indirizzo" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "text", "label": "'.tr('C.A.P.').'", "name": "cap", "maxlength": 5, "class": "text-center" ]}
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
                    {[ "type": "select", "label": "'.tr('Nazione').'", "name": "id_nazione", "values": "query=SELECT id AS id, CONCAT_WS(\' - \', iso2, nome) AS descrizione FROM an_nazioni ORDER BY CASE WHEN iso2=\'IT\' THEN -1 ELSE iso2 END" ]}
                </div>

				<div class="col-md-4">
					{[ "type": "text", "label": "'.tr('Telefono').'", "name": "telefono", "class": "text-center", "icon-before": "<i class=\"fa fa-phone\"></i>" ]}
				</div>
				<div class="col-md-4">
					{[ "type": "text", "label": "'.tr('Cellulare').'", "name": "cellulare", "class": "text-center", "icon-before": "<i class=\"fa fa-mobile\"></i>" ]}
				</div>

			</div>

			<div class="row">

				<div class="col-md-4">
					{[ "type": "text", "label": "'.tr('Email').'", "name": "email", "class": "email-mask", "placeholder":"casella@dominio.ext", "icon-before": "<i class=\"fa fa-envelope\"></i>" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "text", "label": "'.tr('PEC').'", "name": "pec", "class": "email-mask", "placeholder":"pec@dominio.ext", "icon-before": "<i class=\'fa fa-envelope-o\'></i>" ]}
				</div>';

                $help_codice_destinatario = tr("Per impostare il codice specificare prima '<b>Tipologia</b>' e '<b>Nazione</b>' dell'anagrafica").':<br><br><ul><li>'.tr('Ente pubblico (B2G/PA) - Codice Univoco Ufficio (www.indicepa.gov.it), 6 caratteri').'</li><li>'.tr('Azienda (B2B) - Codice Destinatario, 7 caratteri').'</li><li>'.tr('Privato (B2C) - viene utilizzato il Codice Fiscale').'</li>'.'</ul>Se non si conosce il codice destinatario lasciare vuoto il campo. Verrà applicato in automatico quello previsto di default dal sistema (\'0000000\', \'999999\', \'XXXXXXX\').';

echo '
				<div class="col-md-4">
					{[ "type": "text", "label": "'.tr('Codice destinatario').'", "name": "codice_destinatario", "required": 0, "class": "text-center text-uppercase alphanumeric-mask", "maxlength": "7", "extra": "", "help": "'.tr($help_codice_destinatario).'", "readonly": "1" ]}
				</div>
			</div>
		</div>
	</div>';

echo '
    <div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> '.tr('Aggiungi').'</button>
		</div>
	</div>
</form>';
?>

<script>
    // Abilito solo ragione sociale oppure solo nome-cognome in base a cosa compilo
    $('#nome_, #cognome', '#modals > div').blur(function(){
        if ($('#nome_', '#modals > div').val() == '' && $('#cognome', '#modals > div').val() == '' ){
            $('#nome_, #cognome', '#modals > div').prop('disabled', true).prop('required', false);
            $('#ragione_sociale', '#modals > div').prop('disabled', false).prop('required', true);
        }else{
            $('#nome_, #cognome', '#modals > div').prop('disabled', false).prop('required', true);
            $('#ragione_sociale', '#modals > div').prop('disabled', true).prop('required', false);
        }
    });

    $('#ragione_sociale', '#modals > div').blur(function(){
        if ($('#ragione_sociale', '#modals > div').val() == '' ){
            $('#nome_, #cognome', '#modals > div').prop('disabled', false).prop('required', true);
            $('#ragione_sociale', '#modals > div').prop('disabled', true).prop('required', false);
        }else{
            $('#nome_, #cognome', '#modals > div').prop('disabled', true).prop('required', false);
            $('#ragione_sociale', '#modals > div').prop('disabled', false).prop('required', true);
        }
    });

	$('#id_nazione', '#modals > div').change(function(){
		if ($(this).find('option:selected').data('text')=='IT - Italia'){
			$('#codice_destinatario',  '#modals > div').removeAttr('readonly');
		}
	});

</script>
