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

use Models\Plugin;
use Modules\Statistiche\Stats;

echo '
<script src="'.base_path().'/assets/dist/js/chartjs/chart.min.js"></script>
<script src="'.$structure->fileurl('js/functions.js').'"></script>
<script src="'.$structure->fileurl('js/calendar.js').'"></script>
<script src="'.$structure->fileurl('js/manager.js').'"></script>
<script src="'.$structure->fileurl('js/stat.js').'"></script>
<script src="'.$structure->fileurl('js/stats/line_chart.js').'"></script>';

$start = $_SESSION['period_start'];
$end = $_SESSION['period_end'];

$translated_months = [tr('Gennaio'), tr('Febbraio'), tr('Marzo'), tr('Aprile'), tr('Maggio'), tr('Giugno'), tr('Luglio'), tr('Agosto'), tr('Settembre'), tr('Ottobre'), tr('Novembre'), tr('Dicembre')];

// Fatturato
echo '
<div class="card card-info">
    <div class="card-header">
        <h4 class="card-title">'.tr('Vendite e acquisti').'</h4>

        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fa fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="card card-warning collapsed-card">
            <div class="card-header">
                <h4 class="card-title">'.tr('Periodi temporali').'</h4>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fa fa-plus"></i>
                    </button>
                </div>
            </div>

            <div class="card-body" id="calendars">
                <div class="row">
                    <button class="btn btn-warning btn-sm" onclick="add_calendar()">
                        <i class="fa fa-plus"></i> '.tr('Aggiungi periodo').'
                    </button>
                </div>
            <br>
            </div>
        </div>
        <canvas id="fatturato" height="50"></canvas>
    </div>
</div>';

