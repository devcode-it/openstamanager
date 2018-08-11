<?php

include_once __DIR__.'/../../core.php';

$height = '80';

if (isset($_SESSION['period']['month'])) {
    $period = explode(' ', $_SESSION['period']['month']);
    $month = $period[0];
    $year = $period[1];

    $months = ['Gennaio' => '01', 'Febbraio' => '02', 'Marzo' => '03', 'Aprile' => '04', 'Maggio' => '05', 'Giugno' => '06', 'Luglio' => '07', 'Agosto' => '08', 'Settembre' => '09', 'Ottobre' => '10', 'Novembre' => '11', 'Dicembre' => '12'];
    $month = $months[$month];

    $title = $_SESSION['period']['month'];

    //numero di giorni nel mese
    $maxday = cal_days_in_month(CAL_GREGORIAN, $month, $year) + 1;

    $mindate = $year.'-'.$month.'-'.'01';
    $maxdate = $year.'-'.$month.'-'.$maxday;

    $where = '  (in_interventi_tecnici.orario_inizio) <= '.prepare($maxdate).' AND  (in_interventi_tecnici.orario_inizio) >= '.prepare($mindate).' AND ';
}

if (isset($_SESSION['period']['week'])) {
    $period = explode(' ', $_SESSION['period']['week']);

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

    $title = $_SESSION['period']['week'];

    //numero di giorni nel mese
    $maxday = cal_days_in_month(CAL_GREGORIAN, $month, $year) + 1;

    $mindate = $year.'-'.$month.'-'.$day;
    $maxdate = $year.'-'.$maxmonth.'-'.$maxday_;

    //aggiungo un giorno
    $maxdate = date('Y-m-d', date(strtotime('+1 day', strtotime($maxdate))));

    $where = '  (in_interventi_tecnici.orario_inizio) <= '.prepare($maxdate).' AND  (in_interventi_tecnici.orario_inizio) >= '.prepare($mindate).' AND ';
}

$report_name = sanitizeFilename('dashboard_'.$year.'_'.$month.'.pdf');

//$date_start = $_SESSION['period_start'];
//$date_end = $_SESSION['period_end'];
$stati = (array) $_SESSION['dashboard']['idstatiintervento'];
$tipi = (array) $_SESSION['dashboard']['idtipiintervento'];
$tecnici = (array) $_SESSION['dashboard']['idtecnici'];

//in_interventi_tecnici.idintervento, colore, in_interventi_tecnici.id, idtecnico, orario_inizio, orario_fine,(SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=idtecnico) AS nome_tecnico, (SELECT colore FROM an_anagrafiche WHERE idanagrafica=idtecnico) AS colore_tecnico,
$query = 'SELECT DAY(in_interventi_tecnici.orario_inizio) AS giorno, orario_inizio AS data, GROUP_CONCAT((SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=in_interventi.idanagrafica)  SEPARATOR \'<br>\') AS cliente FROM in_interventi_tecnici INNER JOIN (in_interventi LEFT OUTER JOIN in_statiintervento ON in_interventi.idstatointervento=in_statiintervento.idstatointervento) ON in_interventi_tecnici.idintervento=in_interventi.id WHERE '.$where.' idtecnico IN('.implode(',', $tecnici).') AND in_interventi.idstatointervento IN('.implode(',', $stati).') AND in_interventi_tecnici.idtipointervento IN('.implode(',', $tipi).') '.Modules::getAdditionalsQuery('Interventi').' GROUP BY giorno ORDER BY CAST(giorno AS UNSIGNED)';

//echo $query;

$sessioni = $dbo->fetchArray($query);

//echo $query;
$rs = [];
for ($i = 0; $i < 33; ++$i) {
    $rs[$sessioni[$i]['giorno']]['cliente'] = $sessioni[$i]['cliente'];
    $rs[$sessioni[$i]['giorno']]['data'] = $sessioni[$i]['data'];
}

