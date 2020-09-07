<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

namespace API;

use API\Exceptions\InternalError;
use API\Exceptions\ResourceNotFound;
use API\Exceptions\ServiceError;
use Auth;
use Exception;
use Filter;
use Models\ApiResource as Resource;

/**
 * Classe per la gestione delle API del progetto.
 *
 * @since 2.4.11
 */
class Response
{
    /** @var array Stati previsti dall'API */
    protected static $status = [
        'ok' => [
            'code' => 200,
            'message' => 'OK',
        ],
        'internalError' => [
            'code' => 400,
            'message' => "Errore interno dell'API",
        ],
        'unauthorized' => [
            'code' => 401,
            'message' => 'Non autorizzato',
        ],
        'notFound' => [
            'code' => 404,
            'message' => 'Non trovato',
        ],
        'externalError' => [
            'code' => 409,
            'message' => 'Errore in un servizio esterno',
        ],
        'serverError' => [
            'code' => 500,
            'message' => 'Errore del server',
        ],
        'incompatible' => [
            'code' => 503,
            'message' => 'Servizio non disponibile',
        ],
    ];

    public static function manage()
    {
        // Gestione della richiesta
        $method = $_SERVER['REQUEST_METHOD'];
        $type = null;
        switch ($method) {
            // Richiesta PUT (modifica elementi)
            case 'PUT':
                $type = 'update';
                break;

            // Richiesta POST (creazione elementi)
            case 'POST':
                $type = 'create';
                break;

            // Richiesta GET (ottenimento elementi)
            case 'GET':
                $type = 'retrieve';
                break;

            // Richiesta DELETE (eliminazione elementi)
            case 'DELETE':
                $type = 'delete';
                break;
        }

        $request = self::getRequest();
        $version = $request['version'];

        // Controllo sull'accesso
        if (!Auth::check() && $request['resource'] != 'login') {
            return self::response([
                'status' => self::$status['unauthorized']['code'],
            ]);
        }

        // Controllo sulla compatibilità dell'API
        if (!self::isCompatible()) {
            return self::response([
                'status' => self::$status['incompatible']['code'],
            ]);
        }

        if ($type == 'retrieve' && empty($request['resource'])) {
            $resources = self::getResources($type, $version)->toArray();
            $list = array_column($resources, 'resource') ?: [];

            return self::response([
                'resources' => $list,
            ]);
        }

        try {
            $manager = new Manager($request['resource'], $type, $version);

            $response = $manager->manage($request);
        } catch (ResourceNotFound $e) {
            return self::error('notFound');
        } catch (InternalError $e) {
            return self::error('internalError');
        } catch (ServiceError $e) {
            return self::error('externalError');
        } catch (Exception $e) {
            return self::error('serverError');
        }

        return self::response($response);
    }

    /**
     * Genera i contenuti di risposta nel caso si verifichi un errore.
     *
     * @param string|int $error
     *
     * @return string
     */
    public static function error($error)
    {
        $keys = array_keys(self::$status);
        $error = (in_array($error, $keys)) ? $error : 'serverError';

        $code = self::$status[$error]['code'];

        http_response_code($code);

        return self::response([
            'status' => $code,
        ]);
    }

    /**
     * Formatta i contenuti della risposta secondo il formato JSON.
     *
     * @param array $array
     *
     * @return string
     */
    public static function response($array)
    {
        if (empty($array['custom'])) {
            // Aggiunta dello status di default
            if (empty($array['status'])) {
                $array['status'] = self::$status['ok']['code'];
            }

            // Aggiunta del messaggio in base allo status
            if (empty($array['message'])) {
                $codes = array_column(self::$status, 'code');
                $messages = array_column(self::$status, 'message');

                $array['message'] = $messages[array_search($array['status'], $codes)];
            }

            $flags = JSON_FORCE_OBJECT;
            // Beautify forzato dei risultati
            if (get('beautify') !== null) {
                $flags |= JSON_PRETTY_PRINT;
            }

            $result = json_encode($array, $flags);
        } else {
            $result = $array['custom'];
        }

        return $result;
    }

    /**
     * Restituisce l'elenco degli stati dell'API.
     *
     * @return array
     */
    public static function getStatus()
    {
        return self::$status;
    }

    /**
     * Controlla se la richiesta effettuata è rivolta all'API.
     *
     * @return bool
     */
    public static function isAPIRequest()
    {
        return getURLPath() == slashes(ROOTDIR.'/api/index.php');
    }

    /**
     * Restituisce i parametri specificati dalla richiesta.
     *
     * @param bool $raw
     *
     * @return array
     */
    public function getRequest($raw = false)
    {
        $request = [];

        if (self::isAPIRequest()) {
            $request = file_get_contents('php://input');

            if (empty($raw)) {
                $request = (array) json_decode($request, true);
                $request = Filter::sanitize($request);

                // Fallback per input standard vuoto (richiesta da browser o upload file)
                if (empty($request)) { // $_SERVER['REQUEST_METHOD'] == 'GET'
                    $request = Filter::getGET();
                }

                if (empty($request['token'])) {
                    $request['token'] = '';
                }

                if (empty($request['version'])) {
                    $request['version'] = 'v1';
                }
            }
        }

        return $request;
    }

    /**
     * Controlla se il database è compatibile con l'API.
     *
     * @return bool
     */
    public static function isCompatible()
    {
        $database = database();

        return version_compare($database->getMySQLVersion(), '5.6.5') >= 0;
    }

    protected static function getResources($type, $version)
    {
        $resources = Resource::where('version', $version)->where('type', $type)->get();

        return $resources;
    }
}
