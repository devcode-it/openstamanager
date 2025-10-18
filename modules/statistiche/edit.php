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
<script src="'.base_path_osm().'/assets/dist/js/chartjs/chart.min.js"></script>
<script src="'.$structure->fileurl('js/functions.js').'"></script>
<script src="'.$structure->fileurl('js/calendar.js').'"></script>
<script src="'.$structure->fileurl('js/manager.js').'"></script>
<script src="'.$structure->fileurl('js/stat.js').'"></script>
<script src="'.$structure->fileurl('js/stats/line_chart.js').'"></script>';

$start = $_SESSION['period_start'];
$end = $_SESSION['period_end'];

$translated_months = [tr('Gennaio'), tr('Febbraio'), tr('Marzo'), tr('Aprile'), tr('Maggio'), tr('Giugno'), tr('Luglio'), tr('Agosto'), tr('Settembre'), tr('Ottobre'), tr('Novembre'), tr('Dicembre')];

$months = [];
$start_date = new DateTime($start);
$end_date = new DateTime($end);

while ($start_date <= $end_date) {
    $month_number = $start_date->format('n');  // Ottiene il numero del mese (1-12)
    if (!in_array($month_number, $months)) {  // Aggiunge il mese solo se non gia' presente
        $months[] = $month_number;
    }
    $start_date->modify('+1 month');  // Avanza al mese successivo
}
// Sostituisce i numeri con i nomi tradotti
foreach ($months as $key => $month_number) {
    $months[$key] = $translated_months[$month_number - 1];  // Traduce il mese
}