function showMonth($month, $year, &$rs, &$height)
{
    $date = mktime(12, 0, 0, $month, 1, $year);
    $daysInMonth = date('t', $date);
    // calculate the position of the first day in the calendar (sunday = 1st column, etc)
    $offset = (date('w', $date) - 1) % 7;

    if ($offset < 0) {
        $offset = 7 + $offset;
    }

    //echo  $date."<br>";
    //echo date("w", $date)."<br>";
    //echo $offset;

    $rows = 1;

    //echo "<h1>Displaying calendar for " . date("F Y", $date) . "</h1>\n";
    $header = [];
    $row = [];

    //$table .=  "<table border=\"1\">\n";
    //echo "\t<tr><th>Su</th><th>M</th><th>Tu</th><th>W</th><th>Th</th><th>F</th><th>Sa</th></tr>";
    // $table .= "\t<tr><th>Lun</th><th>Mar</th><th>Mer</th><th>Gio</th><th>Ven</th><th>Sab</th><th>Dom</th></tr>";

    //$header[$rows] =  "\n\t<tr>";
    //$row[$rows] =  "\n\t<tr>";

    //giorni prima
    for ($i = 1; $i <= $offset; ++$i) {
        $current_month = $month;
        if ($current_month == 1) {
            $current_month = 12;
            $current_year = $year - 1;
        } else {
            $current_month = $month - 1;
            $current_year = $year;
        }

        $lastdateofmonth = date('t', $current_month);

        //$lastdate = $lastdateofmonth."/".$current_month."/".$current_year;

        $day = (($lastdateofmonth - $offset) + ($i));
        $weekday = date('l', strtotime($current_year.'-'.$current_month.'-'.(sprintf('%02d', $day))));
        $weekdays = ['Monday' => 'Lunedi\'', 'Tuesday' => 'Martedi\'', 'Wednesday' => 'Mercoledi\'', 'Thursday' => 'Giovedi\'', 'Friday' => 'Venerdi\'', 'Saturday' => 'Sabato', 'Sunday' => 'Domenica'];
        $weekday = $weekdays[$weekday];

        $header[$rows] .= '<th>'.tr($weekday.' '.(sprintf('%02d', $day)).'/'.(sprintf('%02d', $current_month)), [], ['upper' => true])."</th>\n";
        $row[$rows] .= "<td style=\"background:lightgray;\" ><b> </b></td>\n";
    }

    //giorni del mese
    for ($day = 1; $day <= $daysInMonth; ++$day) {
        if (($day + $offset - 1) % 7 == 0 && $day != 1) {
            // $table .= "\t<tr><th>Lun ".$day."</th><th>Mar ".($day+1)."</th><th>Mer ".($day+2)."</th><th>Gio ".($day+3)."</th><th>Ven ".($day+4)."</th><th>Sab ".($day+5)."</th><th>Dom ".($day+6)."</th></tr>";

            // $header[$rows] .= "</tr>\n\t<tr>";
            //$row[$rows] .= "</tr>\n\t<tr>";

            ++$rows;
        }

        $weekday = date('l', strtotime($year.'-'.$month.'-'.(sprintf('%02d', $day))));
        $weekdays = ['Monday' => 'Lunedi\'', 'Tuesday' => 'Martedi\'', 'Wednesday' => 'Mercoledi\'', 'Thursday' => 'Giovedi\'', 'Friday' => 'Venerdi\'', 'Saturday' => 'Sabato', 'Sunday' => 'Domenica'];
        $weekday = $weekdays[$weekday];

        $header[$rows] .= '<th>'.tr($weekday.' '.(sprintf('%02d', $day)).'/'.$month, [], ['upper' => true])."</th>\n";
        if (empty($rs[$day]['cliente'])) {
            $rs[$day]['cliente'] = ' ';
        }

        $row[$rows] .= "<td  style='height:".$height."px' >".'<b>'.$rs[$day]['cliente']."</b></td>\n";
    }

    //$i = 1;
    //giorni dopo
    //while( ($day + $offset) <= $rows * 7){

    for ($i = 1; ($day + $offset) <= ($rows * 7); ++$i) {
        $current_month = $month;
        if ($current_month == 12) {
            $current_month = 1;
            $current_year = $year + 1;
        } else {
            $current_month = $month + 1;
            $current_year = $year;
        }

        //$lastdateofmonth = date('t',$current_month);

        //$lastdate = $lastdateofmonth."/".$current_month."/".$current_year;

        $weekday = date('l', strtotime($current_year.'-'.$current_month.'-'.(sprintf('%02d', $i))));
        $weekdays = ['Monday' => 'Lunedi\'', 'Tuesday' => 'Martedi\'', 'Wednesday' => 'Mercoledi\'', 'Thursday' => 'Giovedi\'', 'Friday' => 'Venerdi\'', 'Saturday' => 'Sabato', 'Sunday' => 'Domenica'];
        $weekday = $weekdays[$weekday];

        $header[$rows] .= '<th> '.tr($weekday.' '.(sprintf('%02d', $i)).'/'.(sprintf('%02d', $current_month)), [], ['upper' => true])." </th>\n";
        //$row[$rows] .= "<td> ".($offset+$day)."<br>".($rows * 7)." </td>\n";
        $row[$rows] .= "<td style=\"background:lightgray;\" ><b> </b> </td>\n";

        ++$day;
    }

    //$header[$rows] .= "</tr>";
    //$row[$rows] .= "</tr>";

    //print_r($header);
    //echo "<br>";
    //print_r($row);

    echo '<table class="table table-bordered">\n';

    //creo righe
    for ($i = 1; $i <= count($row); ++$i) {
        echo "<tr>\n";
        echo  $header[$i];
        echo "</tr>\n";

        echo "<tr>\n";
        echo  $row[$i];
        echo "</tr>\n";
    }

    echo '</table>';

    //$table .= "</table>\n";

  //echo $table;
}

// Intestazione tabella per righe
echo "
<h3 class='text-bold'>".tr('Calendario _PERIOD_', [
    '_PERIOD_' => $title,
], ['upper' => true]).'</h3>';

showMonth($month, $year, $rs, $height);
