<?php

namespace Modules\Interventi\API\v1;

use API\Interfaces\RetrieveInterface;
use API\Interfaces\UpdateInterface;
use API\Resource;
use Carbon\Carbon;
use DateTime;
use iCalEasyReader;

class Sync extends Resource implements RetrieveInterface, UpdateInterface
{
    public function retrieve($request)
    {
        $database = database();
        $user = $this->getUser();

        // Normalizzazione degli interventi a database
        $database->query('UPDATE in_interventi_tecnici SET summary = (SELECT ragione_sociale FROM an_anagrafiche INNER JOIN in_interventi ON an_anagrafiche.idanagrafica=in_interventi.idanagrafica WHERE in_interventi.id=in_interventi_tecnici.idintervento) WHERE summary IS NULL');
        $database->query('UPDATE in_interventi_tecnici SET uid = id WHERE uid IS NULL');

        // Individuazione degli interventi
        $query = 'SELECT in_interventi_tecnici.id AS idriga, in_interventi_tecnici.idintervento, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=in_interventi.idanagrafica) AS cliente, richiesta, orario_inizio, orario_fine, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=idtecnico) AS nome_tecnico, summary FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi_tecnici.idintervento=in_interventi.id WHERE DATE(orario_inizio) BETWEEN CURDATE() - INTERVAL 7 DAY AND CURDATE() + INTERVAL 3 MONTH AND deleted_at IS NULL';

        if ($user->anagrafica->isTipo('Tecnico')) {
            $query .= ' AND in_interventi_tecnici.idtecnico = '.prepare($user['idanagrafica']);
        }

        if ($user->anagrafica->isTipo('Cliente')) {
            $query .= ' AND in_interventi.idanagrafica = '.prepare($user['idanagrafica']);
        }

        $rs = $database->fetchArray($query);

        $result = '';

        $result .= "BEGIN:VCALENDAR\n";
        $result .= "VERSION:2.0\n";
        $result .= "PRODID:-// OpenSTAManager\n";

        foreach ($rs as $r) {
            $richiesta = str_replace("\r\n", "\n", $r['richiesta']);
            $richiesta = str_replace("\r", "\n", $richiesta);
            $richiesta = str_replace("\n", '\\n', $richiesta);

            $r['summary'] = str_replace("\r\n", "\n", $r['summary']);

            $now = new Carbon();
            $inizio = new Carbon($r['orario_inizio']);
            $fine = new Carbon($r['orario_fine']);

            $result .= "BEGIN:VEVENT\n";
            $result .= 'UID:'.$r['idriga']."\n";
            $result .= 'DTSTAMP:'.$now->format('Ymd\THis')."\n";
            //$result .= 'ORGANIZER;CN='.$azienda.':MAILTO:'.$email."\n";
            $result .= 'DTSTART:'.$inizio->format('Ymd\THis')."\n";
            $result .= 'DTEND:'.$fine->format('Ymd\THis')."\n";
            $result .= 'SUMMARY:'.html_entity_decode($r['summary'])."\n";
            $result .= 'DESCRIPTION:'.html_entity_decode($richiesta, ENT_QUOTES, 'UTF-8')."\n";
            $result .= "END:VEVENT\n";
        }

        $result .= "END:VCALENDAR\n";

        return [
            'custom' => $result,
        ];
    }

