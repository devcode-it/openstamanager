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

use API\App\AppResource;

/**
 * Risorsa API per la gestione dei token FCM (Firebase Cloud Messaging) dei dispositivi.
 * Permette all'app di salvare e aggiornare i token FCM per l'invio di notifiche push.
 */
class FcmTokens extends AppResource
{
    public function getCleanupData($last_sync_at)
    {
        return [];
    }

    public function getModifiedRecords($last_sync_at)
    {
        return [];
    }

    public function retrieveRecord($id)
    {
        return [];
    }

    public function createRecord($data)
    {
        $database = database();
        $user = $this->getUser();

        $fcm_token = $data['token'];
        $platform = $data['platform'] ?? null;
        $device_info = $data['device_info'] ?? null;
        $user_id = $user['id'];

        // Verifica se esiste già un token per questo utente
        $existing_token = $database->fetchOne('SELECT * FROM `zz_app_tokens` WHERE `id_user` = :user_id', [
            ':user_id' => $user_id,
        ]);

        if ($existing_token) {
            // Aggiorna il token esistente
            $database->update('zz_app_tokens', [
                'token' => $fcm_token,
                'platform' => $platform,
                'device_info' => $device_info ? json_encode($device_info) : null,
            ], ['id' => $existing_token['id']]);

            $token_id = $existing_token['id'];
        } else {
            // Crea un nuovo record
            $database->insert('zz_app_tokens', [
                'id_user' => $user_id,
                'token' => $fcm_token,
                'platform' => $platform,
                'device_info' => $device_info ? json_encode($device_info) : null,
            ]);

            $token_id = $database->lastInsertedID();
        }

        return [
            'id' => $token_id,
        ];
    }

    /**
     * Aggiorna un token FCM esistente.
     *
     * @return array Risposta dell'operazione
     */
    public function updateRecord($data)
    {
        // Per i token FCM, l'operazione di update è identica a create
        // poiché gestiamo automaticamente la creazione o l'aggiornamento
        return $this->create($data);
    }
}