// Script per il grafico del fatturato
echo '
<script>
start = moment("'.$start.'");
end = moment("'.$end.'");

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
                    enabled: true,
                }
            }]
        },
        hover: {
            mode: "nearest",
            intersect: false
        },
        scales: {
            x: {
                display: true,
                title: {
                    display: true,
                    text: "'.tr('Periodo').'"
                }
            },
            y: {
                display: true,
                title: {
                    display: true,
                    text: "'.tr('Andamento').'"
                },
                ticks: {
                    // Include a dollar sign in the ticks
                    callback: function(value, index, values) {
                        return \''.html_entity_decode(currency()).' \' + value;
                    }
                }
            }
        },
    }
};

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

    chart_fatturato = new Chart(fatturato_canvas, chart_options);

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
$clienti = $dbo->fetchArray('SELECT 
        SUM(IF(`reversed`=1, - (`co_righe_documenti`.`subtotale` - `co_righe_documenti`.`sconto`), (`co_righe_documenti`.`subtotale` - `co_righe_documenti`.`sconto`))) AS totale, 
        (SELECT 
            COUNT(*) 
        FROM 
            `co_documenti` 
            INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento`=`co_tipidocumento`.`id`
            INNER JOIN `zz_segments` ON `co_documenti`.`id_segment`=`zz_segments`.`id` 
        WHERE 
            `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica` AND `co_documenti`.`data` BETWEEN '.prepare($start).' AND '.prepare($end)." AND `co_tipidocumento`.`dir`='entrata' AND `zz_segments`.`autofatture`=0) AS qta, 
        `an_anagrafiche`.`idanagrafica`, 
        `an_anagrafiche`.`ragione_sociale` 
    FROM 
        `co_documenti` 
        INNER JOIN `co_statidocumento` ON `co_statidocumento`.`id` = `co_documenti`.`idstatodocumento`
        LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento_lang`.`id_record` = `co_statidocumento`.`id` AND `co_statidocumento_lang`.`id_lang` = ".prepare(Models\Locale::getDefault()->id).")
        INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento`=`co_tipidocumento`.`id` 
        INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`iddocumento`=`co_documenti`.`id` 
        INNER JOIN `an_anagrafiche` ON `an_anagrafiche`.`idanagrafica`=`co_documenti`.`idanagrafica` 
        INNER JOIN `zz_segments` ON `co_documenti`.`id_segment`=`zz_segments`.`id` 
    WHERE 
        `co_tipidocumento`.`dir`='entrata' 
        AND `co_statidocumento_lang`.`title` IN('Pagato', 'Parzialmente pagato', 'Emessa') 
        AND `co_documenti`.`data` BETWEEN ".prepare($start).' AND '.prepare($end).' 
        AND `zz_segments`.`autofatture`=0 
    GROUP BY 
        `an_anagrafiche`.`idanagrafica` 
    ORDER BY 
        `totale` DESC LIMIT 20');

$totale = $dbo->fetchArray('SELECT 
        SUM(IF(`reversed`=1, -(`co_righe_documenti`.`subtotale` - `co_righe_documenti`.`sconto`), (`co_righe_documenti`.`subtotale` - `co_righe_documenti`.`sconto`))) AS totale 
    FROM 
        `co_documenti` 
        INNER JOIN `co_statidocumento` ON `co_statidocumento`.`id` = `co_documenti`.`idstatodocumento`
        LEFT JOIN `co_statidocumento_lang` ON (`co_statidocumento_lang`.`id_record` = `co_statidocumento`.`id` AND `co_statidocumento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).") 
        INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento`=`co_tipidocumento`.`id` 
        INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`iddocumento`=`co_documenti`.`id` 
        INNER JOIN `zz_segments` ON `co_documenti`.`id_segment`=`zz_segments`.`id` 
    WHERE 
        `co_statidocumento_lang`.`title` IN ('Pagato', 'Parzialmente pagato', 'Emessa') 
        AND `co_tipidocumento`.`dir`='entrata' 
        AND `co_documenti`.`data` BETWEEN ".prepare($start).' AND '.prepare($end).' 
        AND `zz_segments`.`autofatture`=0');

echo '
<div class="row">
    <div class="col-md-6">
        <div class="card card-info">
            <div class="card-header">
                <h4 class="card-title">'.tr('I 20 clienti TOP per il periodo').': '.Translator::dateToLocale($start).' - '.Translator::dateToLocale($end).'</h4>

                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fa fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body" height="500">';
if (!empty($clienti)) {
    echo '
                <table class="table table-striped">
                    <tr>
                        <th>'.tr('Ragione sociale').'</th>
                        <th class="text-right" width="100">'.tr('N. fatture').'</th>
                        <th class="text-right" width="120">'.tr('Totale').'<span class="tip" title="'.tr('Valori iva esclusa').'"> <i class="fa fa-question-circle-o" aria-hidden="true"></i></span></th>
                        <th class="text-right" width="120">'.tr('Percentuale').'<span class="tip" title="'.tr('Incidenza sul fatturato').'">&nbsp;<i class="fa fa-question-circle-o" aria-hidden="true"></i></span></th>
                    </tr>';
    foreach ($clienti as $cliente) {
        echo '
                    <tr>
                        <td>'.Modules::link('Anagrafiche', $cliente['idanagrafica'], $cliente['ragione_sociale']).'</td>
                        <td class="text-right">'.intval($cliente['qta']).'</td>
                        <td class="text-right">'.moneyFormat($cliente['totale'], 2).'</td>
                        <td class="text-right">'.Translator::numberToLocale($cliente['totale'] * 100 / ($totale[0]['totale'] != 0 ? $totale[0]['totale'] : 1), 2).' %</td>
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
$articoli = $dbo->fetchArray('SELECT 
        SUM(IF(`reversed`=1, -`co_righe_documenti`.`qta`, `co_righe_documenti`.`qta`)) AS qta,  
        SUM(IF(`reversed`=1, -(`co_righe_documenti`.`subtotale` - `co_righe_documenti`.`sconto`), (`co_righe_documenti`.`subtotale` - `co_righe_documenti`.`sconto`))) AS totale, 
        `mg_articoli`.`id`, 
        `mg_articoli`.`codice`, 
        `mg_articoli_lang`.`title` as descrizione, 
        `mg_articoli`.`um` 
    FROM 
        `co_documenti` 
        INNER JOIN `co_statidocumento` ON `co_statidocumento`.`id` = `co_documenti`.`idstatodocumento`
        LEFT JOIN `co_statidocumento_lang` ON `co_statidocumento_lang`.`id_record` = `co_statidocumento`.`id` AND `co_statidocumento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).'
        INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento`=`co_tipidocumento`.`id` 
        INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`iddocumento`=`co_documenti`.`id` 
        INNER JOIN `mg_articoli` ON `mg_articoli`.`id`=`co_righe_documenti`.`idarticolo` 
        LEFT JOIN `mg_articoli_lang` ON (`mg_articoli_lang`.`id_record`=`mg_articoli`.`id` AND `mg_articoli_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).")
        INNER JOIN `zz_segments` ON `co_documenti`.`id_segment`=`zz_segments`.`id` 
    WHERE 
        `co_tipidocumento`.`dir`='entrata' 
        AND `co_statidocumento_lang`.`title` IN ('Pagato', 'Parzialmente pagato', 'Emessa') 
        AND `co_documenti`.`data` BETWEEN ".prepare($start).' AND '.prepare($end).' 
        AND `zz_segments`.`autofatture`=0 
    GROUP BY 
        `co_righe_documenti`.`idarticolo` 
    ORDER BY 
        `qta` DESC LIMIT 20');

