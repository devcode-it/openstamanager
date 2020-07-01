<?php

include_once __DIR__.'/../../core.php';

$_SESSION['stampe_contabili']['id_sezionale'] = 0;
$id_record = filter('id_record');
$dir = filter('dir');

// Trovo id_print della stampa
$link = Prints::getHref('Registro IVA', $id_record);


echo '
<form action="" method="post" onsubmit="if($(this).parsley().validate()) { return stampa_registro_iva(); }" >

	<div class="row">
		<div class="col-md-4">
			{[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_sezionale", "required": "1", "values": "query=SELECT id AS id, name AS descrizione FROM zz_segments WHERE id_module = (SELECT id FROM zz_modules WHERE name = \''.(($dir == 'entrata') ? 'Fatture di vendita' : 'Fatture di acquisto').'\') AND is_fiscale = 1 UNION SELECT  0 AS id, \'Tutti i sezionali\' AS descrizione", "value": "'.$_SESSION['stampe_contabili']['id_sezionale'].'" ]}
		</div>
		
		<div class="col-md-2">
			{[ "type": "select", "label": "'.tr('Formato').'", "name": "format", "required": "1", "values": "list=\"A4\": \"'.tr('A4').'\", \"A3\": \"'.tr('A3').'\"", "value": "'.$_SESSION['stampe_contabili']['format'].'" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "select", "label": "'.tr('Orientamento').'", "name": "orientation", "required": "1", "values": "list=\"L\": \"'.tr('Orizzontale').'\", \"P\": \"'.tr('Verticale').'\"", "value": "'.$_SESSION['stampe_contabili']['orientation'].'" ]}
		</div>

		<div class="col-md-2">
			<p style="line-height:14px;">&nbsp;</p>
			
			<button type="submit" class="btn btn-primary btn-block">
				<i class="fa fa-print"></i> '.tr('Stampa').'
			</button>
		</div>
	</div>

</form>

<script>$(document).ready(init)</script>';

echo '
<script>
function stampa_registro_iva (){
	window.open("'.$link.'&dir='.$dir.'");
	return false;
}

$("#id_sezionale").change(function() {
	session_set("stampe_contabili,id_sezionale", $(this).val(), 0, 0);
});

$("#format").change(function() {
	session_set("stampe_contabili,format", $(this).val(), 0, 0);
});

$("#orientation").change(function() {
	session_set("stampe_contabili,orientation", $(this).val(), 0, 0);
});

$(function() {
   
});
</script>';
