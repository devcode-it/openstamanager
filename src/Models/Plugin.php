<?php

namespace Models;

use App;
use Traits\RecordTrait;
use Traits\UploadTrait;
use Traits\StoreTrait;
use Traits\PermissionTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Plugin extends Model
{
    use RecordTrait, UploadTrait, StoreTrait, PermissionTrait;

    protected $table = 'zz_plugins';
    protected $main_folder = 'plugins';

    protected $appends = [
        'permission',
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

        static::addGlobalScope('permission', function (Builder $builder) {
            $builder->with('groups');
        });
    }

    public function getOptionAttribute()
    {
        return !empty($this->options2) ? $this->options2 : $this->options;
    }

    /* Metodi personalizzati */

    /**
     * Restituisce l'eventuale percorso personalizzato per il file di creazione dei record.
     *
     * @return string
     */
    public function getCustomAddFile()
    {
        if (empty($this->script)) {
            return;
        }

        $directory = 'modules/'.$this->originalModule->directory.'|custom|/plugins';

        return App::filepath($directory, $this->script);
    }

    /**
     * Restituisce l'eventuale percorso personalizzato per il file di modifica dei record.
     *
     * @return string
     */
    public function getCustomEditFile()
    {
        if (empty($this->script)) {
            return;
        }

        return $this->getAddFile();
    }

    /* Relazioni Eloquent */

    public function originalModule()
    {
        return $this->belongsTo(Module::class, 'idmodule_from');
    }

    public function module()
    {
        return $this->belongsTo(Module::class, 'idmodule_to');
    }

    public function groups()
    {
        return $this->morphToMany(Group::class, 'permission', 'zz_permissions', 'external_id', 'group_id')->where('permission_level', '!=', '-')->withPivot('permission_level');
    }
}
