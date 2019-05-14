<?php

namespace Models;

use App;
use Common\Model;
use Illuminate\Database\Eloquent\Builder;
use Traits\ManagerTrait;
use Traits\StoreTrait;
use Traits\UploadTrait;

class Plugin extends Model
{
    use ManagerTrait, StoreTrait;
    use UploadTrait {
        getUploadDirectoryAttribute as protected defaultUploadDirectory;
    }

    protected $table = 'zz_plugins';
    protected $main_folder = 'plugins';
    protected $upload_identifier = 'id_plugin';

    protected $appends = [
        'permission',
        'option',
    ];

    protected $hidden = [
        'options',
        'options2',
    ];

    /**
     * Restituisce i permessi relativi all'account in utilizzo.
     *
     * @return string
     */
    public function getPermissionAttribute()
    {
        return $this->originalModule->permission;
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

    /**
     * Restituisce il percorso per il salvataggio degli upload.
     *
     * @return string
     */
    public function getUploadDirectoryAttribute()
    {
        if (!empty($this->script)) {
            return $this->uploads_directory.'/'.basename($this->script, '.php');
        }

        return $this->defaultUploadDirectory();
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

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('enabled', function (Builder $builder) {
            $builder->where('enabled', true);
        });
    }
}
