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
use Auth;
use Models\ApiResource as Resource;
use PDOException;

/**
 * Classe per la gestione delle API del progetto.
 *
 * @since 2.4.11
 */
class Manager
{
    protected $resource;
    protected $type;

    /**
     * @throws \InvalidArgumentException
     */
    public function __construct($resource, $type, $version)
    {
        $object = Resource::where('version', $version)
            ->where('type', $type)
            ->where('resource', $resource)
            ->first();

        if (empty($object)) {
            throw new ResourceNotFound();
        }

        $this->resource = $object;
        $this->type = $type;
    }

    public function manage($request)
    {
        $type = $this->type;

        $response = $this->{$type}($request);

        return $response;
    }

    /**
     * Gestisce le richieste di informazioni riguardanti gli elementi esistenti.
     *
     * @param array $request
     *
     * @return string
     */
    public function retrieve($request)
    {
        $user = Auth::user();

        $where = [];
        $order = [];

        // Selezione personalizzata
        $select = !empty($request['display']) ? explode(',', substr($request['display'], 1, -1)) : null;

        // Ricerca personalizzata
        $values = isset($request['filter']) ? (array) $request['filter'] : [];
        foreach ($values as $key => $value) {
            // Rimozione delle parentesi
            $value = substr($value, 1, -1);

            // Individuazione della tipologia (array o string)
            $where[$key] = string_contains($value, ',') ? explode(',', $value) : $value;
        }

        // Ordinamento personalizzato
        $values = isset($request['order']) ? (array) $request['order'] : [];
        foreach ($values as $value) {
            $pieces = explode('|', $value);
            $order[] = empty($pieces[1]) ? $pieces[0] : [$pieces[0] => $pieces[1]];
        }

        // Paginazione automatica dell'API
        $page = isset($request['page']) ? (int) $request['page'] : 0;
        $length = setting('Lunghezza pagine per API');

        $data = array_merge($request, [
            'user' => $user,
            'select' => $select,
            'where' => $where,
            'order' => $order,
            'page' => $page,
            'length' => $length,
        ]);

        $response = $this->getResponse($data);
        $parameters = $response['parameters'];

        $table = $response['table'];
        $select = $response['select'] ?: $select;
        $select = $select ?: '*';

        $query = $response['query'];

        try {
            $database = database();

            // Generazione automatica delle query
            if (!empty($table)) {
                // Date di interesse
                if (!empty($request['upd'])) {
                    $where['#updated_at'] = 'updated_at >= '.prepare($request['upd']);
                }
                if (!empty($request['crd'])) {
                    $where['#created_at'] = 'created_at >= '.prepare($request['crd']);
                }

                // Query per ottenere le informazioni
                $query = $database->select($table, $select, $where, $order, [], true);

                foreach ($where as $key => $value) {
                    $parameters[] = $value;
                }
            }

            if (!empty($query)) {
                $response = [];

                $response['records'] = $database->fetchArray($query.' LIMIT '.($page * $length).', '.$length, $parameters);
                $count = $database->fetchNum($query, $parameters);

                $response['total-count'] = $count;
                $response['pages'] = intval(ceil($count / $length));
            }
        } catch (PDOException $e) {
            // Log dell'errore
            $logger = logger();
            $logger->addRecord(\Monolog\Logger::ERROR, $e);

            throw new InternalError();
        }

        return $response;
    }

    /**
     * Gestisce le richieste di creazione nuovi elementi.
     *
     * @param array $request
     *
     * @return string
     */
    public function create($request)
    {
        return $this->getResponse($request);
    }

    /**
     * Gestisce le richieste di aggiornamento di elementi esistenti.
     *
     * @param array $request
     *
     * @return string
     */
    public function update($request)
    {
        return $this->getResponse($request);
    }

    /**
     * Gestisce le richieste di eliminazione di elementi esistenti.
     *
     * @param array $request
     *
     * @return string
     */
    public function delete($request)
    {
        return $this->getResponse($request);
    }

    public function getResponse($request)
    {
        $class = $this->resource->class;

        if (!class_exists($class)) {
            throw new ResourceNotFound();
        }

        $object = new $class();
        $method = $this->type;

        // Operazioni di inizializzazione
        $block = $object->open($request);
        if (!empty($block)) {
            throw new ResourceNotFound();
        }

        $database = database();
        $database->beginTransaction();

        // Operazioni della risorsa
        $response = $object->{$method}($request);

        $database->commitTransaction();

        // Operazioni di completamento
        $object->close($request, $response);

        return $response;
    }
}
