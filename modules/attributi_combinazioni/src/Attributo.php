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
