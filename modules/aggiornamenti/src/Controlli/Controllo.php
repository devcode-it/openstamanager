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

namespace Modules\Aggiornamenti\Controlli;

abstract class Controllo
{
    public $results = [];

    public function addResult($record)
    {
        $this->results[] = array_merge($record, [
            'type' => $this->getType($record),
            'options' => $this->getOptions($record),
        ]);
    }

    public function getResults()
    {
        return $this->results;
    }

    public function getOptions($record)
    {
        return [];
    }

    abstract public function getName();

    abstract public function getType($record);

    abstract public function check();

    public function solve($records, $params = [])
    {
        if (!isset($records[0])) {
            return $this->execute($records, $params);
        }

        $results = [];
        foreach ($records as $record) {
            $results[$record['id']] = $this->execute($record, $params);
        }

        return $results;
    }

    abstract public function execute($records, $params = []);
}
