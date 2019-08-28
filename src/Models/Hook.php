<?php

namespace Models;

use Common\Model;
use Hooks\Manager;
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
        return $this->module ? $this->module->permission : 'rw';
    }

    public function execute()
    {
        $class = $this->class;
        $hook = new $class();

        if (!$hook instanceof Manager) {
            return [
                'show' => false,
            ];
        }

        return $hook->manage();
    }

    public function prepare()
    {
        $class = $this->class;
        $hook = new $class();

        return $hook->prepare();
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
