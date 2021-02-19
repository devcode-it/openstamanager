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

include_once __DIR__.'/../../core.php';

use Modules\Statistiche\Stats;

echo '
<script src="'.base_path().'/assets/dist/js/chartjs/Chart.min.js"></script>';

$start = $_SESSION['period_start'];
$end = $_SESSION['period_end'];

echo '
<div class="box box-warning">
    <div class="box-header">
        <h4 class="box-title">
            '.tr('Periodi temporali').'
        </h4>
        <div class="box-tools pull-right">
            <button class="btn btn-warning btn-xs" onclick="add_calendar()">
                <i class="fa fa-plus"></i> '.tr('Aggiungi periodo').'
            </button>
            <button type="button" class="btn btn-box-tool" data-widget="collapse">
                <i class="fa fa-minus"></i>
            </button>
        </div>
    </div>

    <div class="box-body collapse in" id="calendars">

    </div>
</div>';

// Fatturato
echo '
<div class="box box-success">
    <div class="box-header with-border">
        <h3 class="box-title">'.tr('Vendite e acquisti').'</h3>

        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse">
                <i class="fa fa-minus"></i>
            </button>
        </div>
    </div>
    <canvas class="box-body collapse in" id="fatturato" height="100"></canvas>
</div>';

echo '
<script src="'.$structure->fileurl('js/functions.js').'"></script>
<script src="'.$structure->fileurl('js/calendar.js').'"></script>
<script src="'.$structure->fileurl('js/manager.js').'"></script>
<script src="'.$structure->fileurl('js/stat.js').'"></script>
<script src="'.$structure->fileurl('js/stats/line_chart.js').'"></script>';

// Script per il grafico del fatturato
echo '
<script>
start = moment("'.$start.'");
end = moment("'.$end.'");

months = get_months(start, end);

var chart_options = {
    type: "line",
    data: {
        labels: [],
        datasets: [],
    },
    options: {
        responsive: true,
        tooltips: {
            callbacks: {
                label: function(tooltipItem, data) {
                    var dataset = data.datasets[tooltipItem.datasetIndex];
                    var label = dataset.labels ? dataset.labels[tooltipItem.index] : "";

                    if (label) {
                        label += ": ";
                    }

                    label += tooltipItem.yLabel;

                    return label;
                }
            }
        },
        elements: {
            line: {
                tension: 0
            }
        },
        annotation: {
            annotations: [{
                type: "line",
                mode: "horizontal",
                scaleID: "y-axis-0",
                value: 0,
                label: {
                    enabled: false,
                }
            }]
        },
        hover: {
            mode: "nearest",
            intersect: false
        },
        scales: {
            xAxes: [{
                display: true,
                scaleLabel: {
                    display: true,
                    labelString: "'.tr('Periodo').'"
                }
            }],
            yAxes: [{
                display: true,
                scaleLabel: {
                    display: true,
                    labelString: "'.tr('Andamento').'"
                },
                ticks: {
                    // Include a dollar sign in the ticks
                    callback: function(value, index, values) {
                        return \''.html_entity_decode(currency()).' \' + value;
                    }
                }
            }]
        },
    }
};

// Inzializzazione manager
var info = {
    url: "'.str_replace('edit.php', '', $structure->fileurl('edit.php')).'",
    id_module: globals.id_module,
    id_record: globals.id_record,
    start_date: globals.start_date,
    end_date: globals.end_date,
}
var manager = new Manager(info);

var chart_fatturato, chart_acquisti;
$(document).ready(function() {
    var fatturato_canvas = document.getElementById("fatturato").getContext("2d");
    //var acquisti_canvas = document.getElementById("acquisti").getContext("2d");

    chart_fatturato = new Chart(fatturato_canvas, chart_options);
    //chart_acquisti = new Chart(fatturato_canvas, chart_options);

    add_calendar();
});

