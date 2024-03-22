<?php

namespace Modules\AttributiCombinazioni;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Traits\RecordTrait;

class Attributo extends Model
{
    use SimpleModelTrait;
    use SoftDeletes;
    use RecordTrait;

    protected $table = 'mg_attributi';

    protected static $translated_fields = [
        'name',
        'title',
    ];

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

    public function getModuleAttribute()
    {
        return 'Attributi combinazioni';
    }

    public static function getTranslatedFields()
    {
        return self::$translated_fields;
    }
}
