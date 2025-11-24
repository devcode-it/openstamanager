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

namespace API\App\v1;

use API\Interfaces\RetrieveInterface;
use API\Resource;

/**
 * Risorsa API per la gestione delle notifiche push basate sui log delle operazioni.
 * Recupera le operazioni sui moduli interventi e genera notifiche per i tecnici interessati.
 */
class GestioneNotifiche extends Resource implements RetrieveInterface
{
    /**
     * Operazioni da monitorare per le notifiche.
     */
    private const OPERAZIONI_MONITORATE = [
        'edit_sessione',
        'update_inline_sessione',
        'delete_sessione',
        'add_sessione',
        'cambio_stato_intervento',
    ];

    /**
     * Recupera le notifiche da inviare basate sui log delle operazioni.
     *
     * @param array $request Richiesta contenente updated_at
     *
     * @return array Array di notifiche con token e messaggi
     */
    public function retrieve($request)
    {
        $updated_at = $request['updated_at'] ?? null;

        if (empty($updated_at)) {
            return [
                'notifications' => [],
                'message' => 'Parametro updated_at richiesto',
            ];
        }

        $database = database();

        // Recupera le operazioni monitorate dalla data specificata
        $operazioni = $this->getOperazioniDaNotificare($database, $updated_at);

        if (empty($operazioni)) {
            return [
                'notifications' => [],
                'message' => 'Nessuna operazione da notificare',
            ];
        }

        // Genera le notifiche per ogni operazione
        $notifiche = [];
        foreach ($operazioni as $operazione) {
            $notifiche_operazione = $this->generaNotifichePerOperazione($database, $operazione);
            $notifiche = array_merge($notifiche, $notifiche_operazione);
        }

        return [
            'notifications' => $notifiche,
            'total_count' => count($notifiche),
            'operations_processed' => count($operazioni),
        ];
    }

    /**
     * Recupera le operazioni da notificare dalla data specificata.
     *
     * @param object $database   Connessione database
     * @param string $updated_at Data di riferimento
     *
     * @return array Array delle operazioni
     */
    private function getOperazioniDaNotificare($database, $updated_at)
    {
        $operazioni_list = "'".implode("','", self::OPERAZIONI_MONITORATE)."'";

        $query = 'SELECT
            zz_operations.*,
            zz_modules_lang.title as module_name
        FROM zz_operations
        LEFT JOIN zz_modules ON zz_operations.id_module = zz_modules.id
        LEFT JOIN zz_modules_lang ON zz_modules.id = zz_modules_lang.id_record
            AND zz_modules_lang.id_lang = '.prepare(\Models\Locale::getDefault()->id)."
        WHERE zz_operations.op IN ({$operazioni_list})
            AND zz_operations.created_at > ".prepare($updated_at)."
            AND zz_modules.name = 'Interventi'
        ORDER BY zz_operations.created_at DESC";

        return $database->fetchArray($query);
    }

    /**
     * Genera le notifiche per una specifica operazione.
     *
     * @param object $database   Connessione database
     * @param array  $operazione Dati dell'operazione
     *
     * @return array Array delle notifiche generate
     */
    private function generaNotifichePerOperazione($database, $operazione)
    {
        $notifiche = [];

        // Recupera le informazioni complete dell'intervento
        $intervento_info = $this->getInterventoInfo($database, $operazione['id_record']);

        if (empty($intervento_info)) {
            return $notifiche;
        }

        // Recupera i tecnici interessati all'intervento
        $tecnici_interessati = $this->getTecniciInteressati($database, $operazione['id_record']);

        if (empty($tecnici_interessati)) {
            return $notifiche;
        }

        // Genera il messaggio in base al tipo di operazione
        $messaggio = $this->generaMessaggio($operazione, $intervento_info);

        if (empty($messaggio)) {
            return $notifiche;
        }

        // Recupera i token FCM per ogni tecnico interessato
        foreach ($tecnici_interessati as $tecnico) {
            if (empty($tecnico['user_id'])) {
                continue; // Salta i tecnici senza utente associato
            }

            $tokens = $this->getTokensFCM($database, $tecnico['user_id']);

            foreach ($tokens as $token) {
                $notifiche[] = [
                    'token' => $token['token'],
                    'platform' => $token['platform'],
                    'message' => $messaggio,
                    'title' => 'OpenSTAManager',
                    'data' => [
                        'operation_id' => $operazione['id'],
                        'operation_type' => $operazione['op'],
                        'intervento_id' => $operazione['id_record'],
                        'intervento_codice' => $intervento_info['codice'],
                        'tecnico_id' => $tecnico['idtecnico'],
                        'tecnico_nome' => $tecnico['ragione_sociale'],
                        'timestamp' => $operazione['created_at'],
                    ],
                ];
            }
        }

        return $notifiche;
    }