function init_calendar(calendar) {
    var fatturato = new LineChart(calendar, "actions.php", {op: "fatturato"}, chart_fatturato);
    var acquisti = new LineChart(calendar, "actions.php", {op: "acquisti"}, chart_fatturato);

    calendar.addElement(fatturato);
    calendar.addElement(acquisti);
}
</script>';

// Clienti top
$clienti = $dbo->fetchArray('SELECT SUM(co_righe_documenti.subtotale - co_righe_documenti.sconto) AS totale, (SELECT COUNT(*) FROM co_documenti WHERE co_documenti.idanagrafica =an_anagrafiche.idanagrafica AND co_documenti.data BETWEEN '.prepare($start).' AND '.prepare($end).") AS qta, an_anagrafiche.idanagrafica, an_anagrafiche.ragione_sociale FROM co_documenti INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id INNER JOIN co_righe_documenti ON co_righe_documenti.iddocumento=co_documenti.id INNER JOIN an_anagrafiche ON an_anagrafiche.idanagrafica=co_documenti.idanagrafica WHERE co_tipidocumento.dir='entrata' AND co_documenti.data BETWEEN ".prepare($start).' AND '.prepare($end).' GROUP BY an_anagrafiche.idanagrafica ORDER BY SUM(subtotale - co_righe_documenti.sconto) DESC LIMIT 20');

$totale = $dbo->fetchArray("SELECT SUM(co_righe_documenti.subtotale - co_righe_documenti.sconto) AS totale FROM co_documenti INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id INNER JOIN co_righe_documenti ON co_righe_documenti.iddocumento=co_documenti.id WHERE co_tipidocumento.dir='entrata' AND co_documenti.data BETWEEN ".prepare($start).' AND '.prepare($end));

echo '
<div class="row">
    <div class="col-md-6">
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title">'.tr('I 20 clienti TOP').'</h3><span class="tip" title="'.tr('Valori iva esclusa').'"> <i class="fa fa-question-circle-o" aria-hidden="true"></i></span>

                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse">
                        <i class="fa fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="box-body collapse in">';
if (!empty($clienti)) {
    echo '
                <table class="table table-striped">
                    <tr>
                        <th>'.tr('Ragione sociale').'</th>
                        <th class="text-center">'.tr('Num. fatture').'</th>
                        <th class="text-right" width="120">'.tr('Totale').'</th>
                        <th class="text-right" width="120">'.tr('Percentuale').'<span class="tip" title="'.tr('Incidenza sul fatturato').'">&nbsp;<i class="fa fa-question-circle-o" aria-hidden="true"></i></span></th>
                    </tr>';
    foreach ($clienti as $cliente) {
        echo '
                    <tr>
                        <td>'.Modules::link('Anagrafiche', $cliente['idanagrafica'], $cliente['ragione_sociale']).'</td>
                        <td class="text-right">'.intval($cliente['qta']).'</td>
                        <td class="text-right">'.moneyFormat($cliente['totale'], 2).'</td>
                        <td class="text-right">'.Translator::numberToLocale($cliente['totale'] * 100 / $totale[0]['totale'], 2).' %</td>
                    </tr>';
    }
    echo '
                </table>';
} else {
    echo '
                <p>'.tr('Nessuna vendita').'...</p>';
}
echo '

            </div>
        </div>
    </div>';

// Articoli più venduti
$articoli = $dbo->fetchArray("SELECT SUM(co_righe_documenti.qta) AS qta,  SUM(co_righe_documenti.subtotale - co_righe_documenti.sconto) AS totale, mg_articoli.id, mg_articoli.codice, mg_articoli.descrizione, mg_articoli.um FROM co_documenti INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id INNER JOIN co_righe_documenti ON co_righe_documenti.iddocumento=co_documenti.id INNER JOIN mg_articoli ON mg_articoli.id=co_righe_documenti.idarticolo WHERE co_tipidocumento.dir='entrata' AND co_documenti.data BETWEEN ".prepare($start).' AND '.prepare($end).' GROUP BY co_righe_documenti.idarticolo ORDER BY SUM(co_righe_documenti.qta) DESC LIMIT 20');

