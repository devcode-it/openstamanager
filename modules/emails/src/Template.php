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

class Template extends Model
{
    use SimpleModelTrait;
    use LocalPoolTrait;
    use SoftDeletes;

    protected $table = 'em_templates';

    public function getVariablesAttribute()
    {
        $dbo = $database = database();

        // Lettura delle variabili del modulo collegato
        $variables = include $this->module->filepath('variables.php');

        return (array) $variables;
    }

    public static function build($id_module, $id_account)
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

    /**
     * Ritorna l'attributo name del template.
     *
     * @return string
     */
    public function getNameAttribute()
    {
        return database()->table($this->table.'_lang')
            ->select('name')
            ->where('id_record', '=', $this->id)
            ->where('id_lang', '=', setting('Lingua'))
            ->first()->name;
    }

    /**
     * Imposta l'attributo name della lista.
     */
    public function setNameAttribute($value)
    {
        $table = database()->table($this->table.'_lang');

        $translated = $table
            ->where('id_record', '=', $this->id)
            ->where('id_lang', '=', setting('Lingua'));

        if ($translated->count() > 0) {
            $translated->update([
                'name' => $value
            ]);
        } else {
            $table->insert([
                'id_record' => $this->id,
                'id_lang' => setting('Lingua'),
                'name' => $value
            ]);
        }
    }
    /**
     * Ritorna l'attributo subject del template.
     *
     * @return string
     */
    public function getSubjectAttribute()
    {
        return database()->table($this->table.'_lang')
            ->select('subject')
            ->where('id_record', '=', $this->id)
            ->where('id_lang', '=', setting('Lingua'))
            ->first()->subject;
    }

    /**
     * Imposta l'attributo subject del template.
     */
    public function setSubjectAttribute($value)
    {
        $table = database()->table($this->table.'_lang');

        $translated = $table
            ->where('id_record', '=', $this->id)
            ->where('id_lang', '=', setting('Lingua'));

        if ($translated->count() > 0) {
            $translated->update([
                'subject' => $value
            ]);
        } else {
            $table->insert([
                'id_record' => $this->id,
                'id_lang' => setting('Lingua'),
                'subject' => $value
            ]);
        }
    }

    /**
     * Ritorna l'attributo body del template.
     *
     * @return string
     */
    public function getBodyAttribute()
    {
        return database()->table($this->table.'_lang')
            ->select('body')
            ->where('id_record', '=', $this->id)
            ->where('id_lang', '=', setting('Lingua'))
            ->first()->body;
    }
    /**
     * Imposta l'attributo body del template.
     */
    public function setBodyAttribute($value)
    {
        $table = database()->table($this->table.'_lang');

        $translated = $table
            ->where('id_record', '=', $this->id)
            ->where('id_lang', '=', setting('Lingua'));

        if ($translated->count() > 0) {
            $translated->update([
                'body' => $value
            ]);
        } else {
            $table->insert([
                'id_record' => $this->id,
                'id_lang' => setting('Lingua'),
                'body' => $value
            ]);
        }
    }

    /**
     * Ritorna l'id del template a partire dal nome.
     *
     * @param string $name il nome da ricercare
     *
     * @return \Illuminate\Support\Collection
     */
    public function getByName($name)
    {
        return database()->table($this->table.'_lang')
            ->select('id_record')
            ->where('name', '=', $name)
            ->where('id_lang', '=', setting('Lingua'))
            ->first();
    }

    public function translations()
    {
        return $this->hasMany(TemplateLang::class, 'id');
    }
}
