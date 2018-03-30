<?php

include_once __DIR__.'/../../../core.php';

//<form action="plugin_editor.php?id_plugin=$id_plugin$&id_module=$id_module$&id_parent=$id_parent$" method="post" role="form">

$idcontratto_riga = $get['idcontratto_riga'];
$qp = 'SELECT *, (SELECT data_conclusione FROM co_contratti WHERE id = '.$id_record.' ) AS data_conclusione, (SELECT descrizione FROM in_tipiintervento WHERE idtipointervento=co_righe_contratti.idtipointervento) AS tipointervento FROM co_righe_contratti WHERE id = '.$idcontratto_riga;
$rsp = $dbo->fetchArray($qp);

$data_richiesta = readDate($rsp[0]['data_richiesta']);

$orario_inizio = '09:00';
$orario_fine = '17:00';

echo '
<form id="add_form" action="'.$rootdir.'/editor.php?id_module='.Modules::get('Contratti')['id'].'&id_record='.$id_record.'&idcontratto_riga='.$idcontratto_riga.'" method="post">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="pianificazione">

	<div class="row">


		<div class="col-md-7">
			 {[ "type": "select", "label": "'.tr('Tipo intervento').'", "name": "idtipointervento", "id": "idtipointervento_", "values": "query=SELECT idtipointervento AS id, descrizione FROM in_tipiintervento ORDER BY descrizione ASC", "value": "'.$rsp[0]['idtipointervento'].'", "extra": "disabled" ]}
		</div>


		<div class="col-md-5">
			{[ "type": "number", "label": "'.tr('Intervallo').'", "name": "intervallo", "class": "", "decimals": 0, "required": 1, "icon-after": "GG",  "min-value": "1"  ]}
		</div>



	</div>

	<div class="row">

		<div class="col-md-12">
			 {[ "type": "textarea", "label": "'.tr('Descrizione').'",  "placeholder": "'.tr('Descrizione').'", "name": "richiesta", "id": "richiesta_", "extra": "readonly", "value": "'.$rsp[0]['richiesta'].'"  ]}
		</div>

	</div>


	<div class="row">



		<!--div class="col-md-8">
			{[ "type": "checkbox", "label": "'.tr('Pianifica anche date passate').'", "name": "date_passate", "value": "0", "help": "", "placeholder": "'.tr('Pianificare promemoria anche con date precedenti ad oggi: ').date('d/m/Y').'" ]}
		</div-->

';

?>

		<div class="col-md-7">
			{[ "type": "select", "label": "<?php echo tr('Inizio pianificazione'); ?>", "name": "parti_da_oggi", "values": "list= \"0\":\"<?php echo tr('Pianificare a partire da questo promemoria ').$data_richiesta; ?>\", \"1\":\"<?php echo tr('Pianificare a partire da oggi ').date('d/m/Y'); ?>\"", "value": "" ]}
		</div>

<?php

echo '


		<div class="col-md-5">
			 {[ "type": "date", "label": "'.tr('Fine pianificazione <small>(Data conclusione contratto)</small>').'", "name": "data_conclusione", "id": "data_conclusione_", "extra": "readonly", "value": "'.$rsp[0]['data_conclusione'].'"  ]}
		</div>


	</div>

	<div class="row">

		<div class="col-md-4">
			{[ "type": "checkbox", "label": "'.tr('Pianifica anche l\'intervento').'", "name": "pianifica_intervento", "value": "0", "help": "", "placeholder": "'.tr('Pianificare già l\'intervento ').'" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "select", "label": "'.tr('Tecnici').'", "multiple": "1",  "name": "idtecnico[]", "required": 0, "ajax-source": "tecnici", "value": "", "extra": "disabled" ]}
		</div>


		<div class="col-xs-6  col-md-2">
			{[ "type": "time", "label": "'.tr('Orario inizio').'", "name": "orario_inizio", "required": 0, "value": "'.$orario_inizio.'", "extra": "disabled" ]}
		</div>

		<div class="col-xs-6  col-md-2">
			{[ "type": "time", "label": "'.tr('Orario fine').'", "name": "orario_fine", "required": 0, "value": "'.$orario_fine.'", "extra": "disabled" ]}
		</div>




	</div>




	<!-- PULSANTI -->
	<div class="row">
		<div class="col-md-12 text-right">
			<button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> '.tr('Pianifica').'</button>
		</div>
	</div>


</form>';

echo '
	<script src="'.$rootdir.'/lib/init.js"></script>';

echo '
	<script>
		$( document ).ready(function() {

			$( "#pianifica_intervento" ).click(function() {

					if ($(this).is(":checked")){
						$("#idtecnico").removeAttr("disabled");
						$("#idtecnico").prop("required", true);
						$("#orario_inizio").removeAttr("disabled");
						$("#orario_fine").removeAttr("disabled");
						$("#orario_inizio").prop("required", true);
						$("#orario_fine").prop("required", true);
					}else{
						$("#idtecnico").prop("disabled", true);
						$("#idtecnico").removeAttr("required");
						$("#orario_inizio").prop("disabled", true);
						$("#orario_fine").prop("disabled", true);
						$("#orario_inizio").removeAttr("required");
						$("#orario_fine").removeAttr("required");
					}

			});

		});
	</script>';