$totale = $dbo->fetchArray("SELECT SUM(co_righe_documenti.qta) AS totale_qta, SUM(co_righe_documenti.subtotale - co_righe_documenti.sconto) AS totale FROM co_documenti INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id INNER JOIN co_righe_documenti ON co_righe_documenti.iddocumento=co_documenti.id INNER JOIN mg_articoli ON mg_articoli.id=co_righe_documenti.idarticolo WHERE co_tipidocumento.dir='entrata' AND co_documenti.data BETWEEN ".prepare($start).' AND '.prepare($end));

echo '
    <div class="col-md-6">
        <div class="box box-danger">
            <div class="box-header with-border">
                <h3 class="box-title">'.tr('I 20 articoli più venduti').'</h3><span class="tip" title="'.tr('Valori iva esclusa').'"> <i class="fa fa-question-circle-o" aria-hidden="true"></i></span>

                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse">
                        <i class="fa fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="box-body collapse in">';
if (!empty($articoli)) {
    echo '
                <table class="table table-striped">
                    <tr>
                        <th>'.tr('Articolo').'</th>
                        <th class="text-right" width="120">'.tr('Q.tà').'</th>
                        <th class="text-right" width="120">'.tr('Percentuale').'<span class="tip" title="'.tr('Incidenza sul numero di articoli venduti').'"> <i class="fa fa-question-circle-o" aria-hidden="true"></i></span></th>
                        <th class="text-right" width="120">'.tr('Totale').'</th>
                    </tr>';
    foreach ($articoli as $articolo) {
        echo '
                    <tr>
                        <td>'.Modules::link('Articoli', $articolo['id'], $articolo['codice'].' - '.$articolo['descrizione']).'</td>
                        <td class="text-right">'.Translator::numberToLocale($articolo['qta'], 'qta').' '.$articolo['um'].'</td>
                        <td class="text-right">'.Translator::numberToLocale($articolo['qta'] * 100 / $totale[0]['totale_qta'], 2).' %</td>
                        <td class="text-right">'.moneyFormat($articolo['totale'], 2).'</td>
                    </tr>';
    }
    echo '
                </table>';
} else {
    echo '
                <p>'.tr('Nessun articolo è stato venduto').'...</p>';
}
echo '

            </div>
        </div>
    </div>
</div>';

// Interventi per tipologia
$tipi = $dbo->fetchArray('SELECT * FROM `in_tipiintervento`');

$dataset = '';
foreach ($tipi as $tipo) {
    $interventi = $dbo->fetchArray('SELECT COUNT(*) AS result, YEAR(in_interventi.data_richiesta) AS year, MONTH(in_interventi.data_richiesta) AS month FROM in_interventi WHERE in_interventi.idtipointervento = '.prepare($tipo['idtipointervento']).' AND in_interventi.data_richiesta BETWEEN '.prepare($start).' AND '.prepare($end).' GROUP BY YEAR(in_interventi.data_richiesta), MONTH(in_interventi.data_richiesta) ORDER BY YEAR(in_interventi.data_richiesta) ASC, MONTH(in_interventi.data_richiesta) ASC');

    $interventi = Stats::monthly($interventi, $start, $end);

    //Random color
    $background = '#'.dechex(rand(256, 16777215));

    $dataset .= '{
        label: "'.$tipo['descrizione'].'",
        backgroundColor: "'.$background.'",
        data: [
            '.implode(',', array_column($interventi, 'result')).'
        ]
    },';
}

echo '
<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">'.tr('Interventi per tipologia').'</h3>

        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse">
                <i class="fa fa-minus"></i>
            </button>
        </div>
    </div>
    <canvas class="box-body collapse in" id="interventi" height="100"></canvas>
</div>';

// Script per il grafico degli interventi per tipologia
echo '
<script>
$(document).ready(function() {
    new Chart(document.getElementById("interventi").getContext("2d"), {
        type: "bar",
        data: {
            labels: months,
            datasets: [
                '.$dataset.'
            ]
        },
        options: {
            responsive: true,
            legend: {
                position: "bottom",
            },
        }
    });
});
</script>';

