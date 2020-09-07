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

namespace Hooks;

use Models\Hook;

abstract class Manager
{
    protected $hook = null;

    public function __construct(Hook $hook)
    {
        $this->hook = $hook;
    }

    /**
     * Restituisce le informazioni sull'esecuzione dell'hook.
     *
     * @return mixed
     */
    abstract public function execute();

    /**
     * Restituisce le informazioni per la visualizzazione dell'hook.
     *
     * @return array
     */
    abstract public function response();

    /**
     * Restituisce se l'hook è un singletion, cioè deve essere richiamato solo da una istanza di navigazione.
     *
     * @return bool
     */
    public function isSingleton()
    {
        return false;
    }

    /**
     * Restituisce se l'hook ha bisogno di una esecuzione.
     *
     * @return bool
     */
    abstract public function needsExecution();

    /**
     * Gestisce la chiamata per l'esecuzione dell'hook.
     *
     * @return array|mixed
     */
    public function manage()
    {
        if (!$this->needsExecution()) {
            return [];
        }

        $results = $this->execute();

        return [];
    }

    /**
     * Restituisce l'hook Eloquent relativo alla classe.
     *
     * @return Hook|null
     */
    protected function getHook()
    {
        return $this->getHook();
    }
}
