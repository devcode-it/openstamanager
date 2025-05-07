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

$id_module = Module::where('name', 'Stato dei servizi')->first()->id;

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
        if (element.dbSize > 0 && element.description == "Allegati"){
            if (element.size < element.dbSize){
                var diff = (element.dbSize - element.size);
                if (diff > 1000){
                    $("#warnings").append("<div class=\"alert alert-warning py-1 px-2 mb-1\"><small><i class=\"fa fa-exclamation-triangle mr-1\"></i>"+formatBytes(diff)+" di files mancanti per allegati.</small></div>");
                }
            }
        }

        //Segnalazione se sul server sembrano mancare file rispetto a quanto previsto a DB
        if (element.dbCount > 0 && element.description == "Allegati" ){
            if (element.count < element.dbCount){
                var diff = (element.dbCount - element.count);
                $("#warnings").append("<div class=\"alert alert-warning py-1 px-2 mb-1\"><small><i class=\"fa fa-exclamation-triangle mr-1\"></i>"+diff+" files non trovati per allegati.</small></div>");
            }
        }

        //Numero di file in Allegati suddivisi per estensione
        if (element.dbExtensions.length > 0){
            $("#file-types").append("<div class=\"card-header bg-light py-1 px-2\"><small><i class=\"fa fa-file mr-1\"></i>Top 10 allegati per estensione</small></div>");
            $("#file-types").append("<div class=\"card-body p-0\"><div class=\"list-group list-group-flush\" id=\"extensions-list\"></div></div>");

            element.dbExtensions.forEach(function(ext) {
                $("#extensions-list").append("<div class=\"list-group-item py-1 px-2 d-flex justify-content-between align-items-center\"><small><i class=\"fa fa-file-o mr-1 text-primary\"></i>"+ext.extension+"</small><small class=\"badge badge-primary badge-pill\">"+ext.num+"</small></div>");
            });
        }

        // Format labels to be more readable
        $labels.push(element.description + " (" + element.formattedSize + ")");
    });

    const options = {
        responsive: true,
        maintainAspectRatio: false,
        legend: {
            display: true,
            position: "right",
            labels: {
                boxWidth: 15,
                padding: 15,
                fontColor: "#555"
            }
        },
        animation: {
            animateScale: true,
            animateRotate: true,
            duration: 1000
        },
        tooltips: {
            callbacks: {
                title: function(tooltipItem, data) {
                    return data.labels[tooltipItem[0].index];
                },
                label: function(tooltipItem, data) {
                    const dataset = data.datasets[0];
                    const percent = Math.round((dataset.data[tooltipItem.index] / dataset._meta[0].total) * 100);
                    return formatBytes(dataset.data[tooltipItem.index]) + " (" + percent + "%)";
                },
            },
            backgroundColor: "#fff",
            titleFontSize: 13,
            titleFontColor: "#333",
            bodyFontColor: "#555",
            bodyFontSize: 12,
            displayColors: true,
            borderColor: "#ddd",
            borderWidth: 1
        }
	};

	data = {
		datasets: [{
			data: $data,
			backgroundColor: [
                "#4e73df80", // Blue
                "#1cc88a80", // Green
                "#f6c23e80", // Yellow
                "#e74a3b80", // Red
                "#36b9cc80", // Cyan
                "#6f42c180"  // Purple
            ],
            borderColor: [
                "#4e73df", // Blue
                "#1cc88a", // Green
                "#f6c23e", // Yellow
                "#e74a3b", // Red
                "#36b9cc", // Cyan
                "#6f42c1"  // Purple
            ],
            borderWidth: 2
		}],
		labels: $labels,
	};

	var chart = new Chart(ctx, {
		type: "doughnut",
		data: data,
		options: options
	});
}
</script>

<div class="row">
    <div class="col-md-8">
        <div class="chart-container" style="position: relative; height: 280px; margin-bottom: 10px;">
            <canvas id="chart"></canvas>
        </div>
    </div>
    <div class="col-md-4">
        <div id="warnings"></div>
        <div id="file-types" class="card mt-2"></div>
    </div>
</div>';
