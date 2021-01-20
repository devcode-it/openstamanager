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

use Carbon\Carbon;

$calendar = $_SESSION['dashboard'];

$date = $calendar['date'];
$date = new Carbon($date);

if ($calendar['format'] == 'week') {
    $period = explode(' ', $_SESSION['dashboard']['week']);

    $day = $period[0];

    if (count($period) == 5) {
        $maxmonth = $period[3];
        $maxday_ = $period[2];

        $month = $period[3];
        $year = $period[4];
    }

    if (count($period) == 6) {
        $maxmonth = $period[1];
        $maxday_ = $period[3];

        $month = $period[4];
        $year = $period[5];
    }

    $months = ['gen' => '01', 'feb' => '02', 'mar' => '03', 'apr' => '04', 'mag' => '05', 'giu' => '06', 'lug' => '07', 'ago' => '08', 'set' => '09', 'ott' => '10', 'nov' => '11', 'dic' => '12'];
    $month = $months[$month];
    $maxmonth = $months[$maxmonth];

    $title = $_SESSION['dashboard']['week'];

    //numero di giorni nel mese
    $maxday = cal_days_in_month(CAL_GREGORIAN, $month, $year) + 1;

    $min_date = $year.'-'.$month.'-'.$day;
    $max_date = $year.'-'.$maxmonth.'-'.$maxday_;

    //aggiungo un giorno
    $max_date = date('Y-m-d', date(strtotime('+1 day', strtotime($max_date))));

    $where = '  (in_interventi_tecnici.orario_inizio) <= '.prepare($max_date).' AND  (in_interventi_tecnici.orario_inizio) >= '.prepare($min_date).' AND ';
} else {
    $title = $date->formatLocalized('%B %Y');

    $min_date = $date->copy()->startOfMonth();
    $max_date = $date->copy()->endOfMonth();

    $where = ' (in_interventi_tecnici.orario_inizio) <= '.prepare($max_date).' AND  (in_interventi_tecnici.orario_inizio) >= '.prepare($min_date).' AND ';
}

$height = '80';

$stati = (array) $calendar['idstatiintervento'];
$tipi = (array) $calendar['idtipiintervento'];
$tecnici = (array) $calendar['idtecnici'];

$query = "SELECT
        DATE(orario_inizio) AS data,
        (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=in_interventi.idanagrafica) AS anagrafica,
        GROUP_CONCAT((SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=in_interventi_tecnici.idtecnico) SEPARATOR ', ') AS tecnico
FROM in_interventi_tecnici
    INNER JOIN in_interventi ON in_interventi_tecnici.idintervento=in_interventi.id
    LEFT OUTER JOIN in_statiintervento ON in_interventi.idstatointervento=in_statiintervento.idstatointervento
WHERE ".$where.'
    idtecnico IN('.implode(',', $tecnici).') AND
    in_interventi.idstatointervento IN('.implode(',', $stati).') AND
    in_interventi_tecnici.idtipointervento IN('.implode(',', $tipi).') '.Modules::getAdditionalsQuery('Interventi').'
GROUP BY in_interventi.id, data';
$sessioni = $dbo->fetchArray($query);

$sessioni = collect($sessioni)->groupBy('data');

// Intestazione tabella
echo '
<h3 class="text-bold">'.tr('Calendario _PERIOD_', [
    '_PERIOD_' => $title,
], ['upper' => true]).'</h3>';

// Elenco per la gestione
$list = [];

// Filler per i giorni non inclusi della settimana iniziale
$week_start = $min_date->startOfWeek();
$current_day = $week_start;
while ($current_day->lessThan($min_date)) {
    $list[] = [
        'date' => $current_day->copy(),
        'contents' => [],
    ];

    $current_day->addDay();
}

// Elenco del periodo indicato
while ($current_day->lessThan($max_date)) {
    $list[] = [
        'date' => $current_day->copy(),
        'contents' => $sessioni[$current_day->toDateString()] ?: [],
    ];

    $current_day->addDay();
}

// Filler per i giorni non inclusi della settimana finale
$week_end = $max_date->endOfWeek();
while ($current_day->lessThan($week_end)) {
    $list[] = [
        'date' => $current_day->copy(),
        'contents' => [],
    ];

    $current_day->addDay();
}

// Stampa della tabella
echo '
<table class="table table-bordered">';

$count = count($list);
for ($i = 0; $i < $count; $i = $i + 7) {
    echo '
    <tr>';

    for ($c = 0; $c < 7; ++$c) {
        $element = $list[$i + $c];

        echo '
        <th>'.ucfirst($element['date']->formatLocalized('%A %d/%m')).'</th>';
    }

    echo '
    </tr>';

    echo '
    <tr>';

    for ($c = 0; $c < 7; ++$c) {
        $element = $list[$i + $c];

        $clienti = '';
        foreach ($element['contents'] as $sessione) {
            $clienti .= $sessione['anagrafica'].'<br>
            <small>'.$sessione['tecnico'].'</small><br>';
        }

        $background = '#ffffff';
        if (empty($clienti)) {
            $background = 'lightgray';
        }

        echo '
        <td style="height:'.$height.'px;background:'.$background.'">'.$clienti.'</td>';
    }

    echo '
    </tr>';
}

echo '
</table>';