$totale = $dbo->fetchArray('SELECT 
        SUM(IF(`reversed`=1, - `co_righe_documenti`.`qta`, `co_righe_documenti`.`qta`)) AS totale_qta,
        SUM(IF(`reversed`=1, - (`co_righe_documenti`.`subtotale` - `co_righe_documenti`.`sconto`), (`co_righe_documenti`.`subtotale` - `co_righe_documenti`.`sconto`))) AS totale 
    FROM 
        `co_documenti` 
        INNER JOIN `co_statidocumento` ON `co_statidocumento`.`id` = `co_documenti`.`idstatodocumento` 
        LEFT JOIN `co_statidocumento_lang` ON `co_statidocumento_lang`.`id_record` = `co_statidocumento`.`id` AND `co_statidocumento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id)."
        INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento`=`co_tipidocumento`.`id` 
        INNER JOIN `co_righe_documenti` ON `co_righe_documenti`.`iddocumento`=`co_documenti`.`id` 
        INNER JOIN `mg_articoli` ON `mg_articoli`.`id`=`co_righe_documenti`.`idarticolo` 
        INNER JOIN `zz_segments` ON `co_documenti`.`id_segment`=`zz_segments`.`id` 
    WHERE 
        `co_tipidocumento`.`dir`='entrata' 
        AND `co_statidocumento_lang`.`title` IN ('Pagato', 'Parzialmente pagato', 'Emessa') 
        AND `co_documenti`.`data` BETWEEN ".prepare($start).' AND '.prepare($end).' 
        AND `zz_segments`.`autofatture`=0');

echo '
    <div class="col-md-6">
        <div class="card card-info">
            <div class="card-header">
                <h4 class="card-title">'.tr('I 20 articoli più venduti per il periodo').': '.Translator::dateToLocale($start).' - '.Translator::dateToLocale($end).'</h4>

                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fa fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body" height="500">';
if (!empty($articoli)) {
    echo '
                <table class="table table-striped">
                    <tr>
                        <th>'.tr('Articolo').'</th>
                        <th class="text-right" width="100">'.tr('N. articoli').'<span class="tip" title="'.tr('Numero di articoli venduti').'"> <i class="fa fa-question-circle" aria-hidden="true"></i></span></th>
                        <th class="text-right" width="120">'.tr('Percentuale').'<span class="tip" title="'.tr('Incidenza sul numero di articoli').'"> <i class="fa fa-question-circle-o" aria-hidden="true"></i></span></th>
                        <th class="text-right" width="120">'.tr('Totale').'<span class="tip" title="'.tr('Valori iva esclusa').'"> <i class="fa fa-question-circle-o" aria-hidden="true"></i></span></th>
                    </tr>';
    foreach ($articoli as $articolo) {
        echo '
                    <tr>
                        <td><div class="shorten"> '.Modules::link('Articoli', $articolo['id'], $articolo['codice'].' - '.$articolo['descrizione']).'</div></td>
                        <td class="text-right">'.Translator::numberToLocale($articolo['qta'], 'qta').' '.$articolo['um'].'</td>
                        <td class="text-right">'.Translator::numberToLocale($articolo['qta'] * 100 / ($totale[0]['totale_qta'] != 0 ? $totale[0]['totale_qta'] : 1), 2).' %</td>
                        <td class="text-right">'.moneyFormat($articolo['totale'], 2).'</td>
                    </tr>';
    }
    echo '
                </table>';

    echo "<br><p class='float-right' >".Modules::link('Articoli', null, tr('Vedi tutto...'), null, null, false, 'tab_'.Plugin::where('name', 'Statistiche vendita')->first()->id).'</p>';
} else {
    echo '
                <p>'.tr('Nessun articolo venduto').'...</p>';
}
echo '

            </div>
        </div>
    </div>
</div>';

// Numero interventi per tipologia
$tipi = $dbo->fetchArray('SELECT * FROM `in_tipiintervento` LEFT JOIN `in_tipiintervento_lang` ON (`in_tipiintervento`.`id` = `in_tipiintervento_lang`.`id_record` AND `in_tipiintervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')');

$dataset = '';
foreach ($tipi as $tipo) {
    $interventi = $dbo->fetchArray('
    SELECT
        COUNT(`in_interventi`.`id`) AS result,
        YEAR(`sessioni`.`orario_fine`) AS `year`,
        MONTH(`sessioni`.`orario_fine`) AS `month`
    FROM
        `in_interventi`
        LEFT JOIN(SELECT `in_interventi_tecnici`.`idintervento`, MAX(`orario_fine`) AS orario_fine FROM `in_interventi_tecnici` GROUP BY `idintervento`) sessioni ON `in_interventi`.`id` = `sessioni`.`idintervento`
    WHERE
        `in_interventi`.`idtipointervento` = '.prepare($tipo['id']).' 
        AND `sessioni`.`orario_fine` BETWEEN '.prepare($start).' AND '.prepare($end).'
    GROUP BY
        YEAR(`sessioni`.`orario_fine`),
        MONTH(`sessioni`.`orario_fine`)
    ORDER BY
        YEAR(`sessioni`.`orario_fine`) ASC,
        MONTH(`sessioni`.`orario_fine`) ASC');

    $interventi = Stats::monthly($interventi, $start, $end);

    // Random color
    $background = '#'.dechex(random_int(256, 16777215));

    $dataset .= '{
        label: "'.$tipo['title'].'",
        backgroundColor: "'.$background.'",
        data: [
            '.implode(',', array_column($interventi, 'result')).'
        ]
    },';
}

echo '
<div class="row">
    <div class="col-md-6">
        <div class="card card-info">
            <div class="card-header">
                <h4 class="card-title">'.tr('Numero interventi per tipologia').'</h4>

                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fa fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="interventi_n_tipologia"></canvas>
            </div>
        </div>
    </div>';

// Script for the chart displaying the number of interventions by type
echo '
<script>
$(document).ready(function() {
    var translatedMonths = '.json_encode($translated_months).';

    new Chart(document.getElementById("interventi_n_tipologia").getContext("2d"), {
        type: "bar",
        data: {
            labels: translatedMonths,
            datasets: [
                '.$dataset.'
            ]
        },
    });
});
</script>';

// Ore interventi per tipologia
$dataset = '';
foreach ($tipi as $tipo) {
    $interventi = $dbo->fetchArray('SELECT ROUND(SUM(in_interventi_tecnici.ore), 2) AS result, YEAR(in_interventi_tecnici.orario_fine) AS year, MONTH(in_interventi_tecnici.orario_fine) AS month FROM in_interventi INNER JOIN in_interventi_tecnici ON in_interventi.id=in_interventi_tecnici.idintervento WHERE in_interventi.idtipointervento = '.prepare($tipo['id']).' AND in_interventi.data_richiesta BETWEEN '.prepare($start).' AND '.prepare($end).' AND in_interventi_tecnici.orario_fine BETWEEN '.prepare($start).' AND '.prepare($end).' GROUP BY YEAR(in_interventi_tecnici.orario_fine), MONTH(in_interventi_tecnici.orario_fine) ORDER BY YEAR(in_interventi_tecnici.orario_fine) ASC, MONTH(in_interventi_tecnici.orario_fine) ASC');

    $interventi = Stats::monthly($interventi, $start, $end);

    // Random color
    $background = '#'.dechex(random_int(256, 16777215));

    $dataset .= '{
        label: "'.$tipo['title'].'",
        backgroundColor: "'.$background.'",
        data: [
            '.implode(',', array_column($interventi, 'result')).'
        ]
    },';
}

