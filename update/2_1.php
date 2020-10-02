<?php

if (file_exists(base_dir().'/lib/jscripts/fullcalendar.min.js')) {
    delete(base_dir().'/lib/jscripts/fullcalendar.min.js');
}

// Riporto su ogni riga della fattura la relativa rivalsa inps e ritenuta d'acconto se impostate
$rivalsainps = $dbo->fetchArray("SELECT valore FROM zz_impostazioni WHERE nome='Percentuale rivalsa INPS'")[0]['valore'];
$ritenuta = $dbo->fetchArray("SELECT valore FROM zz_impostazioni WHERE nome='Percentuale ritenuta d''acconto'")[0]['valore'];

$rs = $dbo->fetchArray('SELECT id FROM co_documenti');

for ($i = 0; $i < sizeof($rs); ++$i) {
    if ($rivalsainps != '') {
        $dbo->query('UPDATE co_righe_documenti SET idrivalsainps="'.$rivalsainps.'", rivalsainps=( (subtotale-sconto) /100 * 4 ) WHERE iddocumento="'.$rs[$i]['id'].'"');
    } else {
        $dbo->query('UPDATE co_righe_documenti SET idrivalsainps="0", rivalsainps=0 WHERE iddocumento="'.$rs[$i]['id'].'"');
    }

    if ($ritenuta != '') {
        $dbo->query('UPDATE co_righe_documenti SET idritenutaacconto="'.$ritenuta.'", ritenutaacconto=( (subtotale+rivalsainps-sconto) /100 * 20 ) WHERE iddocumento="'.$rs[$i]['id'].'"');
    } else {
        $dbo->query('UPDATE co_righe_documenti SET idritenutaacconto="0", ritenutaacconto=0 WHERE iddocumento="'.$rs[$i]['id'].'"');
    }
}
