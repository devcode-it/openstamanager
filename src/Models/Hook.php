<?php

namespace Models;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Common\Model;
use Traits\StoreTrait;

class Hook extends Model
{
    use StoreTrait;

    protected $table = 'zz_hooks';

    protected $cached = null;
    protected $use_cached = null;

    public function getIsCachedAttribute()
    {
        if (!isset($this->use_cached)) {
            $cache = $this->cache;

            $use_cached = false;
            if (!empty($cache)) {
                $date = new Carbon($cache['created_at']);
                $interval = CarbonInterval::make($this->frequency);

                $date = $date->add($interval);

                $now = new Carbon();
                $use_cached = $date->greaterThan($now);
            }

            $this->use_cached = $use_cached;
        }

        return $this->use_cached;
    }

    public function execute()
    {
        $class = $this->class;
        $hook = new $class();

        if ($this->is_cached) {
            $results = $this->cache['results'];

            $results = json_decode($results, true);
        } else {
            $results = $hook->manage();

            $cache = json_encode($results);
            database()->insert('zz_hook_cache', [
                'hook_id' => $this->id,
                'results' => $cache,
            ]);

            $this->cached = null;
            $this->getCacheAttribute();
        }

        return $hook->response($results);
    }

    public function getCacheAttribute()
    {
        if (!isset($this->cached)) {
            $cache = database()->selectOne('zz_hook_cache', '*', ['hook_id' => $this->id], ['id' => 'DESC']);

            $this->cached = $cache;
        }

        return $this->cached;
    }
}
