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

namespace Modules\Emails;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Models\Module;
use Models\PrintTemplate;
use Traits\LocalPoolTrait;
use Traits\RecordTrait;

class Template extends Model
{
    use SimpleModelTrait;
    use LocalPoolTrait;
    use SoftDeletes;
    use RecordTrait;

    protected $table = 'em_templates';

    protected static $translated_fields = [
        'name',
        'body',
        'subject',
    ];

    public function getVariablesAttribute()
    {
        $dbo = $database = database();

        // Lettura delle variabili del modulo collegato
        $variables = include $this->module->filepath('variables.php');

        return (array) $variables;
    }

    public static function build($id_module = null, $id_account = null)
    {
        $model = new static();
        $model->id_module = $id_module;
        $model->id_account = $id_account;
        $model->save();

        return $model;
    }

    /* Relazioni Eloquent */

    public function module()
    {
        return $this->belongsTo(Module::class, 'id_module');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'id_account')->withTrashed();
    }

    public function prints()
    {
        return $this->belongsToMany(PrintTemplate::class, 'em_print_template', 'id_template', 'id_print');
    }

    public function translations()
    {
        return $this->hasMany(TemplateLang::class, 'id');
    }

    public function getModuleAttribute()
    {
        return 'Template email';
    }
    
    public static function getTranslatedFields(){
        return self::$translated_fields;
    }
}