// Interventi per tecnico
$tecnici = $dbo->fetchArray("SELECT an_anagrafiche.idanagrafica AS id, ragione_sociale, colore FROM an_anagrafiche
INNER JOIN
an_tipianagrafiche_anagrafiche ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica
INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica
LEFT OUTER JOIN in_interventi_tecnici ON in_interventi_tecnici.idtecnico = an_anagrafiche.idanagrafica
INNER JOIN in_interventi ON in_interventi_tecnici.idintervento=in_interventi.id
WHERE an_anagrafiche.deleted_at IS NULL AND an_tipianagrafiche.descrizione='Tecnico'
GROUP BY an_anagrafiche.idanagrafica
ORDER BY ragione_sociale ASC");

$dataset = '';
foreach ($tecnici as $tecnico) {
    $sessioni = $dbo->fetchArray('SELECT SUM(in_interventi_tecnici.ore) AS result, CONCAT(CAST(SUM(in_interventi_tecnici.ore) AS char(20)),\' ore\') AS ore_lavorate, YEAR(in_interventi_tecnici.orario_inizio) AS year, MONTH(in_interventi_tecnici.orario_inizio) AS month FROM in_interventi_tecnici  INNER JOIN `in_interventi` ON `in_interventi_tecnici`.`idintervento` = `in_interventi`.`id` LEFT JOIN `in_statiintervento` ON `in_interventi`.`idstatointervento`=`in_statiintervento`.`idstatointervento` WHERE in_interventi_tecnici.idtecnico = '.prepare($tecnico['id']).' AND in_interventi_tecnici.orario_inizio BETWEEN '.prepare($start).' AND '.prepare($end).' AND `in_statiintervento`.`is_completato` = 1 GROUP BY YEAR(in_interventi_tecnici.orario_inizio), MONTH(in_interventi_tecnici.orario_inizio) ORDER BY YEAR(in_interventi_tecnici.orario_inizio) ASC, MONTH(in_interventi_tecnici.orario_inizio) ASC');

    $sessioni = Stats::monthly($sessioni, $start, $end);

    //Colore tecnico
    $background = $tecnico['colore'];
    if (empty($background) || $background == '#FFFFFF') {
        //Random color
        $background = '#'.dechex(rand(256, 16777215));
    }

    $dataset .= '{
        label: "'.$tecnico['ragione_sociale'].'",
        backgroundColor: "'.$background.'",
        data: [
            '.implode(',', array_column($sessioni, 'result')).'
        ],

    },';
}

echo '
<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">'.tr('Ore di lavoro per tecnico').'</h3>

        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse">
                <i class="fa fa-minus"></i>
            </button>
        </div>
    </div>
    <canvas class="box-body collapse in" id="sessioni" height="100"></canvas>
</div>';

// Script per il grafico ore interventi per tecnico
echo '
<script>
$(document).ready(function() {
    new Chart(document.getElementById("sessioni").getContext("2d"), {
        type: "horizontalBar",
        data: {
            labels: months,
            datasets: [
                '.$dataset.'
            ]
        },
        options: {
            responsive: true,
            legend: {
                position: "bottom",
            },
            scales: {
                xAxes: [{
                    ticks: {
                        // Include a dollar sign in the ticks
                        callback: function(value, index, values) {
                            var text = "";
                            if (value<=1){
                                text = " ora";
                            }else{
                                text = " ore";
                            }
                            return value + text;
                        }
                    }
                }]
            },

            tooltips: {
                callbacks: {
                    label: function(tooltipItem, data) {
                        var dataset = data.datasets[tooltipItem.datasetIndex];
                        var label = dataset.labels ? dataset.labels[tooltipItem.index] : "";
    
                        if (label) {
                            label += ": ";
                        }
    
                        label += tooltipItem.xLabel+" ore (attività completate)";
    
                        return label;
                    }
                }
            },
        }
    });
});
</script>';
