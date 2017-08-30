<?php

include_once __DIR__.'/../../core.php';

if (!isset($user['idanagrafica'])) {
    $user['idanagrafica'] = '';
}

switch (get('op')) {
    // Lettura calendario tecnici
    case 'get_current_month':
        $start = get('start');
        $end = get('end');

        $query = "SELECT in_interventi_tecnici.idintervento, colore, in_interventi_tecnici.id, idtecnico, orario_inizio, orario_fine, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=idtecnico) AS nome_tecnico, (SELECT colore FROM an_anagrafiche WHERE idanagrafica=idtecnico) AS colore_tecnico, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=in_interventi.idanagrafica) AS cliente FROM in_interventi_tecnici INNER JOIN (in_interventi LEFT OUTER JOIN in_statiintervento ON in_interventi.idstatointervento=in_statiintervento.idstatointervento) ON in_interventi_tecnici.idintervento=in_interventi.id WHERE in_interventi_tecnici.orario_inizio >= ".prepare($startend)." AND in_interventi_tecnici.orario_fine <= ".prepare($end)." AND idtecnico IN(".implode(',', $_SESSION['dashboard']['idtecnici']).') AND (in_interventi.idstatointervento IN('.implode(',', $_SESSION['dashboard']['idstatiintervento']).") OR in_interventi.idstatointervento='') AND in_interventi_tecnici.idtipointervento IN(".implode(',', $_SESSION['dashboard']['idtipiintervento']).') '.Modules::getAdditionalsQuery('Interventi');

        $rs = $dbo->fetchArray($query);
        $n = count($rs);

        $results = [];
        for ($i = 0; $i < $n; ++$i) {
            if ($rs[$i]['colore_tecnico'] == '#FFFFFF') {
                $color = color_darken($rs[$i]['colore_tecnico'], 100);
            } else {
                $color = $rs[$i]['colore_tecnico'];
            }

            $results[] = '
            {
                "id": "'.$rs[$i]['idintervento'].'",
                "idtecnico":"'.$rs[$i]['idtecnico'].'",
                "orario":"1",
                "title":"<b>Int. '.$rs[$i]['idintervento'].'</b>\\n'.addslashes($rs[$i]['cliente']).'<br>\\n<b>'._('Tecnici').': </b>'.addslashes($rs[$i]['nome_tecnico']).'",
                "start": "'.date('Y-m-d H:i', strtotime($rs[$i]['orario_inizio'])).'",
                "end": "'.date('Y-m-d H:i', strtotime($rs[$i]['orario_fine'])).'",
                "url":"'.$rootdir.'/editor.php?id_module='.Modules::getModule('Interventi')['id'].'&id_record='.$rs[$i]['idintervento'].'",
                "backgroundColor":"'.$rs[$i]['colore'].'",
                "textColor":"'.color_inverse($rs[$i]['colore']).'",
                "borderColor":"'.$color.'",
                "allDay": false
            }';
        }
        echo '['.implode(',', $results).']';

        break;

    case 'update_intervento':
        $id = get('id');
        $timeStart = get('timeStart');
        $timeEnd = get('timeEnd');

        //Aggiornamento prezzo totale
        $q = 'SELECT in_interventi_tecnici.prezzo_ore_unitario, idtecnico, in_statiintervento.completato FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi_tecnici.idintervento=in_interventi.id LEFT OUTER JOIN in_statiintervento ON in_interventi.idstatointervento =  in_statiintervento.idstatointervento WHERE in_interventi.id='.prepare($id).' AND in_statiintervento.completato = 0 '.Modules::getAdditionalsQuery('Interventi');
        $rs = $dbo->fetchArray($q);
        $prezzo_ore = 0.00;

        for ($i = 0; $i < count($rs); ++$i) {
            $prezzo_ore_unitario = $rs[$i]['prezzo_ore_unitario'];

            $t = datediff('n', $timeStart, $timeEnd);
            $t = floatval(round($t / 60, 1));
            $prezzo_ore += $t * $prezzo_ore_unitario;
        }

        if (count($rs) > 0) {
            // Aggiornamento orario tecnico
            $dbo->query('UPDATE in_interventi_tecnici SET orario_inizio = '.prepare($timeStart).', orario_fine = '.prepare($timeEnd).', ore='.prepare($t).', prezzo_ore_consuntivo='.prepare($t * $prezzo_ore_unitario).' WHERE id='.prepare($id));
            echo 'ok';
        } else {
            echo _('Attività completata, non è possibile modificarla!');
        }

        break;

    case 'get_more_info':
        $id = get('id');
        $timeStart = get('timeStart');
        $timeEnd = get('timeEnd');

        //Lettura dati intervento di riferimento
        $query = 'SELECT in_interventi_tecnici.idintervento, in_interventi.id, idtecnico, orario_inizio, orario_fine, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=idtecnico) AS nome_tecnico, (SELECT colore FROM an_anagrafiche WHERE idanagrafica=idtecnico) AS colore FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi_tecnici.idintervento=in_interventi.id WHERE in_interventi.id='.prepare($id).' '.Modules::getAdditionalsQuery('Interventi');
        $rs = $dbo->fetchArray($query);

        $tecnici = [];
        foreach ($rs as $r) {
            $tecnici[] = $r['nome_tecnico'].' ('.Translator::timestampToLocale($r['orario_inizio']).' - '.Translator::timeToLocale($r['orario_fine']).')';
        }

        // Lettura dati intervento
        $query = 'SELECT *, idstatointervento AS parent_idstato, idtipointervento AS parent_idtipo, (SELECT descrizione FROM in_statiintervento WHERE idstatointervento=parent_idstato) AS stato, (SELECT descrizione FROM in_tipiintervento WHERE idtipointervento=parent_idtipo) AS tipo, (SELECT nomesede FROM an_sedi WHERE id=idsede) AS sede, (SELECT idzona FROM an_anagrafiche WHERE idanagrafica=in_interventi.idanagrafica) AS idzona FROM in_interventi LEFT JOIN an_anagrafiche ON in_interventi.idanagrafica=an_anagrafiche.idanagrafica WHERE in_interventi.id='.prepare($id).' '.Modules::getAdditionalsQuery('Interventi');
        $rs = $dbo->fetchArray($query);

        $desc_tipointervento = $rs[0]['tipo'];

        $tooltip_text = '<b>'._('Numero intervento').'</b>: '.$id.'<br/>';
        $tooltip_text .= '<b>'._('Ragione sociale').'</b>: '.nl2br($rs[0]['ragione_sociale']).'<br/>';

        if (!empty($rs[0]['telefono'] != '')) {
            $tooltip_text .= '<b>'._('Telefono').'</b>: '.nl2br($rs[0]['telefono']).'<br/>';
        }

        if (!empty($rs[0]['cellulare'])) {
            $tooltip_text .= '<b>'._('Cellulare').'</b>: '.nl2br($rs[0]['cellulare']).'<br/>';
        }

        if (!empty($rs[0]['indirizzo']) || !empty($rs[0]['citta']) || !empty($rs[0]['provincia'])) {
            $tooltip_text .= '<b>'._('Indirizzo').'</b>: '.nl2br($rs[0]['indirizzo'].' '.$rs[0]['citta'].' ('.$rs[0]['provincia'].')').'<br/>';
        }

        if (!empty($rs[0]['note'])) {
            $tooltip_text .= '<b>'._('Note').'</b>: '.nl2br($rs[0]['note']).'<br/>';
        }

        $tooltip_text .= '<b>'._('Data richiesta').'</b>: '.Translator::timestampToLocale($rs[0]['data_richiesta']).'<br/>';

        $tooltip_text .= '<b>'._('Tipo intervento').'</b>: '.nl2br($desc_tipointervento).'<br/>';

        $tooltip_text .= '<b>'._('Tecnici').'</b>: '.implode(', ', $tecnici).'<br/>';

        if ($rs[0]['richiesta'] != '') {
            $tooltip_text .= '<b>'._('Richiesta').'</b>: '.nl2br($rs[0]['richiesta']).'<br/>';
        }

        if ($rs[0]['descrizione'] != '') {
            $tooltip_text .= '<b>'._('Descrizione').'</b>: '.nl2br($rs[0]['descrizione']).'<br/>';
        }

        if ($rs[0]['informazioniaggiuntive'] != '') {
            $tooltip_text .= '<b>'._('Informazioni aggiuntive').'</b>: '.nl2br($rs[0]['informazioniaggiuntive']).'<br/>';
        }

        echo $tooltip_text;

    break;
}