// Fatturato
echo '
<div class="card card-outline card-info">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="card-title">'.tr('Vendite e acquisti').'</h3>
                <span class="badge badge-info ml-2">'.Translator::dateToLocale($start).' - '.Translator::dateToLocale($end).'</span>
            </div>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fa fa-minus"></i>
                </button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="btn-group float-right">
                    <button class="btn btn-sm btn-outline-info" onclick="add_calendar()">
                        <i class="fa fa-calendar"></i> '.tr('Aggiungi periodo di confronto').'
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" data-toggle="collapse" data-target="#calendars-container">
                        <i class="fa fa-cog"></i> '.tr('Gestisci periodi').'
                    </button>
                </div>
            </div>
        </div>

        <div id="calendars-container" class="collapse">
            <div class="card card-light mb-3">
                <div class="card-body" id="calendars">
                    <div class="row">
                        <div class="col-md-12">
                            <small class="text-muted">'.tr('Aggiungi periodi temporali per confrontare i dati').'</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="chart-container" style="position: relative; height:300px;">
            <canvas id="fatturato"></canvas>
        </div>
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
        maintainAspectRatio: false,
        elements: {
            line: {
                tension: 0.3,
                borderWidth: 2
            },
            point: {
                radius: 3,
                hitRadius: 10,
                hoverRadius: 5
            }
        },
        plugins: {
            legend: {
                position: "top",
                labels: {
                    boxWidth: 12,
                    padding: 10
                }
            },
            tooltip: {
                mode: "index",
                intersect: false,
                backgroundColor: "rgba(0,0,0,0.7)",
                titleFont: {
                    size: 12
                },
                bodyFont: {
                    size: 12
                },
                callbacks: {
                    label: function(context) {
                        var dataset = context.dataset;
                        var label = dataset.labels ? dataset.labels[context.dataIndex] : dataset.label || "";

                        if (label) {
                            label += ": ";
                        }

                        label += \''.html_entity_decode(currency()).' \' + (context.raw || 0).toLocaleString();

                        return label;
                    }
                }
            }
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
                },
                grid: {
                    color: "rgba(0,0,0,0.05)"
                }
            },
            y: {
                display: true,
                title: {
                    display: true,
                    text: "'.tr('Andamento').'"
                },
                grid: {
                    color: "rgba(0,0,0,0.05)"
                },
                ticks: {
                    callback: function(value) {
                        return \''.html_entity_decode(currency()).' \' + value.toLocaleString();
                    }
                }
            }
        }
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
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title">'.tr('I 20 clienti TOP').'</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fa fa-minus"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">';
if (!empty($clienti)) {
    echo '
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>'.tr('Ragione sociale').'</th>
                                <th class="text-right" width="100">'.tr('N. fatture').'</th>
                                <th class="text-right" width="120">'.tr('Totale').' <i class="fa fa-info-circle text-info tip" title="'.tr('Valori iva esclusa').'"></i></th>
                                <th class="text-right" width="120">'.tr('Percentuale').' <i class="fa fa-info-circle text-info tip" title="'.tr('Incidenza sul fatturato').'"></i></th>
                            </tr>
                        </thead>
                        <tbody>';
    foreach ($clienti as $cliente) {
        echo '
                            <tr>
                                <td>'.Modules::link('Anagrafiche', $cliente['idanagrafica'], $cliente['ragione_sociale']).'</td>
                                <td class="text-right">'.intval($cliente['qta']).'</td>
                                <td class="text-right">'.moneyFormat($cliente['totale'], 2).'</td>
                                <td class="text-right">
                                    <span class="badge badge-'.($cliente['totale'] * 100 / ($totale[0]['totale'] != 0 ? $totale[0]['totale'] : 1) > 10 ? 'info' : 'secondary').'">
                                        '.Translator::numberToLocale($cliente['totale'] * 100 / ($totale[0]['totale'] != 0 ? $totale[0]['totale'] : 1), 2).' %
                                    </span>
                                </td>
                            </tr>';
    }
    echo '
                        </tbody>
                    </table>';
} else {
    echo '
                    <div class="alert alert-info m-3">
                        <i class="fa fa-info-circle"></i> '.tr('Nessuna vendita').'...
                    </div>';
}
echo '
                </div>
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
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title">'.tr('I 20 articoli più venduti').'</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fa fa-minus"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">';
if (!empty($articoli)) {
    echo '
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>'.tr('Articolo').'</th>
                                <th class="text-right" width="100">'.tr('N. articoli').' <i class="fa fa-info-circle text-info tip" title="'.tr('Numero di articoli venduti').'"></i></th>
                                <th class="text-right" width="120">'.tr('Percentuale').' <i class="fa fa-info-circle text-info tip" title="'.tr('Incidenza sul numero di articoli').'"></i></th>
                                <th class="text-right" width="120">'.tr('Totale').' <i class="fa fa-info-circle text-info tip" title="'.tr('Valori iva esclusa').'"></i></th>
                            </tr>
                        </thead>
                        <tbody>';
    foreach ($articoli as $articolo) {
        echo '
                            <tr>
                                <td><div class="text-truncate" style="max-width: 250px;"> '.Modules::link('Articoli', $articolo['id'], $articolo['codice'].' - '.$articolo['descrizione']).'</div></td>
                                <td class="text-right">'.Translator::numberToLocale($articolo['qta'], 'qta').' '.$articolo['um'].'</td>
                                <td class="text-right">
                                    <span class="badge badge-'.($articolo['qta'] * 100 / ($totale[0]['totale_qta'] != 0 ? $totale[0]['totale_qta'] : 1) > 10 ? 'info' : 'secondary').'">
                                        '.Translator::numberToLocale($articolo['qta'] * 100 / ($totale[0]['totale_qta'] != 0 ? $totale[0]['totale_qta'] : 1), 2).' %
                                    </span>
                                </td>
                                <td class="text-right font-weight-bold">'.moneyFormat($articolo['totale'], 2).'</td>
                            </tr>';
    }
    echo '
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-between align-items-center p-3 bg-light border-top">
                        <div>
                            <span class="text-muted"><small>'.tr('Periodo').': '.Translator::dateToLocale($start).' - '.Translator::dateToLocale($end).'</small></span>
                        </div>
                        <div>
                            '.Modules::link('Articoli', null, '<i class="fa fa-chart-bar"></i> '.tr('Statistiche complete'), 'btn btn-info btn-sm', null, false, 'tab_'.Plugin::where('name', 'Statistiche vendita')->first()->id).'
                        </div>
                    </div>';
} else {
    echo '
                    <div class="alert alert-info m-3">
                        <i class="fa fa-info-circle"></i> '.tr('Nessun articolo venduto').'...
                    </div>';
}
echo '
                </div>
            </div>
        </div>
    </div>
