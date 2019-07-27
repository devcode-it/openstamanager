<?php

namespace Models;

use Auth;
use Common\Model;
use Illuminate\Database\Eloquent\Builder;
use Traits\ManagerTrait;
use Traits\StoreTrait;
use Traits\UploadTrait;

class Module extends Model
{
    use ManagerTrait;
    use UploadTrait;
    use StoreTrait;

    protected $table = 'zz_modules';
    protected $main_folder = 'modules';
    protected $upload_identifier = 'id_module';

    protected $variables = [];

    protected $appends = [
        'permission',
        'option',
    ];

    protected $hidden = [
        'options',
        'options2',
    ];

    public function replacePlaceholders($id_record, $value)
    {
        $replaces = $this->getPlaceholders($id_record);

        $value = str_replace(array_keys($replaces), array_values($replaces), $value);

        return $value;
    }

    public function getPlaceholders($id_record)
    {
        if (!isset($variables[$id_record])) {
            $dbo = $database = database();

            // Lettura delle variabili nei singoli moduli
            $variables = include $this->filepath('variables.php');

            // Sostituzione delle variabili di base
            $replaces = [];
            foreach ($variables as $key => $value) {
                $replaces['{'.$key.'}'] = $value;
            }

            $this->variables[$id_record] = $replaces;
        }

        return $this->variables[$id_record];
    }

    /**
     * Restituisce i permessi relativi all'account in utilizzo.
     *
     * @return string
     */
    public function getPermissionAttribute()
    {
        if (Auth::user()->is_admin) {
            return 'rw';
        }

        $group = Auth::user()->group->id;

        $pivot = $this->pivot ?: $this->groups->first(function ($item) use ($group) {
            return $item->id == $group;
        })->pivot;

        return $pivot->permessi ?: '-';
    }

    /**
     * Restituisce i permessi relativi all'account in utilizzo.
     *
     * @return string
     */
    public function getViewsAttribute()
    {
        $user = Auth::user();

        $views = database()->fetchArray('SELECT * FROM `zz_views` WHERE `id_module` = :module_id AND
        `id` IN (
            SELECT `id_vista` FROM `zz_group_view` WHERE `id_gruppo` = (
                SELECT `idgruppo` FROM `zz_users` WHERE `id` = :user_id
            ))
        ORDER BY `order` ASC', [
            'module_id' => $this->id,
            'user_id' => $user->id,
        ]);

        return $views;
    }

    public function getOptionAttribute()
    {
        return !empty($this->options2) ? $this->options2 : $this->options;
    }

    /* Relazioni Eloquent */

    public function plugins()
    {
        return $this->hasMany(Plugin::class, 'idmodule_to');
    }

    public function prints()
    {
        return $this->hasMany(PrintTemplate::class, 'id_module');
    }

    public function mailTemplates()
    {
        return $this->hasMany(MailTemplate::class, 'id_module');
    }

    public function views()
    {
        return $this->hasMany(View::class, 'id_module');
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'zz_permissions', 'idmodule', 'idgruppo')->withPivot('permessi');
    }

    public function clauses()
    {
        return $this->hasMany(Clause::class, 'idmodule');
    }

    /* Gerarchia */

    public function children()
    {
        return $this->hasMany(self::class, 'parent')->withoutGlobalScope('enabled')
            ->orderBy('order');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent')->withoutGlobalScope('enabled');
    }

    public function allParents()
    {
        return $this->parent()->with('allParents');
    }

    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }

    public static function getHierarchy()
    {
        return self::with('allChildren')
            ->withoutGlobalScope('enabled')
            ->whereNull('parent')
            ->orderBy('order')
            ->get();
    }

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
}
