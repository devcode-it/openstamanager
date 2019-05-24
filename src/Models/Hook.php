<?php

namespace Models;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Common\Model;
use Illuminate\Database\Eloquent\Builder;
use Traits\StoreTrait;

class Hook extends Model
{
    use StoreTrait;

    protected $table = 'zz_hooks';

    protected $appends = [
        'permission',
    ];

    protected $cached = null;
    protected $use_cached = null;

    /**
     * Restituisce i permessi relativi all'account in utilizzo.
     *
     * @return string
     */
    public function getPermissionAttribute()
    {
        return $this->module->permission;
    }

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

            // Interpretazione della cache
            $results = json_decode($results, true);
        } else {
            $results = $hook->manage();

            // Rimozione cache precedente
            $database = database();
            $database->delete('zz_hook_cache', [
                'hook_id' => $this->id,
            ]);

            // Aggiunta del risultato come cache
            $cache = json_encode($results);
            $database->insert('zz_hook_cache', [
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

    /* Relazioni Eloquent */

    public function module()
    {
        return $this->belongsTo(Module::class, 'id_module');
    }

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('enabled', function (Builder $builder) {
            $builder->where('enabled', true);
        });
    }
}
