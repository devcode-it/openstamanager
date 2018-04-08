<?php

switch ($resource) {
    case 'sync':
        $idtecnico = $user['idanagrafica'];

        $response = API::getRequest(true);

        $ical = new iCalEasyReader();
        $events = $ical->load($response);

        for ($j = 0; $j < sizeof($events['VEVENT']); ++$j) {
            $description = $events['VEVENT'][$j]['DESCRIPTION'];

            // idriga di in_interventi_tecnici
            if (strstr($events['VEVENT'][$j]['UID'], '-')) {
                $idriga = 'NEW';
            } else {
                $idriga = $events['VEVENT'][$j]['UID'];
            }

            // Data-ora inizio
            $dataora = explode('T', $events['VEVENT'][$j]['DTSTART']);
            $data = $dataora[0];
            $ora = $dataora[1];

            $Y = substr($data, 0, 4);
            $m = substr($data, 4, 2);
            $d = substr($data, 6, 2);

            $H = substr($ora, 0, 2);
            $i = substr($ora, 2, 2);

            $orario_inizio = "$Y-$m-$d $H:$i:00";

            // Data-ora fine
            $dataora = explode('T', $events['VEVENT'][$j]['DTEND']);
            $data = $dataora[0];
            $ora = $dataora[1];

            $Y = substr($data, 0, 4);
            $m = substr($data, 4, 2);
            $d = substr($data, 6, 2);

            $H = substr($ora, 0, 2);
            $i = substr($ora, 2, 2);

            $orario_fine = "$Y-$m-$d $H:$i:00";

            // Descrizione
            $richiesta = $events['VEVENT'][$j]['DESCRIPTION'];
            $richiesta = str_replace('\\r\\n', "\n", $richiesta);
            $richiesta = str_replace('\\n', "\n", $richiesta);

            $oggetto = trim($events['VEVENT'][$j]['SUMMARY']);
            $richiesta = str_replace('\\r\\n', "\n", $richiesta);
            $richiesta = str_replace('\\n', "\n", $richiesta);

            // Nuova attività
            if ($idriga == 'NEW') {
                // Data-ora inizio
                $dataora = explode('T', $events['VEVENT'][$j]['DTSTART']['value']);
                $data = $dataora[0];
                $ora = $dataora[1];

                $Y = substr($data, 0, 4);
                $m = substr($data, 4, 2);
                $d = substr($data, 6, 2);

                $H = substr($ora, 0, 2);
                $i = substr($ora, 2, 2);

                $orario_inizio = "$Y-$m-$d $H:$i:00";

                // Data-ora fine
                $dataora = explode('T', $events['VEVENT'][$j]['DTEND']['value']);
                $data = $dataora[0];
                $ora = $dataora[1];

                $Y = substr($data, 0, 4);
                $m = substr($data, 4, 2);
                $d = substr($data, 6, 2);

                $H = substr($ora, 0, 2);
                $i = substr($ora, 2, 2);

                $orario_fine = "$Y-$m-$d $H:$i:00";

                $rs_copie = $dbo->fetchArray("SELECT * FROM in_interventi_tecnici WHERE uid='".$events['VEVENT'][$j]['UID']."'");

                if (sizeof($rs_copie) > 0) {
                    $query = 'UPDATE in_interventi_tecnici SET orario_inizio="'.$orario_inizio.'", orario_fine="'.$orario_fine.'", summary="'.prepare($oggetto).'" WHERE uid="'.$events['VEVENT'][$j]['UID'].'" AND idtecnico="'.$idtecnico.'"';
                    $dbo->query($query);

                    $query = 'UPDATE in_interventi SET richiesta="'.prepare($richiesta).'", oggetto="'.prepare($oggetto)."\" WHERE idintervento=(SELECT idintervento FROM in_interventi_tecnici WHERE idintervento='".$rs_copie[0]['idintervento']."' AND idtecnico=\"".$idtecnico.'" LIMIT 0,1)';
                    $dbo->query($query);

                    $idriga = $rs_copie[0]['id'];
                } else {
                    $idintervento = get_new_idintervento();
                    $query = 'INSERT INTO in_interventi( idintervento, idanagrafica, data_richiesta, richiesta, idtipointervento, idstatointervento, oggetto ) VALUES( "'.$idintervento."\", (SELECT valore FROM zz_impostazioni WHERE nome='Azienda predefinita'), NOW(), \"".prepare($richiesta).'", 0, "CALL", "'.prepare($oggetto).'" )';
                    $dbo->query($query);

                    $query = 'INSERT INTO in_interventi_tecnici( idintervento, idtecnico, orario_inizio, orario_fine, summary, uid ) VALUES( "'.$idintervento.'", "'.$idtecnico.'", "'.$orario_inizio.'", "'.$orario_fine.'", "'.$oggetto.'", "'.$events['VEVENT'][$j]['UID'].'"  )';
                    $dbo->query($query);

                    $idriga = $dbo->last_inserted_id();
                }
            }

            // Modifica attività esistente
            else {
                $query = 'UPDATE in_interventi_tecnici SET orario_inizio="'.$orario_inizio.'", orario_fine="'.$orario_fine.'", summary="'.prepare($oggetto).'" WHERE id="'.$idriga.'" AND idtecnico="'.$idtecnico.'"';
                $dbo->query($query);

                $query = 'UPDATE in_interventi SET richiesta="'.prepare($richiesta).'", oggetto="'.prepare($oggetto).'" WHERE idintervento=(SELECT idintervento FROM in_interventi_tecnici WHERE id="'.$idriga.'" AND idtecnico="'.$idtecnico.'" LIMIT 0,1)';
                $dbo->query($query);
            }

            array_push($allsession, $idriga);
        }

        // Eliminazione attività
        /*
        $rs_sessioni = $dbo->fetchArray("SELECT * FROM in_interventi_tecnici");
        for($i=0;$i<sizeof($rs_sessioni);$i++){
            if(!in_array($rs_sessioni[$i]['id'], $allsession)){
                $idintervento = $rs_sessioni[$i]['idintervento'];
                $dbo->query("DELETE FROM in_interventi_tecnici WHERE id='".$rs_sessioni[$i]['id']."'");
                $rs_per_intervento = $dbo->fetchArray("SELECT * FROM in_interventi_tecnici WHERE idintervento='".$idintervento."'");
                if(sizeof($rs_per_intervento)==0){
                    $dbo->query("UPDATE in_interventi SET deleted=1 WHERE idintervento='".$idintervento."'");
                }
            }
        }
        */

        break;
}

return [
    'sync',
];