    public function update($request)
    {
        $database = database();
        $user = $this->getUser();

        // Normalizzazione degli interventi a database
        $database->query('UPDATE in_interventi_tecnici SET summary = (SELECT ragione_sociale FROM an_anagrafiche INNER JOIN in_interventi ON an_anagrafiche.idanagrafica=in_interventi.idanagrafica WHERE in_interventi.id=in_interventi_tecnici.idintervento) WHERE summary IS NULL');
        $database->query('UPDATE in_interventi_tecnici SET uid = id WHERE uid IS NULL');

        // Interpretazione degli eventi
        $idtecnico = $user['idanagrafica'];

        $response = API\Response::getRequest(true);

        $ical = new iCalEasyReader();
        $events = $ical->load($response);

        foreach ($events['VEVENT'] as $event) {
            $description = $event['DESCRIPTION'];

            // Individuazione idriga di in_interventi_tecnici
            if (str_contains($event['UID'], '-')) {
                $idriga = 'NEW';
            } else {
                $idriga = $event['UID'];
            }

            // Timestamp di inizio
            $orario_inizio = DateTime::createFromFormat('Ymd\\THi', $event['DTSTART'])->format(Intl\Formatter::getStandardFormats()['timestamp']);

            // Timestamp di fine
            $orario_fine = DateTime::createFromFormat('Ymd\\THi', $event['DTEND'])->format(Intl\Formatter::getStandardFormats()['timestamp']);

            // Descrizione
            $richiesta = $event['DESCRIPTION'];
            $richiesta = str_replace('\\r\\n', "\n", $richiesta);
            $richiesta = str_replace('\\n', "\n", $richiesta);

            $summary = trim($event['SUMMARY']);
            $summary = str_replace('\\r\\n', "\n", $summary);
            $summary = str_replace('\\n', "\n", $summary);

            // Nuova attività
            if ($idriga == 'NEW') {
                $rs_copie = $database->fetchArray('SELECT * FROM in_interventi_tecnici WHERE uid = '.prepare($event['UID']));

                if (!empty($rs_copie)) {
                    $idintervento = $rs_copie[0]['idintervento'];

                    $database->update('in_interventi_tecnici', [
                        'orario_inizio' => $orario_inizio,
                        'orario_fine' => $orario_fine,
                        'summary' => $summary,
                    ], [
                        'uid' => $event['UID'],
                        'idtecnico' => $idtecnico,
                    ]);

                    $database->query('UPDATE in_interventi SET richiesta='.prepare($richiesta).', oggetto='.prepare($summary).' WHERE idintervento = (SELECT idintervento FROM in_interventi_tecnici WHERE idintervento = '.prepare($idintervento).' AND idtecnico = '.prepare($idtecnico).' LIMIT 0,1)');

                    $idriga = $rs_copie[0]['id'];
                } else {
                    $idintervento = get_new_idintervento();
                    $stato = $database->fetchArray("SELECT * FROM in_statiintervento WHERE descrizione = 'Chiamata'");

                    $database->insert('in_interventi', [
                        'idintervento' => $idintervento,
                        'idanagrafica' => setting('Azienda predefinita'),
                        'data_richiesta' => Carbon::now(),
                        'richiesta' => $richiesta,
                        'idtipointervento' => 0,
                        'idstatointervento' => $stato['idstatointerventoWIP'],
                        'oggetto' => $summary,
                    ]);

                    $database->insert('in_interventi', [
                        'idintervento' => $idintervento,
                        'idtecnico' => $idtecnico,
                        'orario_inizio' => $orario_inizio,
                        'orario_fine' => $orario_fine,
                        'summary' => $summary,
                        'uid' => $event['UID'],
                    ]);

                    $idriga = $database->lastInsertedID();
                }
            }

            // Modifica attività esistente
            else {
                $database->update('in_interventi_tecnici', [
                    'orario_inizio' => $orario_inizio,
                    'orario_fine' => $orario_fine,
                    'summary' => $summary,
                ], [
                    'id' => $idriga,
                    'idtecnico' => $idtecnico,
                ]);

                $query = 'UPDATE in_interventi SET richiesta='.prepare($richiesta).', oggetto='.prepare($summary).' WHERE idintervento = (SELECT idintervento FROM in_interventi_tecnici WHERE id = '.prepare($idriga).' AND idtecnico = '.prepare($idtecnico).' LIMIT 0,1)';
                $database->query($query);
            }
        }
    }
}
