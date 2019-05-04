<?php

include_once __DIR__.'/../../../core.php';

$module = Modules::get('Stato dei servizi');

echo '
<script src="'.ROOTDIR.'/assets/dist/js/chartjs/Chart.min.js"></script>';

// Operazioni JavaScript
echo '
<script>

$(document).ready(function() {
    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "get",
        data: {
            id_module: '.$module->id.',
            op: "sizes",
        },
        success: function(data) {
            data = JSON.parse(data);
            
            crea_grafico(data);
        }
    });
});

function crea_grafico(values){
	var ctx = $("#chart");

	$data = [];
	$labels = [];
	values.forEach(function(element) {
        $data.push(element.size);
        
        $labels.push(element.description + " (" + element.formattedSize + ")")
    });
	
	options = {
		legend: {
			display: true,
			position: "right"
		},
		animation:{
			animateScale: true,
			animateRotate: true,
		},
	};
	
	data = {
		datasets: [{
			data: $data,
			 backgroundColor: [
                \'rgba(255, 99, 132, 0.2)\',
                \'rgba(54, 162, 235, 0.2)\',
                \'rgba(255, 206, 86, 0.2)\',
                \'rgba(75, 192, 192, 0.2)\',
                \'rgba(153, 102, 255, 0.2)\',
                \'rgba(255, 159, 64, 0.2)\'
            ],
            borderColor: [
                \'rgba(255, 99, 132, 1)\',
                \'rgba(54, 162, 235, 1)\',
                \'rgba(255, 206, 86, 1)\',
                \'rgba(75, 192, 192, 1)\',
                \'rgba(153, 102, 255, 1)\',
                \'rgba(255, 159, 64, 1)\'
            ],
		}],
		
		labels: $labels,
	};

	var chart = new Chart(ctx, {
		type: "pie",
		data: data,
		options: options
	});
}
</script>

<div class="chart-container" style="width:20vw">
    <canvas id="chart"></canvas>
</div>';
