<?php

switch ($resource) {
    case 'sync':
        // Normalizzazione degli interventi a database
        $dbo->query('UPDATE in_interventi_tecnici SET summary = (SELECT ragione_sociale FROM an_anagrafiche INNER JOIN in_interventi ON an_anagrafiche.idanagrafica=in_interventi.idanagrafica WHERE in_interventi.id=in_interventi_tecnici.idintervento) WHERE summary IS NULL');
        $dbo->query('UPDATE in_interventi_tecnici SET uid = id WHERE uid IS NULL');

        // Individuazione degli interventi
        $query = 'SELECT in_interventi_tecnici.id AS idriga, in_interventi_tecnici.idintervento, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=in_interventi.idanagrafica) AS cliente, richiesta, orario_inizio, orario_fine, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=idtecnico) AS nome_tecnico, summary FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi_tecnici.idintervento=in_interventi.id WHERE CAST(orario_inizio AS DATE) BETWEEN CURDATE()-INTERVAL 7 DAY AND CURDATE()+INTERVAL 3 MONTH AND deleted_at IS NULL';

        if (!empty($user['idanagrafica'])) {
            $query .= ' AND in_interventi_tecnici.idtecnico = '.prepare($user['idanagrafica']);
        }

        $rs = $dbo->fetchArray($query);

        $results = [];
        $results['custom'] = '';

        $results['custom'] .= "BEGIN:VCALENDAR\n";
        $results['custom'] .= 'VERSION:'.Update::getVersion()."\n";
        $results['custom'] .= "PRODID:-// OpenSTAManager\n";

        foreach ($rs as $r) {
            $richiesta = str_replace("\r\n", "\n", $r['richiesta']);
            $richiesta = str_replace("\r", "\n", $richiesta);
            $richiesta = str_replace("\n", '\\n', $richiesta);

            $r['summary'] = str_replace("\r\n", "\n", $r['summary']);

            $results['custom'] .= "BEGIN:VEVENT\n";
            $results['custom'] .= 'UID:'.$r['idriga']."\n";
            $results['custom'] .= 'DTSTAMP:'.date('Ymd').'T'.date('His')."\n";
            //$results['custom'] .= 'ORGANIZER;CN='.$azienda.':MAILTO:'.$email."\n";
            $results['custom'] .= 'DTSTART:'.date('Ymd', strtotime($r['orario_inizio'])).'T'.date('His', strtotime($r['orario_inizio']))."\n";
            $results['custom'] .= 'DTEND:'.date('Ymd', strtotime($r['orario_fine'])).'T'.date('His', strtotime($r['orario_fine']))."\n";
            $results['custom'] .= 'SUMMARY:'.html_entity_decode($r['summary'])."\n";
            $results['custom'] .= 'DESCRIPTION:'.html_entity_decode($richiesta, ENT_QUOTES, 'UTF-8')."\n";
            $results['custom'] .= "END:VEVENT\n";
        }

        $results['custom'] .= "END:VCALENDAR\n";

        break;

    // Elenco interventi per l'applicazione (recupero sempre tutti gli interventi che non vengono chiusi)
    case 'interventi':
        // Periodo per selezionare interventi
        $today = date('Y-m-d');
        $period_end = date('Y-m-d', strtotime($today.' +7 days'));

        $query = "SELECT `in_interventi`.`id`,
            `in_interventi`.`codice`,
            DATE_FORMAT(`in_interventi`.`data_richiesta`, '%Y%m%d') AS `data_richiesta`,
            `in_interventi`.`richiesta`,
            `in_interventi`.`descrizione`,
            `in_interventi`.`idtipointervento`,
            `in_interventi`.`idanagrafica`,
            `an_anagrafiche`.`idzona` AS zona_anagrafica,
            `in_interventi`.`idsede`,
            `an_sedi`.`idzona` AS zona_sede,
            `in_interventi`.`idstatointervento`,
            `in_interventi`.`informazioniaggiuntive`,
            `in_interventi`.`idclientefinale`,
            `in_interventi`.`firma_file`,
            IF(firma_data = '0000-00-00 00:00:00', '', DATE_FORMAT(firma_data,'%d/%m/%Y %T') ) AS `firma_data`,
            `in_interventi`.firma_nome,
            (SELECT GROUP_CONCAT( CONCAT(my_impianti.matricola, ' - ', my_impianti.nome) SEPARATOR ', ') FROM (my_impianti_interventi INNER JOIN my_impianti ON my_impianti_interventi.idimpianto=my_impianti.id) WHERE my_impianti_interventi.idintervento=`in_interventi`.`id`) AS `impianti`,
            (SELECT DATE_FORMAT(MAX(`orario_fine`), '%Y%m%d') FROM `in_interventi_tecnici` WHERE `in_interventi_tecnici`.`idintervento`=`in_interventi`.`id`) AS `data`,
            (SELECT GROUP_CONCAT(ragione_sociale SEPARATOR ', ') FROM `in_interventi_tecnici` INNER JOIN `an_anagrafiche` ON `in_interventi_tecnici`.`idtecnico`=`an_anagrafiche`.`idanagrafica` WHERE `in_interventi_tecnici`.`idintervento`=`in_interventi`.`id`) AS `tecnici`,
            `in_statiintervento`.`colore` AS `bgcolor`,
            `in_statiintervento`.`descrizione` AS `stato`,
            `in_interventi`.`idtipointervento` AS `tipo`
        FROM `in_interventi`
            INNER JOIN `in_statiintervento` ON `in_interventi`.`idstatointervento`=`in_statiintervento`.`idstatointervento`
            INNER JOIN `an_anagrafiche` ON `in_interventi`.`idanagrafica`=`an_anagrafiche`.`idanagrafica`
            LEFT JOIN `an_sedi` ON `in_interventi`.`idsede`=`an_sedi`.`id`
        WHERE (SELECT MAX(`orario_fine`) FROM `in_interventi_tecnici` WHERE `in_interventi_tecnici`.`idintervento`=`in_interventi`.`id`) <= :period_end";

        // TODO: rimosse seguenti clausole:

        // WHERE `in_interventi`.idstatointervento IN(SELECT idstatointervento FROM in_statiintervento WHERE app_download=1)
        // nel database ufficiale manca in_statiintervento.app_download

        // AND `in_interventi_tecnici`.`idtecnico`='".$tecnico[0]['idanagrafica']."'
        // nell'inner join con in_interventi_tecnici -> ad oggi 16-05-2018 non gestisco ancora idtecnico

        $parameters = [
            ':period_end' => $period_end,
        ];

        $results = $dbo->fetchArray($query, $parameters.' LIMIT '.($page * $length).', '.$length);

        $results['records'] = $database->fetchNum($query, $parameters);
        $results['pages'] = $results['records'] / $length;

        break;

    // Elenco sessioni dell'intervento per l'applicazione
    case 'sessioni_intervento':
        $query = 'SELECT id, idintervento, orario_inizio, orario_fine FROM in_interventi_tecnici WHERE `idintervento` = :id_intervento';

        if ($user['gruppo'] == 'Tecnici') {
            $query .= ' AND `idtecnico` = :id_tecnico';
        }

        // TODO: rimosse seguenti clausole:

        // WHERE `in_interventi`.idstatointervento IN(SELECT idstatointervento FROM in_statiintervento WHERE app_download=1)
        // nel database ufficiale manca in_statiintervento.app_download

        $parameters = [
            ':id_intervento' => $request['id_intervento'],
            ':id_tecnico' => $user['idanagrafica'],
        ];

        $results = $dbo->fetchArray($query, $parameters.' LIMIT '.($page * $length).', '.$length);

        $results['records'] = $database->fetchNum($query, $parameters);
        $results['pages'] = $results['records'] / $length;

        break;
}

return [
    'sync',
    'interventi',
];