echo '
    <div class="col-md-6">
        <div class="card card-info">
            <div class="card-header">
                <h4 class="card-title">'.tr('Ore interventi per tipologia').'</h4>

                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fa fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="interventi_ore_tipologia"></canvas>
            </div>
        </div>
    </div>
</div>';

// Script per il grafico delle ore interventi per tipologia
echo '
<script>
$(document).ready(function() {
    var translatedMonths = '.json_encode($translated_months).';
    new Chart(document.getElementById("interventi_ore_tipologia").getContext("2d"), {
        type: "bar",
        data: {
            labels: translatedMonths,
            datasets: [
                '.$dataset.'
            ]
        },
    });
});
</script>';

// Interventi per tecnico
$tecnici = $dbo->fetchArray('SELECT `an_anagrafiche`.`idanagrafica` AS id, `ragione_sociale`, `colore` 
FROM 
    `an_anagrafiche`
    INNER JOIN `an_tipianagrafiche_anagrafiche` ON `an_anagrafiche`.`idanagrafica`=`an_tipianagrafiche_anagrafiche`.`idanagrafica`
    INNER JOIN `an_tipianagrafiche` ON `an_tipianagrafiche_anagrafiche`.`idtipoanagrafica`=`an_tipianagrafiche`.`id`
    LEFT JOIN `an_tipianagrafiche_lang` ON (`an_tipianagrafiche_lang`.`id_record` = `an_tipianagrafiche`.`id` AND `an_tipianagrafiche_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).")
    LEFT JOIN `in_interventi_tecnici` ON `in_interventi_tecnici`.`idtecnico` = `an_anagrafiche`.`idanagrafica`
    INNER JOIN `in_interventi` ON `in_interventi_tecnici`.`idintervento`=`in_interventi`.`id`
WHERE 
    `an_anagrafiche`.`deleted_at` IS NULL AND `an_tipianagrafiche_lang`.`title`='Tecnico'
GROUP BY 
    `an_anagrafiche`.`idanagrafica`
ORDER BY 
    `ragione_sociale` ASC");

$dataset = '';
if (is_array($_SESSION['superselect']['idtipiintervento'])) {
    $where = '`in_interventi_tecnici`.`id` IN('.implode(',', (array) json_decode((string) $_SESSION['superselect']['idtipiintervento'])).')';
} else {
    $where = '1=1';
}

foreach ($tecnici as $tecnico) {
    $sessioni = $dbo->fetchArray('SELECT SUM(`in_interventi_tecnici`.`ore`) AS result, CONCAT(CAST(SUM(`in_interventi_tecnici`.`ore`) AS char(20)),\' ore\') AS ore_lavorate, YEAR(`in_interventi_tecnici`.`orario_inizio`) AS year, MONTH(`in_interventi_tecnici`.`orario_inizio`) AS month FROM `in_interventi_tecnici` INNER JOIN `in_interventi` ON `in_interventi_tecnici`.`idintervento` = `in_interventi`.`id` LEFT JOIN `in_statiintervento` ON `in_interventi`.`idstatointervento`=`in_statiintervento`.`id` WHERE `in_interventi_tecnici`.`idtecnico` = '.prepare($tecnico['id']).' AND `in_interventi_tecnici`.`orario_inizio` BETWEEN '.prepare($start).' AND '.prepare($end).' AND `in_statiintervento`.`is_completato` AND '.$where.' GROUP BY YEAR(`in_interventi_tecnici`.`orario_inizio`), MONTH(`in_interventi_tecnici`.`orario_inizio`) ORDER BY YEAR(`in_interventi_tecnici`.`orario_inizio`) ASC, MONTH(`in_interventi_tecnici`.`orario_inizio`) ASC');

    $sessioni = Stats::monthly($sessioni, $start, $end);

    // Colore tecnico
    $background = strtoupper((string) $tecnico['colore']);
    if (empty($background) || $background == '#FFFFFF') {
        // Random color
        $background = '#'.dechex(random_int(256, 16777215));
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
<div class="row">
    <div class="col-md-6">
        <div class="card card-info">
            <div class="card-header">
                <h4 class="card-title">'.tr('Ore di lavoro per tecnico').'</h4>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fa fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        {["type": "select", "multiple": "1", "label": "'.tr('Tipi attività').'", "name": "idtipiintervento[]", "ajax-source": "tipiintervento", "value": "'.implode(',', (array) json_decode((string) $_SESSION['superselect']['idtipiintervento'])).'", "placeholder": "Tutti" ]}
                    </div>
                </div>
          
                <canvas id="sessioni"></canvas>
            </div>
        </div>
    </div>';

// Script per il grafico ore interventi per tecnico
echo '
<script>
$(document).ready(function() {
    var translatedMonths = '.json_encode($translated_months).';

    new Chart(document.getElementById("sessioni").getContext("2d"), {
        type: "bar",
        data: {
            labels: translatedMonths,
            datasets: [
                '.($dataset ?: '{ label: "", backgroundColor: "transparent", data: [ 0,0,0,0,0,0,0,0,0,0,0,0 ] }').'
            ]
        },
        options: {
            responsive: true,
            indexAxis: "y",
            scales: {
                x: {
                    ticks: {
                        // Include a dollar sign in the ticks
                        callback: function(value, index, values) {
                            var text = "";
                            if (value<=1 && value!=0){
                                text = " ora";
                            }else{
                                text = " ore";
                            }
                            return value + text;
                        }
                    }
                }
            },
            tooltips: {
                callbacks: {
                    label: function(tooltipItem, data) {
                        var dataset = data.datasets[tooltipItem.datasetIndex];
                        var label = dataset.labels ? dataset.labels[tooltipItem.index] : "";
    
                        if (label) {
                            label += ": ";
                        }

                        label += tooltipItem.xLabel;
                        
                        if (tooltipItem.xLabel<=1) {
                            label += " ora ";
                        }else{
                            label += " ore ";
                        }

                        label += "(in attività completate)";
    
                        return label;
                    }
                }
            },
        }
    });
});
</script>';

$dataset = '';

$nuovi_clienti = $dbo->fetchArray('SELECT 
    COUNT(*) AS result, 
    GROUP_CONCAT(`an_anagrafiche`.`ragione_sociale`, "<br>") AS ragioni_sociali, 
    YEAR(`an_anagrafiche`.`created_at`) AS year, 
    MONTH(`an_anagrafiche`.`created_at`) AS month 
FROM 
    `an_anagrafiche`
    INNER JOIN `an_tipianagrafiche_anagrafiche` ON `an_anagrafiche`.`idanagrafica`=`an_tipianagrafiche_anagrafiche`.`idanagrafica`
    INNER JOIN `an_tipianagrafiche` ON `an_tipianagrafiche_anagrafiche`.`idtipoanagrafica`=`an_tipianagrafiche`.`id`
    LEFT JOIN `an_tipianagrafiche_lang` ON (`an_tipianagrafiche`.`id` = `an_tipianagrafiche_lang`.`id_record` AND `an_tipianagrafiche_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
WHERE 
    `an_tipianagrafiche_lang`.`title` = "Cliente" AND `deleted_at` IS NULL AND `an_anagrafiche`.`created_at` BETWEEN '.prepare($start).' AND '.prepare($end).' GROUP BY YEAR(`an_anagrafiche`.`created_at`), MONTH(`an_anagrafiche`.`created_at`) ORDER BY YEAR(`an_anagrafiche`.`created_at`) ASC, MONTH(`an_anagrafiche`.`created_at`) ASC');

$nuovi_fornitori = $dbo->fetchArray('SELECT 
    COUNT(*) AS result, 
    GROUP_CONCAT(`an_anagrafiche`.`ragione_sociale`, "<br>") AS ragioni_sociali, 
    YEAR(`an_anagrafiche`.`created_at`) AS year, 
    MONTH(`an_anagrafiche`.`created_at`) AS month 
FROM 
    `an_anagrafiche`
    INNER JOIN `an_tipianagrafiche_anagrafiche` ON `an_anagrafiche`.`idanagrafica`=`an_tipianagrafiche_anagrafiche`.`idanagrafica`
    INNER JOIN `an_tipianagrafiche` ON `an_tipianagrafiche_anagrafiche`.`idtipoanagrafica`=`an_tipianagrafiche`.`id`
    LEFT JOIN `an_tipianagrafiche_lang` ON (`an_tipianagrafiche`.`id` = `an_tipianagrafiche_lang`.`id_record` AND `an_tipianagrafiche_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
WHERE 
    `an_tipianagrafiche_lang`.`title` = "Fornitore" AND `deleted_at` IS NULL AND `an_anagrafiche`.`created_at` BETWEEN '.prepare($start).' AND '.prepare($end).' 
GROUP BY 
    YEAR(`an_anagrafiche`.`created_at`), MONTH(`an_anagrafiche`.`created_at`) 
ORDER BY 
    YEAR(`an_anagrafiche`.`created_at`) ASC, MONTH(`an_anagrafiche`.`created_at`) ASC');

// Nuovi clienti per i quali ho emesso almeno una fattura di vendita
$clienti_acquisiti = $dbo->fetchArray('SELECT 
    COUNT(*) AS result, 
    GROUP_CONCAT(`an_anagrafiche`.`ragione_sociale`, "<br>") AS ragioni_sociali, 
    YEAR(`an_anagrafiche`.`created_at`) AS year, 
    MONTH(`an_anagrafiche`.`created_at`) AS month 
FROM 
    `an_anagrafiche`
    INNER JOIN `co_documenti` ON `an_anagrafiche`.`idanagrafica` = `co_documenti`.`idanagrafica`
    INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento`=`co_tipidocumento`.`id`
    INNER JOIN `an_tipianagrafiche_anagrafiche` ON `an_anagrafiche`.`idanagrafica`=`an_tipianagrafiche_anagrafiche`.`idanagrafica`
    INNER JOIN `an_tipianagrafiche` ON `an_tipianagrafiche_anagrafiche`.`idtipoanagrafica`=`an_tipianagrafiche`.`id`
    LEFT JOIN `an_tipianagrafiche_lang` ON (`an_tipianagrafiche`.`id` = `an_tipianagrafiche_lang`.`id_record` AND `an_tipianagrafiche_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
WHERE 
    `an_tipianagrafiche_lang`.`title` = "Cliente" AND 
    `co_tipidocumento`.`dir` = "entrata" AND 
    `an_anagrafiche`.`created_at` BETWEEN '.prepare($start).' AND '.prepare($end).' 
GROUP BY 
    YEAR(`an_anagrafiche`.`created_at`), MONTH(`an_anagrafiche`.`created_at`) 
ORDER BY 
    YEAR(`an_anagrafiche`.`created_at`) ASC, MONTH(`an_anagrafiche`.`created_at`) ASC');

// Random color
$background = '#'.dechex(random_int(256, 16777215));

$dataset .= '{
    label: "'.tr('Nuovi clienti').'",   
    backgroundColor: "'.$background.'",
    data: [
        '.implode(',', array_column($nuovi_clienti, 'result')).'
    ]
},';

// Random color
$background = '#'.dechex(random_int(256, 16777215));

$dataset .= '{
    label: "'.tr('Clienti acquisiti').'",   
    backgroundColor: "'.$background.'",
    data: [
        '.implode(',', array_column($clienti_acquisiti, 'result')).'
    ]
},';

// Random color
$background = '#'.dechex(random_int(256, 16777215));

$dataset .= '{
    label: "'.tr('Nuovi fornitori').'",   
    backgroundColor: "'.$background.'",
    data: [
        '.implode(',', array_column($nuovi_fornitori, 'result')).'
    ]
},';
echo '

    <div class="col-md-6">
        <div class="card card-info">
            <div class="card-header">
                <h4 class="card-title">'.tr('Nuove anagrafiche').'</h4>

                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fa fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="n_anagrafiche"></canvas>
            </div>
        </div>
    </div>
</div>';

// Script per il grafico dei nuovi clienti per mese
echo '
<script>
$(document).ready(function() {
    var translatedMonths = '.json_encode($translated_months).';

    new Chart(document.getElementById("n_anagrafiche").getContext("2d"), {
        type: "line",
        data: {
            labels: translatedMonths,
            datasets: [
                '.$dataset.'
            ]
        },
        options: {
            responsive: true,
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
                x: {
                    display: true,
                    scaleLabel: {
                        display: true,
                        labelString: "'.tr('Periodo').'"
                    }
                },
                y: {
                    display: true,
                    scaleLabel: {
                        display: true,
                        labelString: "'.tr('Numero').'"
                    },
                    ticks: {
                        callback: function(value, index, values) {
                            return value;
                        }
                    }
                }
            },
        }
    });
});
</script>


<script type="text/javascript">
$(".shorten").shorten({
    moreText: "'.tr('Mostra tutto').'",
    lessText: "'.tr('Comprimi').'",
    showChars : 70
});

$("#idtipiintervento").change(function(){
    let idtipi = JSON.stringify($(this).val());
    session_set("superselect,idtipiintervento",idtipi,0);
    location.reload();
});
</script>';
