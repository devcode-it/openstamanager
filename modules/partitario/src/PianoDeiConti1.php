<?php

namespace Modules\Partitario;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;

class PianoDeiConti1 extends Model
{
    use SimpleModelTrait;

    protected $table = 'co_piano_dei_conti1';

    protected $fillable = [
        'numero',
        'descrizione',
    ];

    public function secondiLivelli()
    {
        return $this->hasMany(PianoDeiConti2::class, 'id_piano_dei_conti1')->orderBy('numero', 'asc');
    }
}