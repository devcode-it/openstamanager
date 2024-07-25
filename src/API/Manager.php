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

namespace API;

use API\Exceptions\InternalError;
use API\Exceptions\ResourceNotFound;
use Models\ApiResource as Resource;

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
        $user = \Auth::user();

        $where = [];
        $order = [];
        $whereraw = [];
        // Selezione campi personalizzati
        // Esempio:
        // display=[id,ragione_sociale,telefono]
        $select = !empty($request['display']) ? explode(',', substr((string) $request['display'], 1, -1)) : null;

        // Filtri personalizzati
        // Esempio:
        // filter[ragione_sociale]=[Mario Rossi]&filter[telefono]=[0429%]
        $values = isset($request['filter']) ? (array) $request['filter'] : [];
        foreach ($values as $key => $value) {
            // Individuazione della tipologia (array o string)
            $value = trim((string) $value, '[');
            $value = trim($value, ']');
            $values = explode(',', $value);

            foreach ($values as $value) {
                // Filtro per LIKE se il valore contiene %
                if (string_contains($value, '%')) {
                    $where[] = [
                        $key,
                        'LIKE',
                        $value,
                    ];
                }

                // Filtro preciso se il valore non contiene %
                else {
                    $where[] = [
                        $key,
                        '=',
                        $value,
                    ];
                }
            }
        }

        // Ordinamento personalizzato
        // Esempi:
        // order=[ragione_sociale]
        // order=[ragione_sociale|asc]
        // order=[ragione_sociale|desc]
        // order=[ragione_sociale]&order=[telefono]
        $values = isset($request['order']) ? (array) $request['order'] : [];
        foreach ($values as $value) {
            $value = trim((string) $value, '[');
            $value = trim($value, ']');
            $pieces = explode('|', $value);
            $order = empty($pieces[1]) ? $pieces[0] : [$pieces[0] => $pieces[1]];
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
            'whereraw' => $whereraw,
        ]);

        $response = $this->getResponse($data);
        $parameters = $response['parameters'];

        $table = $response['table'];
        $joins = $response['joins'];
        $group = $response['group'];

        if (!empty($response['where'])) {
            $where = array_merge($where, $response['where']);
        }
        if (!empty($response['whereraw'])) {
            $whereraw = $response['whereraw'];
        }

        if (empty($select)) {
            $select = $response['select'] ?: $select;
            $select = $select ?: '*';
        }

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

                $query = $database->table($table);

                // Query per ottenere le informazioni
                foreach ($select as $s) {
                    $query->selectRaw($s);
                }

                foreach ($joins as $join) {
                    if (count($join) >= 3) {
                        $query->leftJoin($join[0], function($joinClause) use ($join) {
                            $joinClause->on($join[1], $join[2]);
                            
                            // Aggiungi condizioni aggiuntive se ci sono abbastanza elementi in $join
                            if (isset($join[3])) {
                                $joinClause->whereRaw($join[3] . ' = ?', [$join[4]]);
                            }
                            
                        });
                    }
                }

                if (!empty($where)) {
                    $query->where($where);
                }

                foreach ($whereraw as $w) {
                    $query->whereRaw($w);
                }

                if (!empty($group)) {
                    $query->groupBy($group);
                }

                $count = $query->count();

                // Composizione query finale
                $response = [];

                $response['records'] = $database->select($table, $select, $joins, $where, $order, [$page * $length, $length], null, $group, $whereraw);
                $response['total-count'] = $count;
            }

            // Query diretta
            elseif (!empty($query)) {
                $response = [];

                $response['records'] = $database->fetchArray($query.' LIMIT '.($page * $length).', '.$length, [$parameters]);
                $count = $database->fetchNum($query);

                $response['total-count'] = $count;
            }

            if (empty($response['pages'])) {
                $response['pages'] = intval(ceil($response['total-count'] / $length));
            }
        } catch (\PDOException $e) {
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

        try {
            $database->commitTransaction();
        } catch (\PDOException) {
        }

        // Operazioni di completamento
        $object->close($request, $response);

        return $response;
    }
}
