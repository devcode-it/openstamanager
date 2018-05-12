<?php

switch ($resource) {
    case 'sync':
        // Normalizzazione degli interventi a database
        $dbo->query('UPDATE in_interventi_tecnici SET summary = (SELECT ragione_sociale FROM an_anagrafiche INNER JOIN in_interventi ON an_anagrafiche.idanagrafica=in_interventi.idanagrafica WHERE in_interventi.id=in_interventi_tecnici.idintervento) WHERE summary IS NULL');
        $dbo->query('UPDATE in_interventi_tecnici SET uid = id WHERE uid IS NULL');

        // Individuazione degli interventi
        $query = 'SELECT in_interventi_tecnici.id AS idriga, in_interventi_tecnici.idintervento, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=in_interventi.idanagrafica) AS cliente, richiesta, orario_inizio, orario_fine, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=idtecnico) AS nome_tecnico, summary FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi_tecnici.idintervento=in_interventi.id WHERE CAST(orario_inizio AS DATE) BETWEEN CURDATE()-INTERVAL 7 DAY AND CURDATE()+INTERVAL 3 MONTH AND deleted=0';

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
}

return [
    'sync',
];
