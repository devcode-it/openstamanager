<?php

include_once __DIR__.'/../../../core.php';

$module = Modules::get('Stato dei servizi');

echo '
<script src="'.ROOTDIR.'/assets/dist/js/chartjs/Chart.min.js"></script>';

// Operazioni JavaScript
echo '
<script>

function formatBytes(a,b=2){if(0===a)return"0 Bytes";const c=0>b?0:b,d=Math.floor(Math.log(a)/Math.log(1024));return parseFloat((a/Math.pow(1024,d)).toFixed(c))+" "+["Bytes","KB","MB","GB","TB","PB","EB","ZB","YB"][d]}

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
        
        //Segnalazione se sul server sembrano mancare file rispetto a quanto previsto a DB
        if (element.dbSize!==""){
           if (element.size<element.dbSize){
                var diff = (element.dbSize-element.size);

                if (diff>1000){
                    $("#message").append("<div class=\"label label-warning\" ><i class=\"fa fa-exclamation-triangle\" aria-hidden=\"true\"></i> "+formatBytes(diff)+" di file mancanti per allegati.</div>");
                }
            }
        }

        //Segnalazione se sul server sembrano mancare file rispetto a quanto previsto a DB
        if (element.dbCount!==""){
           if (element.count<element.dbCount){
                var diff = (element.dbCount-element.count);

                $("#message").append("<div class=\"label label-warning\" ><i class=\"fa fa-exclamation-triangle\" aria-hidden=\"true\"></i> "+diff+" di file mancanti per allegati.</div>");
               
            }
        }

        $labels.push(element.description + " (" + element.formattedSize + ")" + " [" + element.count + "]" )

      
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

<div class="chart-container" style="width:25em;">
    <canvas id="chart"></canvas>
</div><div id="message" ></div>';
