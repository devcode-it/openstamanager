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
}
