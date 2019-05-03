<?php

include_once __DIR__.'/../../../core.php';
echo '
<script src="'.ROOTDIR.'/assets/dist/js/chartjs/Chart.min.js"></script>';

// Operazioni JavaScript
echo '
<script>

var valori = [];

function loadSize(name, id){

    $("#" + id).html("'.tr('Calcolo in corso').'...");

    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "get",
        data: {
            id_module: globals.id_module,
            op: "size",
            folder: name,
        },
        success: function(data) {
            $("#" + id).html(data);
			valori.push(data); 
			
			//if (valori.length == 3)
				//crea_grafico(valori);
        }
    });
}

function crea_grafico (valori){
		
	var ctx = $("#chart");

	options = {
		legend: {
			display: true,
			position: "right"
		},
		legendCallback: function(chart) {
		
		},
		animation:{
			animateScale: true,
			animateRotate: true,
		}
		
	};
	data = {
		datasets: [{
			data: [parseFloat(valori[0]), parseFloat(valori[1]), parseFloat(valori[2])],
			backgroundColor: [
				"rgba(255, 99, 132, 0.2)",
				"rgba(54, 162, 235, 0.2)",
				"rgba(255, 206, 86, 0.2)",
			],
			borderColor: [
				"rgba(255, 99, 132, 1)",
				"rgba(54, 162, 235, 1)",
				"rgba(255, 206, 86, 1)",
			]
		}],

		
		labels: [
			"Backup ("+valori[0]+")",
			"Allegati ("+valori[1]+")",
			"Logs ("+valori[2]+")"
		]
		
	};

	var myPieChart = new Chart(ctx, {
		type: "pie",
		data: data,
		options: options
	});	
}

</script>';


echo '
    <div class="col-md-6">
	
		<span class="label label-info hide">'.tr('SPAZIO UTILIZZATO: _SPAZIO_', [
			'_SPAZIO_' => '<i id="total_size"></i>',
		]).'<br></span>
		<span class="label label-danger"><i class="fa fa-archive" aria-hidden="true"></i> '.tr('BACKUP: _SPAZIO_BACKUP_', [
			'_SPAZIO_BACKUP_' => '<i id="backup_size"></i>',
		]).'<br></span>
		<span class="label label-primary"><i class="fa fa-paperclip" aria-hidden="true"></i> '.tr('ALLEGATI: _SPAZIO_FILES_', [
			'_SPAZIO_FILES_' => '<i id="files_size"></i>',
		]).'<br></span>
		<span class="label label-warning"><i class="fa fa-file-text" aria-hidden="true"></i> '.tr('LOGS: _SPAZIO_LOGS_', [
			'_SPAZIO_LOGS_' => '<i id="logs_size"></i>',
		]).'</span>
		
    </div>
	<div class="col-md-6">
		<canvas id="chart"></canvas>
		<!--div class="chart-container" style="position: relative; width:9vw">
		
		</div-->
	</div>

<script>
	//loadSize("", "total_size");
	loadSize("backup", "backup_size");
	loadSize("files", "files_size");
	loadSize("logs", "logs_size");
</script>';

?>

