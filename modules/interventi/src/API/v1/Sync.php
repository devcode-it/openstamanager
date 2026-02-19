<?php

/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Modules\Interventi\API\v1;

use API\Interfaces\RetrieveInterface;
use API\Interfaces\UpdateInterface;
use API\Resource;
use Carbon\Carbon;
use Modules\Anagrafiche\Anagrafica;
use Modules\Interventi\Components\Sessione;
use Modules\Interventi\Intervento;
use Modules\Interventi\Stato;
use Modules\TipiIntervento\Tipo as TipoSessione;

class Sync extends Resource implements RetrieveInterface, UpdateInterface
{
    public function retrieve($request)
    {
        $database = database();
        $user = $this->getUser();

        // Controllo permessi per il modulo Interventi
        $module = \Models\Module::where('name', 'Interventi')->first();
        $permission = \Modules::getPermission($module->id);
        
        // Verifica se l'utente ha permessi di lettura per il modulo Interventi
        if (!in_array($permission, ['r', 'rw']) || (!$user->anagrafica->isTipo('Tecnico') && !$user->anagrafica->isTipo('Cliente'))) {
            return [
                'custom' => '',
            ];
        }

        // Normalizzazione degli interventi a database
        $database->query('UPDATE in_interventi_tecnici SET summary = (SELECT ragione_sociale FROM an_anagrafiche INNER JOIN in_interventi ON an_anagrafiche.idanagrafica=in_interventi.idanagrafica WHERE in_interventi.id=in_interventi_tecnici.idintervento) WHERE summary IS NULL');
        $database->query('UPDATE in_interventi_tecnici SET uid = id WHERE uid IS NULL');
        $database->query('UPDATE in_interventi_tecnici SET description = (SELECT richiesta FROM in_interventi WHERE in_interventi.id=in_interventi_tecnici.idintervento) WHERE description=""');

        // Individuazione degli interventi
        $query = 'SELECT 
            in_interventi_tecnici.id AS idriga, in_interventi_tecnici.idintervento, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=in_interventi.idanagrafica) AS cliente, in_interventi_tecnici.description, orario_inizio, orario_fine, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=idtecnico) AS nome_tecnico, summary 
        FROM 
            in_interventi_tecnici 
        INNER JOIN 
            in_interventi ON in_interventi_tecnici.idintervento=in_interventi.id
        LEFT JOIN `in_tipiintervento` ON `in_interventi_tecnici`.`idtipointervento` = `in_tipiintervento`.`id`
        WHERE 
            DATE(orario_inizio) BETWEEN CURDATE() - INTERVAL 7 DAY AND CURDATE() + INTERVAL 3 MONTH AND in_interventi.deleted_at IS NULL';

        if ($user->anagrafica->isTipo('Tecnico')) {
            $query .= ' AND in_interventi_tecnici.idtecnico = '.prepare($user['idanagrafica']);
        } elseif ($user->anagrafica->isTipo('Cliente')) {
            $query .= ' AND in_interventi.idanagrafica = '.prepare($user['idanagrafica']);
        }

        $rs = $database->fetchArray($query);

        $result = '';

        $result .= "BEGIN:VCALENDAR\n";
        $result .= "VERSION:2.0\n";
        $result .= "PRODID:-// OpenSTAManager\n";

        foreach ($rs as $r) {
            $description = str_replace("\r\n", "\n", strip_tags((string) $r['description']));
            $description = str_replace("\r", "\n", $description);
            $description = str_replace("\n", '\\n', $description);

            $r['summary'] = str_replace("\r\n", "\n", $r['summary']);

            $now = new Carbon();
            $inizio = new Carbon($r['orario_inizio']);
            $fine = new Carbon($r['orario_fine']);

            $result .= "BEGIN:VEVENT\n";
            $result .= 'UID:'.$r['idriga']."\n";
            $result .= 'DTSTAMP:'.$now->format('Ymd\THis')."\n";
            // $result .= 'ORGANIZER;CN='.$azienda.':MAILTO:'.$email."\n";
            $result .= 'DTSTART:'.$inizio->format('Ymd\THis')."\n";
            $result .= 'DTEND:'.$fine->format('Ymd\THis')."\n";
            $result .= 'SUMMARY:'.html_entity_decode($r['summary'])."\n";
            $result .= 'DESCRIPTION:'.html_entity_decode($description, ENT_QUOTES, 'UTF-8')."\n";
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

        // Controllo permessi per il modulo Interventi
        $module = \Models\Module::where('name', 'Interventi')->first();
        $permission = \Modules::getPermission($module->id);
        
        // Verifica se l'utente ha permessi di scrittura per il modulo Interventi
        if (!in_array($permission, ['rw'])) {
            return;
        }

        // Normalizzazione degli interventi a database
        $database->query('UPDATE in_interventi_tecnici SET summary = (SELECT ragione_sociale FROM an_anagrafiche INNER JOIN in_interventi ON an_anagrafiche.idanagrafica=in_interventi.idanagrafica WHERE in_interventi.id=in_interventi_tecnici.idintervento) WHERE summary IS NULL');
        $database->query('UPDATE in_interventi_tecnici SET uid = id WHERE uid IS NULL');
        $database->query('UPDATE in_interventi_tecnici SET description = (SELECT richiesta FROM in_interventi WHERE in_interventi.id=in_interventi_tecnici.idintervento) WHERE description=""');

        // Interpretazione degli eventi
        $idtecnico = $user['idanagrafica'];

        $response = \API\Response::getRequest(true);

        $ical = new \iCalEasyReader();
        $events = $ical->load($response);

        foreach ($events['VEVENT'] as $event) {
            // Individuazione idriga di in_interventi_tecnici
            if (string_contains($event['UID'], '-')) {
                $idriga = 'NEW';
            } else {
                $idriga = $event['UID'];
            }

            $start = is_array($event['DTSTART']) ? $event['DTSTART']['VALUE'] : $event['DTSTART'];
            $end = is_array($event['DTEND']) ? $event['DTEND']['VALUE'] : $event['DTEND'];

            // Timestamp di inizio
            $orario_inizio = \DateTime::createFromFormat('Ymd\\THis', $start)->format(\Intl\Formatter::getStandardFormats()['timestamp']);

            // Timestamp di fine
            $orario_fine = \DateTime::createFromFormat('Ymd\\THis', $end)->format(\Intl\Formatter::getStandardFormats()['timestamp']);

            // Descrizione
            $description = is_array($event['DESCRIPTION']) ? $event['DESCRIPTION']['VALUE']: $event['DESCRIPTION'];
            // Rimozione prefisso tipo "text/html,2":TESTO per ottenere solo TESTO
            if (preg_match('/^[^:]+:(.*)$/', $description, $matches)) {
                $description = $matches[1];
            }
            $description = str_replace('\\r\\n', "\n", $description);
            $description = str_replace('\\n', "\n", $description);

            $summary = trim((string) $event['SUMMARY']);
            $summary = str_replace('\\r\\n', "\n", $summary);
            $summary = str_replace('\\n', "\n", $summary);

            // Creazione nuova attività o modifica attività creata dal calendario
            if ($idriga == 'NEW') {
                $sessione = Sessione::where('uid', $event['UID'])->first();

                if ($sessione) {
                    $sessione->orario_inizio = $orario_inizio;
                    $sessione->orario_fine = $orario_fine;
                    $sessione->summary = $summary;
                    $sessione->description = $description;
                    $sessione->save();
                } else {
                    $anagrafica = Anagrafica::find(setting('Azienda predefinita'));
                    $tipo = $anagrafica->idtipointervento_default ?: TipoSessione::first();
                    $stato = Stato::find(setting('Stato predefinito dell\'attività'));
                    $data_richiesta = Carbon::now();

                    $intervento = Intervento::build($anagrafica, $tipo, $stato, $data_richiesta);
                    $intervento->richiesta = $description;
                    $intervento->save();

                    add_tecnico($intervento->id, $idtecnico, $orario_inizio, $orario_fine);
                    $sessione = Sessione::where('idintervento', $intervento->id)->get()->last();
                    $sessione->summary = $summary;
                    $sessione->description = $description;
                    $sessione->uid = $event['UID'];
                    $sessione->save();
                }
            }

            // Modifica attività creata in OSM
            else {
                $sessione = Sessione::find($idriga);
                $sessione->orario_inizio = $orario_inizio;
                $sessione->orario_fine = $orario_fine;
                $sessione->summary = $summary;
                $sessione->description = $description;
                $sessione->save();
            }
        }
    }
}
