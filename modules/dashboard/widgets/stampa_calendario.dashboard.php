<?php

include_once __DIR__.'/../../../core.php';

//trovo id_print della stampa
$id_print = Prints::getModulePredefinedPrint(1)['id'];

echo '
<form action="" method="post" onsubmit="if($(this).parsley().validate()) { return stampa_calendario(); }" >

	<div class="row">
		<div class="col-md-4">
			{[ "type": "text", "label": "'.tr('Mese e anno').'", "name": "date", "required": "1", "value": "'.$_SESSION['dashboard']['date'].'" ]}
		</div>
		
		<div class="col-md-2">
			{[ "type": "select", "label": "'.tr('Formato').'", "name": "format", "required": "1", "values": "list=\"A4\": \"'.tr('A4').'\", \"A3\": \"'.tr('A3').'\"", "value": "'.$_SESSION['dashboard']['format'].'" ]}
		</div>

		<div class="col-md-4">
			{[ "type": "select", "label": "'.tr('Orientamento').'", "name": "orientation", "required": "1", "values": "list=\"L\": \"'.tr('Orizzontale').'\", \"P\": \"'.tr('Verticale').'\"", "value": "'.$_SESSION['dashboard']['orientation'].'" ]}
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

?>
<script>
function stampa_calendario (){
	window.open('<?php echo $rootdir; ?>/pdfgen.php?id_print=<?php echo $id_print; ?>');

	return false;
}

$('#format').change(function() {
	session_set('settings,format', $(this).val(), 0, 0);
});

$('#orientation').change(function() {
	session_set('settings,orientation', $(this).val(), 0, 0);
});

$(function() {
    $('#date').datetimepicker({
        format: 'MMMM YYYY',
        locale: globals.locale,
        useCurrent: false,
    });

    $('#date').on('dp.change', function(e) {
        session_set('dashboard,date', e.date.format("YYYY-MM-DD"), 0, 0);
    });
});
</script>
