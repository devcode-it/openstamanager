<?php

include_once __DIR__.'/../../core.php';

echo '
<script src="'.$rootdir.'/assets/dist/js/chartjs/Chart.min.js"></script>';

$start = $_SESSION['period_start'];
$end = $_SESSION['period_end'];

echo '
<h3 class="text-center">
    <span class="label label-primary">'.tr('Periodo dal _START_ al _END_', [
        '_START_' => Translator::dateToLocale($start),
        '_END_' => Translator::dateToLocale($end),
    ]).'</span>
</h3>
<hr>

<script>
$(document).ready(function() {
    start = moment("'.$start.'");
    end = moment("'.$end.'");

    months = [];
    while(start.isSameOrBefore(end, "month")){
        string = start.format("MMMM YYYY");

        months.push(string.charAt(0).toUpperCase() + string.slice(1));

        start.add(1, "months");
    }
});
</script>';

// Differenza delle date in mesi
$d1 = new DateTime($start);
$d2 = new DateTime($end);
$count = $d1->diff($d2)->m + ($d1->diff($d2)->y * 12) + 1;

$fatturato = $dbo->fetchArray("SELECT SUM(subtotale - sconto) AS totale, YEAR(co_documenti.data) AS year, MONTH(co_documenti.data) AS month FROM co_documenti INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id INNER JOIN co_righe_documenti ON co_righe_documenti.iddocumento=co_documenti.id WHERE co_tipidocumento.dir='entrata' AND co_tipidocumento.descrizione!='Bozza' AND co_documenti.data BETWEEN ".prepare($start).' AND '.prepare($end).' GROUP BY YEAR(co_documenti.data), MONTH(co_documenti.data) ORDER BY YEAR(co_documenti.data) ASC, MONTH(co_documenti.data) ASC');

$entrate = $dbo->fetchArray("SELECT SUM(subtotale - sconto) AS totale, YEAR(co_documenti.data) AS year, MONTH(co_documenti.data) AS month FROM co_documenti INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id INNER JOIN co_righe_documenti ON co_righe_documenti.iddocumento=co_documenti.id INNER JOIN co_movimenti ON co_movimenti.iddocumento=co_documenti.id AND primanota=1 WHERE co_tipidocumento.dir='entrata' AND co_documenti.data BETWEEN ".prepare($start).' AND '.prepare($end).' GROUP BY YEAR(co_documenti.data), MONTH(co_documenti.data) ORDER BY YEAR(co_documenti.data) ASC, MONTH(co_documenti.data) ASC');

$uscite = $dbo->fetchArray("SELECT SUM(subtotale - sconto) AS totale, YEAR(co_documenti.data) AS year, MONTH(co_documenti.data) AS month FROM co_documenti INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id INNER JOIN co_righe_documenti ON co_righe_documenti.iddocumento=co_documenti.id INNER JOIN co_movimenti ON co_movimenti.iddocumento=co_documenti.id AND primanota=1 WHERE co_tipidocumento.dir='uscita' AND co_documenti.data BETWEEN ".prepare($start).' AND '.prepare($end).' GROUP BY YEAR(co_documenti.data), MONTH(co_documenti.data) ORDER BY YEAR(co_documenti.data) ASC, MONTH(co_documenti.data) ASC');

$month = intval($d1->format('m')) - 1;
for ($i = 0; $i < $count; ++$i) {
    $month = $month % 12;

    if (intval($fatturato[$i]['month']) != $month + 1) {
        array_splice($fatturato, $i, 0, [[
            'totale' => 0,
        ]]);
    }

    if (intval($entrate[$i]['month']) != $month + 1) {
        array_splice($entrate, $i, 0, [[
            'totale' => 0,
        ]]);
    }

    if (intval($uscite[$i]['month']) != $month + 1) {
        array_splice($uscite, $i, 0, [[
            'totale' => 0,
        ]]);
    }

    ++$month;
}

// Fatturato
echo '
<div class="box box-success">
    <div class="box-header with-border">
        <h3 class="box-title">'.tr('Fatturato').'</h3>

        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse">
                <i class="fa fa-minus"></i>
            </button>
        </div>
    </div>
    <canvas class="box-body collapse in" id="fatturato"></canvas>
</div>';

