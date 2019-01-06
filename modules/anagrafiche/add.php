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
			{[ "type": "select", "label": "'.tr('Tipo di anagrafica').'", "name": "idtipoanagrafica[]", "multiple": "1", "required": 1, "values": "query=SELECT idtipoanagrafica AS id, descrizione FROM an_tipianagrafiche WHERE idtipoanagrafica NOT IN (SELECT DISTINCT(x.idtipoanagrafica) FROM an_tipianagrafiche_anagrafiche x INNER JOIN an_tipianagrafiche t ON x.idtipoanagrafica = t.idtipoanagrafica INNER JOIN an_anagrafiche ON an_anagrafiche.idanagrafica = x.idanagrafica WHERE t.descrizione = \'Azienda\' AND deleted_at IS NULL) ORDER BY descrizione", "value": "'.(isset($idtipoanagrafica) ? $idtipoanagrafica : null).'", "readonly": '.(!empty($readonly_tipo) ? 1 : 0).' ]}
		</div>
	</div>

	<div class="row">
		<div class="col-md-6">
			{[ "type": "text", "label": "'.tr('Nome').'", "name": "nome", "required": 0 ]}
		</div>

		<div class="col-md-6">
			{[ "type": "text", "label": "'.tr('Cognome').'", "name": "cognome", "required": 0 ]}
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
					{[ "type": "text", "label": "'.tr('Partita IVA').'", "maxlength": 13, "name": "piva", "class": "text-center alphanumeric-mask" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "text", "label": "'.tr('Codice fiscale').'", "maxlength": 16, "name": "codice_fiscale", "class": "text-center alphanumeric-mask" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "select", "label": "'.tr('Relazione').'", "name": "idrelazione", "values": "query=SELECT id, descrizione, colore AS _bgcolor_ FROM an_relazioni ORDER BY descrizione" ]}
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
					{[ "type": "text", "label": "'.tr('Telefono').'", "name": "telefono", "class": "text-center", "icon-before": "<i class=\"fa fa-phone\"></i>" ]}
				</div>
				<div class="col-md-4">
					{[ "type": "text", "label": "'.tr('Cellulare').'", "name": "cellulare", "class": "text-center", "icon-before": "<i class=\"fa fa-mobile\"></i>" ]}
				</div>

				<div class="col-md-4">
					{[ "type": "text", "label": "'.tr('Email').'", "name": "email", "class": "email-mask", "placeholder":"casella@dominio.ext", "icon-before": "<i class=\"fa fa-envelope\"></i>" ]}
				</div>
			</div>


		</div>
	</div>';

echo
    '<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> '.tr('Aggiungi').'</button>
		</div>
	</div>
</form>';
?>

<script>
    // Abilito solo ragione sociale oppure solo nome-cognome in base a cosa compilo
    $('#nome, #cognome', '#bs-popup, #bs-popup2').keyup(function(){
        if ($('#nome', '#bs-popup, #bs-popup2').val() == '' && $('#cognome', '#bs-popup, #bs-popup2').val() == '' ){
            $('#nome, #cognome', '#bs-popup, #bs-popup2').prop('disabled', true).prop('required', false);
            $('#ragione_sociale', '#bs-popup, #bs-popup2').prop('disabled', false).prop('required', true);
        }else{
            $('#nome, #cognome', '#bs-popup, #bs-popup2').prop('disabled', false).prop('required', true);
            $('#ragione_sociale', '#bs-popup, #bs-popup2').prop('disabled', true).prop('required', false);
        }
    });

    $('#ragione_sociale', '#bs-popup, #bs-popup2').keyup(function(){
        if ($('#ragione_sociale', '#bs-popup, #bs-popup2').val() == '' ){
            $('#nome, #cognome', '#bs-popup, #bs-popup2').prop('disabled', false).prop('required', true);
            $('#ragione_sociale', '#bs-popup, #bs-popup2').prop('disabled', true).prop('required', false);
        }else{
            $('#nome, #cognome', '#bs-popup, #bs-popup2').prop('disabled', true).prop('required', false);
            $('#ragione_sociale', '#bs-popup, #bs-popup2').prop('disabled', false).prop('required', true);
        }
    });
</script>