    /**
     * Recupera le informazioni complete di un intervento.
     *
     * @param object $database      Connessione database
     * @param int    $id_intervento ID dell'intervento
     *
     * @return array|null Informazioni dell'intervento
     */
    private function getInterventoInfo($database, $id_intervento)
    {
        $query = 'SELECT
            in_interventi.id,
            in_interventi.codice,
            in_interventi.richiesta,
            in_interventi.descrizione,
            in_interventi.idstatointervento,
            in_statiintervento_lang.title as stato_descrizione,
            an_anagrafiche.ragione_sociale as cliente,
            an_anagrafiche.idanagrafica as id_cliente
        FROM in_interventi
        LEFT JOIN in_statiintervento ON in_interventi.idstatointervento = in_statiintervento.id
        LEFT JOIN in_statiintervento_lang ON in_statiintervento.id = in_statiintervento_lang.id_record
            AND in_statiintervento_lang.id_lang = '.prepare(\Models\Locale::getDefault()->id).'
        LEFT JOIN an_anagrafiche ON in_interventi.idanagrafica = an_anagrafiche.idanagrafica
        WHERE in_interventi.id = '.prepare($id_intervento);

        return $database->fetchOne($query);
    }

    /**
     * Recupera i tecnici interessati a un intervento specifico.
     *
     * @param object $database      Connessione database
     * @param int    $id_intervento ID dell'intervento
     *
     * @return array Array dei tecnici interessati
     */
    private function getTecniciInteressati($database, $id_intervento)
    {
        $query = 'SELECT DISTINCT
            in_interventi_tecnici.idtecnico,
            an_anagrafiche.ragione_sociale,
            zz_users.id as user_id
        FROM in_interventi_tecnici
        LEFT JOIN an_anagrafiche ON in_interventi_tecnici.idtecnico = an_anagrafiche.idanagrafica
        LEFT JOIN zz_users ON an_anagrafiche.idanagrafica = zz_users.idanagrafica
        WHERE in_interventi_tecnici.idintervento = '.prepare($id_intervento).'
            AND zz_users.id IS NOT NULL';

        return $database->fetchArray($query);
    }

    /**
     * Recupera i token FCM per un utente specifico.
     *
     * @param object $database Connessione database
     * @param int    $user_id  ID dell'utente
     *
     * @return array Array dei token FCM
     */
    private function getTokensFCM($database, $user_id)
    {
        $query = 'SELECT token, platform, device_info
        FROM zz_app_tokens
        WHERE id_user = '.prepare($user_id)."
            AND token IS NOT NULL
            AND token != ''";

        return $database->fetchArray($query);
    }

    /**
     * Genera il messaggio di notifica in base al tipo di operazione.
     *
     * @param array $operazione      Dati dell'operazione
     * @param array $intervento_info Informazioni complete dell'intervento
     *
     * @return string Messaggio di notifica
     */
    private function generaMessaggio($operazione, $intervento_info)
    {
        $intervento_codice = $intervento_info['codice'] ?? 'N/A';
        $cliente = $intervento_info['cliente'] ?? 'N/A';

        switch ($operazione['op']) {
            case 'cambio_stato_intervento':
                $stato_nuovo = $intervento_info['stato_descrizione'] ?? 'N/A';

                return "L'attività {$intervento_codice} è passata nello stato: {$stato_nuovo}";

            case 'add_sessione':
                return "Nuova sessione creata per l'attività {$intervento_codice}";

            case 'update_inline_sessione':
            case 'edit_sessione':
                return "Sessione modificata per l'attività {$intervento_codice}";

            case 'delete_sessione':
                return "Sessione eliminata per l'attività {$intervento_codice}";

            default:
                return "Aggiornamento per l'attività {$intervento_codice}";
        }
    }
}
