<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

include_once __DIR__.'/../../../core.php';
use Models\Module;

$id_module = (new Module())->getByField('title', 'Stato dei servizi', Models\Locale::getPredefined()->id);

echo '
<script src="'.base_path().'/assets/dist/js/chartjs/chart.min.js"></script>';

// Operazioni JavaScript
echo '
<script>

function formatBytes(a,b=2){if(0===a)return"0 Bytes";const c=0>b?0:b,d=Math.floor(Math.log(a)/Math.log(1024));return parseFloat((a/Math.pow(1024,d)).toFixed(c))+" "+["Bytes","KB","MB","GB","TB","PB","EB","ZB","YB"][d]}

$(document).ready(function() {
    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "get",
        data: {
            id_module: '.$id_module.',
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
        if (element.dbSize>0 && element.description == "Allegati"){
           if (element.size<element.dbSize){
                var diff = (element.dbSize-element.size);

                if (diff>1000){
                    $("#message").append("<div class=\"badge badge-warning\" ><i class=\"fa fa-exclamation-triangle\" aria-hidden=\"true\"></i> "+formatBytes(diff)+" di files mancanti per allegati.</div><br>");
                }
            }
        }


        //Segnalazione se sul server sembrano mancare file rispetto a quanto previsto a DB
        if (element.dbCount>0 && element.description == "Allegati" ){
           if (element.count<element.dbCount){
                var diff = (element.dbCount-element.count);

                $("#message").append("<div class=\"badge badge-warning\" ><i class=\"fa fa-exclamation-triangle\" aria-hidden=\"true\"></i> "+diff+" files non trovati per allegati.</div><br>");

            }
        }
        
        //Numero di file in Allegati suddivisi per estensione
        if (element.dbExtensions.length > 0){

            $("#message").append("<br><p><b>Top 10 allegati:</b></p>");

            element.dbExtensions.forEach(function(ext) {
                $("#message").append("<div class=\"badge badge-info\" ><i class=\"fa fa-file\" aria-hidden=\"true\"></i> <b>"+ext["num"]+"</b> files con estensione <b>"+ext["extension"]+"</b>.</div><br>");

            });

        }

        $labels.push(element.description + " (" + element.formattedSize + ")" + " [" + element.count + "]" )
    
    });

	options = {
        responsive: true,
        maintainAspectRatio: false,
		legend: {
			display: true,
			position: "right",
		},
		animation:{
			animateScale: true,
			animateRotate: true,
        },
        tooltips: {
            callbacks: {
              title: function(tooltipItem, data) {
                return data["labels"][tooltipItem[0]["index"]];
              },
              label: function(tooltipItem, data) {
                //return data["datasets"][0]["data"][tooltipItem["index"]];
                var dataset = data["datasets"][0];
                var percent = Math.round((dataset["data"][tooltipItem["index"]] / dataset["_meta"][0]["total"]) * 100)
                return "(" + percent + "%)";
              },
              afterLabel: function(tooltipItem, data) {
                //var dataset = data["datasets"][0];
                //var percent = Math.round((dataset["data"][tooltipItem["index"]] / dataset["_meta"][0]["total"]) * 100)
                //return "(" + percent + "%)";
              }
            },
            backgroundColor: "#fbfbfb",
            titleFontSize: 12,
            titleFontColor: "#000",
            bodyFontColor: "#444",
            bodyFontSize: 10,
            displayColors: true
          }
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
<div id="message" class="pull-right"></div>
<div class="chart-container">
    <canvas id="chart"></canvas>
</div>';
