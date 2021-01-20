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

namespace API\Common;

use API\Interfaces\CreateInterface;
use API\Interfaces\RetrieveInterface;
use API\Resource;
use Carbon\Carbon;
use Models\Cache;
use Tasks\Log;

class Task extends Resource implements RetrieveInterface, CreateInterface
{
    public function retrieve($request)
    {
        $logs = Log::latest()
            ->take(1000)->get()
            ->groupBy('task.name');

        return [
            'results' => $logs->toArray(),
        ];
    }

    public function create($request)
    {
        $database = database();

        // Rimozione della registrazione del cron attuale
        $ultima_esecuzione = Cache::pool('Ultima esecuzione del cron');
        $ultima_esecuzione->set(null);

        // Segnalazione della chiusura al cron attuale
        $cron_id = Cache::pool('ID del cron');
        $cron_id->set(null);

        // Rimozione dell'eventuale blocco sul cron
        $disattiva = Cache::pool('Disabilita cron');
        $disattiva->set(null);

        // Salvataggio delle modifiche
        $database->commitTransaction();

        // Attesa della conclusione per il cron precedente
        $in_esecuzione = Cache::pool('Cron in esecuzione');
        while ($in_esecuzione->content) {
            $timestamp = (new Carbon())->addMinutes(1)->getTimestamp();
            time_sleep_until($timestamp);

            $in_esecuzione->refresh();
        }

        // Chiamata al cron per l'avvio
        $this->request();

        // Riavvio transazione
        $database->beginTransaction();
    }

    /**
     * Richiesta HTTP fire-and-forget.
     *
     * @source https://cwhite.me/blog/fire-and-forget-http-requests-in-php
     */
    protected function request()
    {
        $endpoint = base_url().'/cron.php';
        $postData = json_encode([]);

        $endpointParts = parse_url($endpoint);
        $endpointParts['path'] = $endpointParts['path'] ?: '/';
        $endpointParts['port'] = $endpointParts['port'] ?: $endpointParts['scheme'] === 'https' ? 443 : 80;

        $contentLength = strlen($postData);

        $request = "POST {$endpointParts['path']} HTTP/1.1\r\n";
        $request .= "Host: {$endpointParts['host']}\r\n";
        $request .= "User-Agent: OpenSTAManager API v1\r\n";
        $request .= "Authorization: Bearer api_key\r\n";
        $request .= "Content-Length: {$contentLength}\r\n";
        $request .= "Content-Type: application/json\r\n\r\n";
        $request .= $postData;

        $prefix = substr($endpoint, 0, 8) === 'https://' ? 'tls://' : '';

        $socket = fsockopen($prefix.$endpointParts['host'], $endpointParts['port']);
        fwrite($socket, $request);
        fclose($socket);
    }
}