// Script per il grafico del fatturato
echo '
<script>
$(document).ready(function() {
    new Chart(document.getElementById("fatturato").getContext("2d"), {
        type: "bar",
        data: {
            labels: months,
            datasets: [
                {
                    label: "'.tr('Fatturato').'",
                    backgroundColor: "yellow",
                    data: [
                        '.implode(',', array_column($fatturato, 'totale')).'
                    ]
                },
                {
                    label: "'.tr('Entrate').'",
                    backgroundColor: "green",
                    data: [
                        '.implode(',', array_column($entrate, 'totale')).'
                    ]
                },
                {
                    label: "'.tr('Uscite').'",
                    backgroundColor: "red",
                    data: [
                        '.implode(',', array_column($uscite, 'totale')).'
                    ]
                }
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

// Clienti top
$clienti = $dbo->fetchArray("SELECT SUM(subtotale - sconto) AS totale, (SELECT COUNT(*) FROM co_documenti WHERE co_documenti.idanagrafica =an_anagrafiche.idanagrafica) AS qta, an_anagrafiche.idanagrafica, an_anagrafiche.ragione_sociale FROM co_documenti INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id INNER JOIN co_righe_documenti ON co_righe_documenti.iddocumento=co_documenti.id INNER JOIN an_anagrafiche ON an_anagrafiche.idanagrafica=co_documenti.idanagrafica WHERE co_tipidocumento.dir='entrata' AND co_documenti.data BETWEEN ".prepare($start).' AND '.prepare($end).' GROUP BY an_anagrafiche.idanagrafica ORDER BY SUM(subtotale - sconto) DESC LIMIT 15');

$totale = $dbo->fetchArray("SELECT SUM(subtotale - sconto) AS totale FROM co_documenti INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id INNER JOIN co_righe_documenti ON co_righe_documenti.iddocumento=co_documenti.id WHERE co_tipidocumento.dir='entrata' AND co_documenti.data BETWEEN ".prepare($start).' AND '.prepare($end));

echo '
<div class="row">
    <div class="col-xs-12 col-md-6">
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title">'.tr('Clienti TOP').'</h3>

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
                        <th class="text-right">'.tr('Totale').'</th>
                        <th class="text-right">'.tr('Percentuale').'</th>
                    </tr>';
    foreach ($clienti as $cliente) {
        echo '
                    <tr>
                        <td>'.Modules::link('Anagrafiche', $cliente['idanagrafica'], $cliente['ragione_sociale']).'</td>
                        <td class="text-center">'.intval($cliente['qta']).'</td>
                        <td class="text-right">'.Translator::numberToLocale($cliente['totale']).' &euro;</td>
                        <td class="text-right">'.Translator::numberToLocale($cliente['totale'] * 100 / $totale[0]['totale']).' %</td>
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
    </div>';

// Articoli più venduti
$articoli = $dbo->fetchArray("SELECT SUM(co_righe_documenti.qta) AS qta, mg_articoli.id, mg_articoli.codice, mg_articoli.descrizione, mg_articoli.um FROM co_documenti INNER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id INNER JOIN co_righe_documenti ON co_righe_documenti.iddocumento=co_documenti.id INNER JOIN mg_articoli ON mg_articoli.id=co_righe_documenti.idarticolo WHERE co_tipidocumento.dir='entrata' AND co_documenti.data BETWEEN ".prepare($start).' AND '.prepare($end).' GROUP BY co_righe_documenti.idarticolo ORDER BY SUM(co_righe_documenti.qta) DESC LIMIT 15');

echo '
    <div class="col-xs-12 col-md-6">
        <div class="box box-danger">
            <div class="box-header with-border">
                <h3 class="box-title">'.tr('Articoli più venduti').'</h3>

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
                        <th>'.tr('Codice').'</th>
                        <th>'.tr('Descrizione').'</th>
                        <th class="text-right">'.tr('Q.tà').'</th>
                    </tr>';
    foreach ($articoli as $articolo) {
        echo '
                    <tr>
                        <td>'.Modules::link('Articoli', $articolo['id'], $articolo['codice']).'</td>
                        <td>'.$articolo['descrizione'].'</td>
                        <td class="text-right">'.Translator::numberToLocale($articolo['qta']).' '.$articolo['um'].'</td>
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

// Interventi per stato
$stati = $dbo->fetchArray('SELECT * FROM `in_statiintervento`');

$dataset = '';
foreach ($stati as $stato) {
    $interventi = $dbo->fetchArray('SELECT COUNT(*) AS totale, YEAR(in_interventi.data_richiesta) AS year, MONTH(in_interventi.data_richiesta) AS month FROM in_interventi WHERE in_interventi.idstatointervento = '.prepare($stato['idstatointervento']).' AND in_interventi.data_richiesta BETWEEN '.prepare($start).' AND '.prepare($end).' GROUP BY YEAR(in_interventi.data_richiesta), MONTH(in_interventi.data_richiesta) ORDER BY YEAR(in_interventi.data_richiesta) ASC, MONTH(in_interventi.data_richiesta) ASC');

    $month = intval($d1->format('m')) - 1;
    for ($i = 0; $i < $count; ++$i) {
        $month = $month % 12;

        if (intval($interventi[$i]['month']) != $month + 1) {
            array_splice($interventi, $i, 0, [[
                'totale' => 0,
            ]]);
        }

        ++$month;
    }

    $dataset .= '{
        label: "'.$stato['descrizione'].'",
        backgroundColor: "'.$stato['colore'].'",
        data: [
            '.implode(',', array_column($interventi, 'totale')).'
        ]
    },';
}

echo '
<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">'.tr('Interventi per stato').'</h3>

        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse">
                <i class="fa fa-minus"></i>
            </button>
        </div>
    </div>
    <canvas class="box-body collapse in" id="interventi"></canvas>
</div>';

// Script per il grafico del fatturato
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
