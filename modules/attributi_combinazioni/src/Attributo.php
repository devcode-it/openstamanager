<?php

namespace Modules\AttributiCombinazioni;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attributo extends Model
{
    use SimpleModelTrait;
    use SoftDeletes;

    protected $table = 'mg_attributi';

    public static function build()
    {
        $model = new static();
        $model->save();

        return $model;
    }

    /* Relazioni Eloquent */
    public function valori()
    {
        return $this->hasMany(ValoreAttributo::class, 'id_attributo');
    }


    /**
     * Ritorna l'attributo name dell'attributo.
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
     * Imposta l'attributo name dell'attributo.
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
     * Ritorna l'attributo title dell'attributo.
     *
     * @return string
     */
    public function getTitleAttribute()
    {
        return database()->table($this->table.'_lang')
            ->select('title')
            ->where('id_record', '=', $this->id)
            ->where('id_lang', '=', setting('Lingua'))
            ->first()->title;
    }

    /**
     * Imposta l'attributo title dell'attributo.
     */
    public function setTitleAttribute($value)
    {
        $table = database()->table($this->table.'_lang');

        $translated = $table
            ->where('id_record', '=', $this->id)
            ->where('id_lang', '=', setting('Lingua'));

        if ($translated->count() > 0) {
            $translated->update([
                'title' => $value
            ]);
        } else {
            $table->insert([
                'id_record' => $this->id,
                'id_lang' => setting('Lingua'),
                'title' => $value
            ]);
        }
    }

    /**
     * Ritorna l'id dell'attributo a partire dal nome.
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
}
