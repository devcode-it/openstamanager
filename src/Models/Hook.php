<?php

namespace Models;

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

    /**
     * Restituisce i permessi relativi all'account in utilizzo.
     *
     * @return string
     */
    public function getPermissionAttribute()
    {
        return $this->module->permission;
    }

    public function execute()
    {
        $class = $this->class;
        $hook = new $class();

        $this->processing = true;
        $this->save();

        $data = $hook->manage();
        $results = $hook->response($data);

        $this->processing = false;
        $this->save();

        return $results;
    }

    public function prepare()
    {
        $class = $this->class;
        $hook = new $class();

        $results = $hook->prepare();

        return $results;
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
