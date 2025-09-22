<?php

/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Models;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Checklists\Traits\ChecklistTrait;
use Modules\Emails\Template;
use Traits\Components\NoteTrait;
use Traits\Components\UploadTrait;
use Traits\LocalPoolTrait;
use Traits\ManagerTrait;
use Traits\RecordTrait;

class Module extends Model
{
    use SimpleModelTrait;
    use ManagerTrait;
    use UploadTrait;
    use LocalPoolTrait;
    use NoteTrait;
    use ChecklistTrait;
    use RecordTrait;

    protected $table = 'zz_modules';

    protected static $translated_fields = [
        'title',
    ];

    protected $main_folder = 'modules';
    protected $component_identifier = 'id_module';

    protected $variables = [];

    protected $appends = [
        'permission',
        'option',
    ];

    protected $hidden = [
        'options',
        'options2',
    ];

    public function replacePlaceholders($id_record, $value, $options = [])
    {
        $replaces = $this->getPlaceholders($id_record, $options);

        $value = str_replace(array_keys($replaces), array_values($replaces), $value);

        return $value;
    }

    public function getPlaceholders($id_record, $options = [])
    {
        if (!isset($this->variables[$id_record])) {
            $dbo = $database = database();

            // Lettura delle variabili nei singoli moduli
            $path = $this->filepath('variables.php');
            if (!empty($path)) {
                $variables = include $path;
            }

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
        if (\Auth::user()->is_admin) {
            return 'rw';
        }

        $group = \Auth::user()->group->id;

        $pivot = $this->pivot ?: $this->groups->first(fn ($item) => $item->id == $group)->pivot;

        return $pivot->permessi ?: '-';
    }

    /**
     * Restituisce i permessi relativi all'account in utilizzo.
     *
     * @return string
     */
    public function getViewsAttribute()
    {
        $user = \Auth::user();

        $views = database()->fetchArray('SELECT 
            `zz_views`.*, 
            `zz_views_lang`.*
        FROM 
            `zz_views` 
            LEFT JOIN `zz_views_lang` ON (`zz_views`.`id` = `zz_views_lang`.`id_record` AND `zz_views_lang`.`id_lang` = '.prepare(\Models\Locale::getDefault()->id).') 
            LEFT JOIN `zz_group_view` ON `zz_views`.`id` = `zz_group_view`.`id_vista`
            LEFT JOIN `zz_users` ON `zz_users`.`idgruppo` = `zz_group_view`.`id_gruppo`
        WHERE 
            `id_module`='.$this->id.' AND
            `zz_users`.`id` = '.$user->id.'
        ORDER BY 
            `order` ASC');
            
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

    public function Templates()
    {
        return $this->hasMany(Template::class, 'id_module');
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
        return $this->hasMany(self::class, 'parent')
            ->withoutGlobalScope('enabled')
            ->selectRaw('zz_modules.*, zz_modules_lang.title as title')
            ->join('zz_modules_lang', function ($join) {
                $join->on('zz_modules.id', '=', 'zz_modules_lang.id_record')
                    ->where('zz_modules_lang.id_lang', '=', Locale::getDefault()->id);
            })
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
            ->select('zz_modules.*', 'zz_modules_lang.title as title')
            ->join('zz_modules_lang', function ($join) {
                $join->on('zz_modules.id', '=', 'zz_modules_lang.id_record')
                    ->where('zz_modules_lang.id_lang', '=', Locale::getDefault()->id);
            })
            ->withoutGlobalScope('enabled')
            ->whereNull('parent')
            ->orderBy('order')
            ->get();
    }

    public function getModuleAttribute()
    {
        return '';
    }

    public static function getTranslatedFields()
    {
        return self::$translated_fields;
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
