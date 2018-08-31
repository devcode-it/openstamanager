<?php

switch ($resource) {
    case 'sync':
        // Normalizzazione degli interventi a database
        $dbo->query('UPDATE in_interventi_tecnici SET summary = (SELECT ragione_sociale FROM an_anagrafiche INNER JOIN in_interventi ON an_anagrafiche.idanagrafica=in_interventi.idanagrafica WHERE in_interventi.id=in_interventi_tecnici.idintervento) WHERE summary IS NULL');
        $dbo->query('UPDATE in_interventi_tecnici SET uid = id WHERE uid IS NULL');

        // Interpretazione degli eventi
        $idtecnico = $user['idanagrafica'];

        $response = API::getRequest(true);

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
                $rs_copie = $dbo->fetchArray('SELECT * FROM in_interventi_tecnici WHERE uid = '.prepare($event['UID']));

                if (!empty($rs_copie)) {
                    $idintervento = $rs_copie[0]['idintervento'];

                    $dbo->update('in_interventi_tecnici', [
                        'orario_inizio' => $orario_inizio,
                        'orario_fine' => $orario_fine,
                        'summary' => $summary,
                    ], [
                        'uid' => $event['UID'],
                        'idtecnico' => $idtecnico,
                    ]);

                    $dbo->query('UPDATE in_interventi SET richiesta='.prepare($richiesta).', oggetto='.prepare($summary).' WHERE idintervento = (SELECT idintervento FROM in_interventi_tecnici WHERE idintervento = '.prepare($idintervento).' AND idtecnico = '.prepare($idtecnico).' LIMIT 0,1)');

                    $idriga = $rs_copie[0]['id'];
                } else {
                    $idintervento = get_new_idintervento();

                    $dbo->insert('in_interventi', [
                        'idintervento' => $idintervento,
                        '#idanagrafica' => "(SELECT valore FROM zz_settings WHERE nome='Azienda predefinita')",
                        '#data_richiesta' => 'NOW()',
                        'richiesta' => $richiesta,
                        'idtipointervento' => 0,
                        'idstatointervento' => 'CALL',
                        'oggetto' => $summary,
                    ]);

                    $dbo->insert('in_interventi', [
                        'idintervento' => $idintervento,
                        'idtecnico' => $idtecnico,
                        'orario_inizio' => $orario_inizio,
                        'orario_fine' => $orario_fine,
                        'summary' => $summary,
                        'uid' => $event['UID'],
                    ]);

                    $idriga = $dbo->lastInsertedID();
                }
            }

            // Modifica attività esistente
            else {
                $dbo->update('in_interventi_tecnici', [
                    'orario_inizio' => $orario_inizio,
                    'orario_fine' => $orario_fine,
                    'summary' => $summary,
                ], [
                    'id' => $idriga,
                    'idtecnico' => $idtecnico,
                ]);

                $query = 'UPDATE in_interventi SET richiesta='.prepare($richiesta).', oggetto='.prepare($summary).' WHERE idintervento = (SELECT idintervento FROM in_interventi_tecnici WHERE id = '.prepare($idriga).' AND idtecnico = '.prepare($idtecnico).' LIMIT 0,1)';
                $dbo->query($query);
            }
        }

        break;

    case 'intervento':
        $data = $request['data'];

        $dbo->update('in_interventi', [
            'idstatointervento' => $data['id_stato_intervento'],
            'descrizione' => $data['descrizione'],
            'informazioniaggiuntive' => $data['informazioniaggiuntive'],
        ], ['id' => $data['id']]);

        break;

    case 'firma_intervento':
        $data = $request['data'];

        $dbo->update('in_interventi', [
            'firma_file' => $data['firma_file'],
            'firma_data' => $data['firma_data'],
            'firma_nome' => $data['firma_nome'],
        ], ['id' => $data['id']]);

        break;
}

return [
    'sync',
    'intervento',
    'firma_intervento',
];