</div>';

// Fornitori top
$fornitori = $dbo->fetchArray('SELECT
        SUM(IF(`reversed`=1, - (`co_righe_documenti`.`subtotale` - `co_righe_documenti`.`sconto`), (`co_righe_documenti`.`subtotale` - `co_righe_documenti`.`sconto`))) AS totale,
        (SELECT
            COUNT(*)
        FROM
            `co_documenti`
            INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento`=`co_tipidocumento`.`id`
            INNER JOIN `zz_segments` ON `co_documenti`.`id_segment`=`zz_segments`.`id`
        WHERE
            `co_documenti`.`idanagrafica` = `an_anagrafiche`.`idanagrafica` AND `co_documenti`.`data` BETWEEN '.prepare($start).' AND '.prepare($end)." AND `co_tipidocumento`.`dir`='uscita' AND `zz_segments`.`autofatture`=0) AS qta,
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
        `co_tipidocumento`.`dir`='uscita'
        AND `co_statidocumento_lang`.`title` IN('Pagato', 'Parzialmente pagato', 'Emessa')
        AND `co_documenti`.`data` BETWEEN ".prepare($start).' AND '.prepare($end).'
        AND `zz_segments`.`autofatture`=0
    GROUP BY
        `an_anagrafiche`.`idanagrafica`
    ORDER BY
        `totale` DESC LIMIT 20');

$totale_fornitori = $dbo->fetchArray('SELECT
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
        AND `co_tipidocumento`.`dir`='uscita'
        AND `co_documenti`.`data` BETWEEN ".prepare($start).' AND '.prepare($end).'
        AND `zz_segments`.`autofatture`=0');

echo '
<div class="row">
    <div class="col-md-6">
        <div class="card card-info">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title">'.tr('I 20 fornitori TOP').'</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fa fa-minus"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">';
if (!empty($fornitori)) {
    echo '
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>'.tr('Ragione sociale').'</th>
                                <th class="text-right" width="100">'.tr('N. fatture').'</th>
                                <th class="text-right" width="120">'.tr('Totale').' <i class="fa fa-info-circle text-info tip" title="'.tr('Valori iva esclusa').'"></i></th>
                                <th class="text-right" width="120">'.tr('Percentuale').' <i class="fa fa-info-circle text-info tip" title="'.tr('Incidenza sugli acquisti').'"></i></th>
                            </tr>
                        </thead>
                        <tbody>';
    foreach ($fornitori as $fornitore) {
        echo '
                            <tr>
                                <td>'.Modules::link('Anagrafiche', $fornitore['idanagrafica'], $fornitore['ragione_sociale']).'</td>
                                <td class="text-right">'.intval($fornitore['qta']).'</td>
                                <td class="text-right">'.moneyFormat($fornitore['totale'], 2).'</td>
                                <td class="text-right">
                                    <span class="badge badge-'.($fornitore['totale'] * 100 / ($totale_fornitori[0]['totale'] != 0 ? $totale_fornitori[0]['totale'] : 1) > 10 ? 'info' : 'secondary').'">
                                        '.Translator::numberToLocale($fornitore['totale'] * 100 / ($totale_fornitori[0]['totale'] != 0 ? $totale_fornitori[0]['totale'] : 1), 2).' %
                                    </span>
                                </td>
                            </tr>';
    }
    echo '
                        </tbody>
                    </table>';
} else {
    echo '
                    <div class="alert alert-info m-3">
                        <i class="fa fa-info-circle"></i> '.tr('Nessun acquisto').'...
                    </div>';
}
echo '
                </div>
            </div>
        </div>
    </div>';

// Articoli più acquistati
$articoli_acquistati = $dbo->fetchArray('SELECT
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
        `co_tipidocumento`.`dir`='uscita'
        AND `co_statidocumento_lang`.`title` IN ('Pagato', 'Parzialmente pagato', 'Emessa')
        AND `co_documenti`.`data` BETWEEN ".prepare($start).' AND '.prepare($end).'
        AND `zz_segments`.`autofatture`=0
    GROUP BY
        `co_righe_documenti`.`idarticolo`
    ORDER BY
        `qta` DESC LIMIT 20');

$totale_acquistati = $dbo->fetchArray('SELECT
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
        `co_tipidocumento`.`dir`='uscita'
        AND `co_statidocumento_lang`.`title` IN ('Pagato', 'Parzialmente pagato', 'Emessa')
        AND `co_documenti`.`data` BETWEEN ".prepare($start).' AND '.prepare($end).'
        AND `zz_segments`.`autofatture`=0');

echo '
    <div class="col-md-6">
        <div class="card card-info">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title">'.tr('I 20 articoli più acquistati').'</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fa fa-minus"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">';
if (!empty($articoli_acquistati)) {
    echo '
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>'.tr('Articolo').'</th>
                                <th class="text-right" width="100">'.tr('N. articoli').' <i class="fa fa-info-circle text-info tip" title="'.tr('Numero di articoli acquistati').'"></i></th>
                                <th class="text-right" width="120">'.tr('Percentuale').' <i class="fa fa-info-circle text-info tip" title="'.tr('Incidenza sul numero di articoli').'"></i></th>
                                <th class="text-right" width="120">'.tr('Totale').' <i class="fa fa-info-circle text-info tip" title="'.tr('Valori iva esclusa').'"></i></th>
                            </tr>
                        </thead>
                        <tbody>';
    foreach ($articoli_acquistati as $articolo) {
        echo '
                            <tr>
                                <td><div class="text-truncate" style="max-width: 250px;"> '.Modules::link('Articoli', $articolo['id'], $articolo['codice'].' - '.$articolo['descrizione']).'</div></td>
                                <td class="text-right">'.Translator::numberToLocale($articolo['qta'], 'qta').' '.$articolo['um'].'</td>
                                <td class="text-right">
                                    <span class="badge badge-'.($articolo['qta'] * 100 / ($totale_acquistati[0]['totale_qta'] != 0 ? $totale_acquistati[0]['totale_qta'] : 1) > 10 ? 'info' : 'secondary').'">
                                        '.Translator::numberToLocale($articolo['qta'] * 100 / ($totale_acquistati[0]['totale_qta'] != 0 ? $totale_acquistati[0]['totale_qta'] : 1), 2).' %
                                    </span>
                                </td>
                                <td class="text-right font-weight-bold">'.moneyFormat($articolo['totale'], 2).'</td>
                            </tr>';
    }
    echo '
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-between align-items-center p-3 bg-light border-top">
                        <div>
                            <span class="text-muted"><small>'.tr('Periodo').': '.Translator::dateToLocale($start).' - '.Translator::dateToLocale($end).'</small></span>
                        </div>
                        <div>
                            '.Modules::link('Articoli', null, '<i class="fa fa-chart-bar"></i> '.tr('Statistiche complete'), 'btn btn-info btn-sm', null, false, 'tab_'.Plugin::where('name', 'Statistiche vendita')->first()->id).'
                        </div>
                    </div>';
} else {
    echo '
                    <div class="alert alert-info m-3">
                        <i class="fa fa-info-circle"></i> '.tr('Nessun articolo acquistato').'...
                    </div>';
}
echo '
                </div>
            </div>
        </div>
    </div>
</div>';

// Numero interventi per tipologia
$tipi = $dbo->fetchArray('SELECT *, in_tipiintervento.id AS idtipointervento FROM `in_tipiintervento` LEFT JOIN `in_tipiintervento_lang` ON (`in_tipiintervento`.`id` = `in_tipiintervento_lang`.`id_record` AND `in_tipiintervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')');

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
        `in_interventi`.`idtipointervento` = '.prepare($tipo['idtipointervento']).'
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
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title">'.tr('Numero interventi per tipologia').'</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fa fa-minus"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-container" style="position: relative; height:300px;">
                    <canvas id="interventi_n_tipologia"></canvas>
                </div>
            </div>
        </div>
    </div>';

// Script for the chart displaying the number of interventions by type
echo '
<script>
$(document).ready(function() {
    var translatedMonths = '.json_encode($months).';

    new Chart(document.getElementById("interventi_n_tipologia").getContext("2d"), {
        type: "bar",
        data: {
            labels: translatedMonths,
            datasets: [
                '.$dataset.'
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            tooltips: {
                mode: "index",
                intersect: false,
                backgroundColor: "rgba(0,0,0,0.7)",
            },
            legend: {
                position: "top",
                labels: {
                    boxWidth: 12,
                    padding: 8
                }
            },
            scales: {
                x: {
                    grid: {
                        color: "rgba(0,0,0,0.05)"
                    }
                },
                y: {
                    grid: {
                        color: "rgba(0,0,0,0.05)"
                    },
                    beginAtZero: true
                }
            }
        }
    });
});
</script>';

// Ore interventi per tipologia
$dataset = '';
foreach ($tipi as $tipo) {
    $interventi = $dbo->fetchArray('SELECT ROUND(SUM(in_interventi_tecnici.ore), 2) AS result, YEAR(in_interventi_tecnici.orario_fine) AS year, MONTH(in_interventi_tecnici.orario_fine) AS month FROM in_interventi INNER JOIN in_interventi_tecnici ON in_interventi.id=in_interventi_tecnici.idintervento WHERE in_interventi.idtipointervento = '.prepare($tipo['idtipointervento']).' AND in_interventi.data_richiesta BETWEEN '.prepare($start).' AND '.prepare($end).' AND in_interventi_tecnici.orario_fine BETWEEN '.prepare($start).' AND '.prepare($end).' GROUP BY YEAR(in_interventi_tecnici.orario_fine), MONTH(in_interventi_tecnici.orario_fine) ORDER BY YEAR(in_interventi_tecnici.orario_fine) ASC, MONTH(in_interventi_tecnici.orario_fine) ASC');

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
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title">'.tr('Ore interventi per tipologia').'</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fa fa-minus"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-container" style="position: relative; height:300px;">
                    <canvas id="interventi_ore_tipologia"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>';

// Script per il grafico delle ore interventi per tipologia
echo '
<script>
$(document).ready(function() {
    var translatedMonths = '.json_encode($months).';
    new Chart(document.getElementById("interventi_ore_tipologia").getContext("2d"), {
        type: "bar",
        data: {
            labels: translatedMonths,
            datasets: [
                '.$dataset.'
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            tooltips: {
                mode: "index",
                intersect: false,
                backgroundColor: "rgba(0,0,0,0.7)",
            },
            legend: {
                position: "top",
                labels: {
                    boxWidth: 12,
                    padding: 8
                }
            },
            scales: {
                x: {
                    grid: {
                        color: "rgba(0,0,0,0.05)"
                    }
                },
                y: {
                    grid: {
                        color: "rgba(0,0,0,0.05)"
                    },
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value + " ore";
                        }
                    }
                }
            }
        }
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
$where = ($_SESSION['superselect']['idtipiintervento'] && $_SESSION['superselect']['idtipiintervento'] != '[]') ?
    '`in_interventi_tecnici`.`idtipointervento` IN('.implode(',', (array) json_decode((string) $_SESSION['superselect']['idtipiintervento'])).')' :
    '1=1';

foreach ($tecnici as $tecnico) {
    $sessioni = $dbo->fetchArray('SELECT SUM(`in_interventi_tecnici`.`ore`) AS result, CONCAT(CAST(SUM(`in_interventi_tecnici`.`ore`) AS char(20)),\' ore\') AS ore_lavorate, YEAR(`in_interventi_tecnici`.`orario_inizio`) AS year, MONTH(`in_interventi_tecnici`.`orario_inizio`) AS month FROM `in_interventi_tecnici` INNER JOIN `in_interventi` ON `in_interventi_tecnici`.`idintervento` = `in_interventi`.`id` LEFT JOIN `in_statiintervento` ON `in_interventi`.`idstatointervento`=`in_statiintervento`.`id` WHERE `in_interventi_tecnici`.`idtecnico` = '.prepare($tecnico['id']).' AND `in_interventi_tecnici`.`orario_inizio` BETWEEN '.prepare($start).' AND '.prepare($end).' AND `in_statiintervento`.`is_bloccato` AND '.$where.' GROUP BY YEAR(`in_interventi_tecnici`.`orario_inizio`), MONTH(`in_interventi_tecnici`.`orario_inizio`) ORDER BY YEAR(`in_interventi_tecnici`.`orario_inizio`) ASC, MONTH(`in_interventi_tecnici`.`orario_inizio`) ASC');

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
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title">'.tr('Ore di lavoro per tecnico').'</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fa fa-minus"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-container" style="position: relative; height:300px;">
                    <canvas id="sessioni"></canvas>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        {["type": "select", "multiple": "1", "label": "'.tr('Filtra per tipi attività').'", "name": "idtipiintervento[]", "ajax-source": "tipiintervento", "value": "'.implode(',', (array) json_decode((string) $_SESSION['superselect']['idtipiintervento'])).'", "placeholder": "Tutti", "icon-before": "<i class=\"fa fa-filter\"></i>" ]}
                    </div>
                </div>
            </div>
        </div>
    </div>';

// Script per il grafico ore interventi per tecnico
echo '
<script>
$(document).ready(function() {
    var translatedMonths = '.json_encode($months).';

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
            maintainAspectRatio: false,
            indexAxis: "y",
            plugins: {
                legend: {
                    position: "right",
                    labels: {
                        boxWidth: 12,
                        padding: 8
                    }
                },
                tooltip: {
                    backgroundColor: "rgba(0,0,0,0.7)",
                    callbacks: {
                        label: function(context) {
                            var label = context.dataset.label || "";
                            if (label) {
                                label += ": ";
                            }

                            var value = context.raw || 0;
                            label += value;

                            if (value <= 1 && value != 0) {
                                label += " ora ";
                            } else {
                                label += " ore ";
                            }

                            label += "(in attività completate)";

                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        color: "rgba(0,0,0,0.05)"
                    },
                    ticks: {
                        callback: function(value) {
                            return value + (value <= 1 && value != 0 ? " ora" : " ore");
                        }
                    }
                },
                y: {
                    grid: {
                        color: "rgba(0,0,0,0.05)"
                    }
                }
            }
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
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title">'.tr('Nuove anagrafiche').'</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fa fa-minus"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-container" style="position: relative; height:300px;">
                    <canvas id="n_anagrafiche"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>';

// Script per il grafico dei nuovi clienti per mese
echo '
<script>
$(document).ready(function() {
    var translatedMonths = '.json_encode($months).';

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
            maintainAspectRatio: false,
            elements: {
                line: {
                    tension: 0.3,
                    borderWidth: 2
                },
                point: {
                    radius: 3,
                    hitRadius: 10,
                    hoverRadius: 5
                }
            },
            plugins: {
                legend: {
                    position: "top",
                    labels: {
                        boxWidth: 12,
                        padding: 8
                    }
                },
                tooltip: {
                    mode: "index",
                    intersect: false,
                    backgroundColor: "rgba(0,0,0,0.7)"
                }
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
                    },
                    grid: {
                        color: "rgba(0,0,0,0.05)"
                    }
                },
                y: {
                    display: true,
                    title: {
                        display: true,
                        text: "'.tr('Numero').'"
                    },
                    grid: {
                        color: "rgba(0,0,0,0.05)"
                    },
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
});
</script>


<script type="text/javascript">
// Inizializzazione automatica tramite text-shortener.js

$("#idtipiintervento").change(function(){
    let tipi = "";
    if( $(this).val() ){
        idtipi = JSON.stringify($(this).val());
    }

    session_set("superselect,idtipiintervento",idtipi,0);
    location.reload();
});
</script>';
