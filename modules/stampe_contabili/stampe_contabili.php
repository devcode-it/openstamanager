<?php

include_once __DIR__.'/../../core.php';

$id_record = filter('id_record');
$dir = filter('dir');
$nome_stampa = filter('nome_stampa');

// Trovo id_print della stampa
$link = Prints::getHref($nome_stampa, $id_record);

echo '
<form action="" method="post" onsubmit="if($(this).parsley().validate()) { return stampa_registro_iva(); }" >

	<div class="row">
		<div class="col-md-4">
			{[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_sezionale", "required": "1", "values": "query=SELECT id AS id, name AS descrizione FROM zz_segments WHERE id_module = (SELECT id FROM zz_modules WHERE name = \''.(($dir == 'entrata') ? 'Fatture di vendita' : 'Fatture di acquisto').'\') AND is_fiscale = 1 UNION SELECT  0 AS id, \'Tutti i sezionali\' AS descrizione" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "date", "label": "'.tr('Data inizio').'", "required": "1", "name": "date_start", "value": "'.Translator::dateToLocale($_SESSION['period_start']).'" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "date", "label": "'.tr('Data fine').'", "required": "1", "name": "date_end", "value": "'.Translator::dateToLocale($_SESSION['period_end']).'" ]}
		</div>

	</div>';

echo '
	<div class="row">
		<div class="col-md-4">
			{[ "type": "select", "label": "'.tr('Formato').'", "name": "format", "required": "1", "values": "list=\"A4\": \"'.tr('A4').'\", \"A3\": \"'.tr('A3').'\"", "value": "'.$_SESSION['stampe_contabili']['format'].'" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "select", "label": "'.tr('Orientamento').'", "name": "orientation", "required": "1", "values": "list=\"L\": \"'.tr('Orizzontale').'\", \"P\": \"'.tr('Verticale').'\"", "value": "'.$_SESSION['stampe_contabili']['orientation'].'" ]}
		</div>

		<div class="col-md-4">
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
	window.open("'.$link.'&dir='.$dir.'&id_sezionale="+$("#id_sezionale").val()+"&date_start="+$("#date_start").val()+"&date_end="+$("#date_end").val()+"");
	return false;
}

$("#format").change(function() {
	session_set("stampe_contabili,format", $(this).val(), 0, 0);
});

$("#orientation").change(function() {
	session_set("stampe_contabili,orientation", $(this).val(), 0, 0);
});

$(function() {
   
});
</script>';
