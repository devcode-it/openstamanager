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
use Modules\CategorieFiles\Categoria;
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
        'title',
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

    public static function build($id_module = null, $id_account = null, $name = null)
    {
        $model = new static();
        $model->id_module = $id_module;
        $model->id_account = $id_account;
        $model->name = $name;
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

    public function categories()
    {
        return $this->belongsToMany(Categoria::class, 'em_files_categories_template', 'id_template', 'id_category');
    }

    /**
     * Ottiene tutti gli allegati associati alle categorie del template.
     *
     * @return \Illuminate\Support\Collection
     */
    public function uploads($id_record = null)
    {
        $uploads = [];
        
        // Recupera le categorie associate al template
        $categories = $this->categories;
        
        // Per ogni categoria, recupera i file associati
        foreach ($categories as $category) {
            // Recupera i file del modulo corrente
            if ($this->id_module) {
                $files = \Models\Upload::where('id_category', $category->id)
                    ->where('id_module', $this->id_module)
                    ->where('id_record', $id_record)
                    ->get();
                
                $uploads = array_merge($uploads, $files->all());
            }
            
            // Recupera anche i file dell'azienda predefinita
            $id_module_anagrafiche = \Models\Module::where('name', 'Anagrafiche')->first()->id;
            $id_record_azienda = setting('Azienda predefinita');
            if ($id_record_azienda) {
                $files = \Models\Upload::where('id_category', $category->id)
                    ->where('id_module', $id_module_anagrafiche)
                    ->where('id_record', $id_record_azienda)
                    ->get();
                    
                $uploads = array_merge($uploads, $files->all());
            }
        }
        
        // Rimuovi eventuali duplicati
        $unique_uploads = [];
        foreach ($uploads as $upload) {
            $unique_uploads[$upload->id] = $upload;
        }
        
        return collect(array_values($unique_uploads));
    }

    public function translations()
    {
        return $this->hasMany(TemplateLang::class, 'id');
    }

    public function getModuleAttribute()
    {
        return $this->belongsTo(Module::class, 'id_module')->first();
    }

    public static function getTranslatedFields()
    {
        return self::$translated_fields;
    }
}
