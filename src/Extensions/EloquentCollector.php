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

namespace Extensions;

use Illuminate\Database\Capsule\Manager;
use PDO;

class EloquentCollector extends \DebugBar\DataCollector\PDO\PDOCollector
{
    protected $capsule;

    public function __construct($capsule)
    {
        parent::__construct();
        $this->capsule = $capsule;
        $this->addConnection($this->getTraceablePdo(), 'Eloquent PDO');
    }

    // Override
    public function getName()
    {
        return 'eloquent_pdo';
    }

    // Override
    public function getWidgets()
    {
        return [
            'eloquent' => [
                'icon' => 'inbox',
                'widget' => 'PhpDebugBar.Widgets.SQLQueriesWidget',
                'map' => 'eloquent_pdo',
                'default' => '[]',
            ],
            'eloquent:badge' => [
                'map' => 'eloquent_pdo.nb_statements',
                'default' => 0,
            ],
        ];
    }

    /**
     * @return Manager;
     */
    protected function getEloquentCapsule()
    {
        return $this->capsule;
    }

    /**
     * @return PDO
     */
    protected function getEloquentPdo()
    {
        return $this->getEloquentCapsule()->getConnection()->getPdo();
    }

    /**
     * @return \DebugBar\DataCollector\PDO\TraceablePDO
     */
    protected function getTraceablePdo()
    {
        return new \DebugBar\DataCollector\PDO\TraceablePDO($this->getEloquentPdo());
    }
}
