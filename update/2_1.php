<?php

if (file_exists(DOCROOT.'/lib/jscripts/fullcalendar.min.js')) {
    delete(DOCROOT.'/lib/jscripts/fullcalendar.min.js');
}

// Riporto su ogni riga della fattura la relativa rivalsa inps e ritenuta d'acconto se impostate
$rivalsainps = setting('Percentuale rivalsa inps');
$ritenuta = setting("Percentuale ritenuta d'acconto");

$rs = $dbo->fetchArray('SELECT id FROM co_documenti');

for ($i = 0; $i < sizeof($rs); ++$i) {
    if ($rivalsainps != '') {
        $dbo->query('UPDATE co_righe_documenti SET idrivalsainps="'.setting('Percentuale rivalsa INPS').'", rivalsainps=( (subtotale-sconto) /100 * 4 ) WHERE iddocumento="'.$rs[$i]['id'].'"');
    } else {
        $dbo->query('UPDATE co_righe_documenti SET idrivalsainps="0", rivalsainps=0 WHERE iddocumento="'.$rs[$i]['id'].'"');
    }

    if ($ritenuta != '') {
        $dbo->query('UPDATE co_righe_documenti SET idritenutaacconto="'.setting("Percentuale ritenuta d'acconto").'", ritenutaacconto=( (subtotale+rivalsainps-sconto) /100 * 20 ) WHERE iddocumento="'.$rs[$i]['id'].'"');
    } else {
        $dbo->query('UPDATE co_righe_documenti SET idritenutaacconto="0", ritenutaacconto=0 WHERE iddocumento="'.$rs[$i]['id'].'"');
    }
}
