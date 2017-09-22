<?php

switch ($resource) {
    case 'sync':
        $dbo->query("UPDATE in_interventi_tecnici SET summary=(SELECT ragione_sociale FROM an_anagrafiche INNER JOIN in_interventi ON an_anagrafiche.idanagrafica=in_interventi.idanagrafica WHERE in_interventi.id=in_interventi_tecnici.idintervento) WHERE summary=''");
        $dbo->query("UPDATE in_interventi_tecnici SET uid=id WHERE uid=''");

        if ($idtecnico != '0') {
            $query = 'SELECT in_interventi_tecnici.id AS idriga, in_interventi_tecnici.idintervento, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=in_interventi.idanagrafica) AS cliente, richiesta, orario_inizio, orario_fine, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=idtecnico) AS nome_tecnico, summary FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi_tecnici.idintervento=in_interventi.id WHERE CAST(orario_inizio AS DATE) BETWEEN CURDATE()-INTERVAL 7 DAY AND CURDATE()+INTERVAL 3 MONTH AND in_interventi_tecnici.idtecnico="'.$idtecnico.'" AND deleted=0';
        } else {
            $query = 'SELECT in_interventi_tecnici.id AS idriga, in_interventi_tecnici.idintervento, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=in_interventi.idanagrafica) AS cliente, richiesta, orario_inizio, orario_fine, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=idtecnico) AS nome_tecnico, summary FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi_tecnici.idintervento=in_interventi.id WHERE CAST(orario_inizio AS DATE) BETWEEN CURDATE()-INTERVAL 7 DAY AND CURDATE()+INTERVAL 3 MONTH AND deleted=0';
        }
        $rs = $dbo->fetchArray($query);

        $results = [];
        $results['custom'] = '';

        $results['custom'] .= "BEGIN:VCALENDAR\n";
        $results['custom'] .= "VERSION:2.0\n";
        $results['custom'] .= "PRODID:-// OpenSTAManager\n";

        for ($i = 0; $i < sizeof($rs); ++$i) {
            $richiesta = str_replace("\r\n", "\n", $rs[$i]['richiesta']);
            $richiesta = str_replace("\r", "\n", $richiesta);
            $richiesta = str_replace("\n", '\\n', $richiesta);

            $oggetto = str_replace("\r\n", "\n", $rs[$i]['oggetto']);

            $results['custom'] .= "BEGIN:VEVENT\n";
            $results['custom'] .= 'UID:'.$rs[$i]['idriga']."\n";
            $results['custom'] .= 'DTSTAMP:'.date('Ymd').'T'.date('His')."\n";
            $results['custom'] .= 'ORGANIZER;CN='.$azienda.':MAILTO:'.$email."\n";
            $results['custom'] .= 'DTSTART:'.date('Ymd', strtotime($rs[$i]['orario_inizio'])).'T'.date('His', strtotime($rs[$i]['orario_inizio']))."\n";
            $results['custom'] .= 'DTEND:'.date('Ymd', strtotime($rs[$i]['orario_fine'])).'T'.date('His', strtotime($rs[$i]['orario_fine']))."\n";
            $results['custom'] .= 'SUMMARY:'.html_entity_decode($rs[$i]['summary'])."\n";
            $results['custom'] .= 'DESCRIPTION:'.html_entity_decode($richiesta, ENT_QUOTES, 'UTF-8')."\n";
            $results['custom'] .= "END:VEVENT\n";
        }

        $results['custom'] .= "END:VCALENDAR\n";

        break;
}

return [
    'sync',
];
