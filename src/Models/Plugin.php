<?php

namespace Models;

use App;
use Traits\RecordTrait;
use Traits\UploadTrait;
use Traits\StoreTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Plugin extends Model
{
    use RecordTrait, UploadTrait, StoreTrait;

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
    }

    /**
     * Restituisce i permessi relativi all'account in utilizzo.
     *
     * @return string
     */
    public function getPermissionAttribute()
    {
        return $this->originalModule()->permission;
    }

    public function getOptionAttribute()
    {
        return !empty($this->options) ? $this->options : $this->options2;
    }

    public function getOptionsAttribute($value)
    {
        return App::replacePlaceholder($value);
    }

    public function getOptions2Attribute($value)
    {
        return App::replacePlaceholder($value);
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

        $directory = 'modules/'.$this->originalModule()->directory.'|custom|/plugins';

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
        return $this->belongsTo(Module::class, 'idmodule_from')->first();
    }

    public function module()
    {
        return $this->belongsTo(Module::class, 'idmodule_to')->first();
    }
}
