<?php

namespace Modules\AttributiCombinazioni;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ValoreAttributo extends Model
{
    use SimpleModelTrait;
    use SoftDeletes;

    protected $table = 'mg_valori_attributi';

    public static function build(Attributo $attributo = null, $valore = null)
    {
        $model = new self();

        $model->attributo()->associate($attributo);
        $model->nome = $valore;

        $model->save();

        return $model;
    }

    /* Relazioni Eloquent */
    public function attributo()
    {
        return $this->belongsTo(Attributo::class, 'id_attributo');
    }
}
