<?php

namespace Models;

use Illuminate\Database\Eloquent\Model;
use App;

class Plugin extends Model
{
    protected $table = 'zz_plugins';

    protected $appends = [
        'option',
    ];

    protected $hidden = [
        'options',
        'options2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('enabled', function (Builder $builder) {
            $builder->where('enabled', true);
        });
    }

    public function getModuleDirAttribute()
    {
        return $this->originalModule()->directory;
    }

    public function getOptionAttribute()
    {
        return !empty($this->options) ? $this->options : $this->options2;
    }

    public function getOptionsAttribute($value)
    {
        return App::replacePlaceholder($value, app('parent_id'));
    }

    public function getOptions2Attribute($value)
    {
        return App::replacePlaceholder($value, app('parent_id'));
    }

    /* Relazioni Eloquent */

    public function originalModule()
    {
        return $this->belongsTo(Module::class, 'idmodule_from')->first();
    }

    public function module()
    {
        return $this->belongsTo(Module::class, 'idmodule_to')->first();
    }
}
