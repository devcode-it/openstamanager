<?php

include_once __DIR__.'/../../../core.php';

//trovo id_print della stampa
$id_print = Prints::getModuleMainPrint(1)['id'];

echo '
<form action="" method="post" onsubmit="if($(this).parsley().validate()) { return stampa_calendario(); }" >

	<div class="row">

		<div class="col-md-6">
			{[ "type": "text", "label": "'.tr('Anno Mese').'", "name": "anno-mese", "required": "1", "extra":"readonly", "value": "'.$_SESSION['period']['month'].'" ]}
		</div>

		<div class="col-md-2">
			{[ "type": "select", "label": "'.tr('Formato').'", "name": "format", "required": "1", "values": "list=\"A4\": \"'.tr('A4').'\", \"A3\": \"'.tr('A3').'\"", "value": "'.$_SESSION['settings']['format'].'" ]}
		</div>

		<div class="col-md-2">
			{[ "type": "select", "label": "'.tr('Orientamento').'", "name": "orientation", "required": "1", "values": "list=\"L\": \"'.tr('Orizzontale').'\", \"P\": \"'.tr('Verticale').'\"", "value": "'.$_SESSION['settings']['orientation'].'" ]}
		</div>


		<div class="col-md-2">
			<p style="line-height:14px;">&nbsp;</p>
			<button type="submit" class="btn btn-primary"><i class="fa fa-print"></i> '.tr('Stampa').'</button>
		</div>

	</div>

</form>';

echo '<script src="'.$rootdir.'/lib/init.js"></script>';
echo '<link rel="stylesheet" type="text/css" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/themes/base/jquery-ui.css">';

?>
<style>
.ui-datepicker-calendar, .ui-datepicker-current{
		display:none;
}
</style>

<script>

function stampa_calendario (){
	window.open('<?php echo $rootdir; ?>/pdfgen.php?id_print=<?php echo $id_print; ?>');
	//$('button[type=submit]').removeAttr("disabled");
	//$('button[type=submit]').prop("disabled", false);
	return false;
}

$( '#format' ).change(function() {
	session_set('settings,format', $(this).val(), 0, 0);
});

$( '#orientation' ).change(function() {
	session_set('settings,orientation', $(this).val(), 0, 0);
});


$(function() {

	$('#anno-mese').datepicker( {

        changeMonth: true,
        changeYear: true,
        showButtonPanel: true,
        dateFormat: 'MM yy',
		todayBtn: false,

        onClose: function(dateText, inst) {
            $(this).datepicker('setDate', new Date(inst.selectedYear, inst.selectedMonth, 1));
			session_set('period,month', $('#anno-mese').val(), 0, 0);
        },

		beforeShow : function(input, inst) {
			session_set('period,month', '', 1, 0);
		}

    });
});

</script>