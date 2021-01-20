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

namespace Hooks;

use Models\Cache;
use Models\Hook;

abstract class CachedManager extends Manager
{
    protected $cache = null;

    public function __construct(Hook $hook)
    {
        parent::__construct($hook);

        $this->cache = Cache::pool($this->getCacheName());
    }

    abstract public function cacheData();

    abstract public function getCacheName();

    public function needsExecution()
    {
        return !$this->isCached();
    }

    public function execute()
    {
        if (!$this->isCached()) {
            $data = $this->cacheData();

            $this->getCache()->set($data);
        }

        return $this->getCache()->content;
    }

    public function getCache()
    {
        return $this->cache;
    }

    public function isCached()
    {
        return $this->getCache()->isValid();
    }
}
