<?php

namespace Hooks;

use Models\Cache;
use Models\Hook;

abstract class CachedManager extends Manager
{
    protected $cache = null;

    public function __construct(Hook $hook)
    {
        parent::__construct($hook);

        $this->cache = Cache::get($this->getCacheName());
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
