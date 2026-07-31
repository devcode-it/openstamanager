<?php

namespace Modules\Partitario;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;

class PianoDeiConti2 extends Model
{
    use SimpleModelTrait;

    protected $table = 'co_piano_dei_conti2';

    protected $fillable = [
        'numero',
        'descrizione',
        'id_piano_dei_conti1',
        'dir',
    ];

    public function primoLivello()
    {
        return $this->belongsTo(PianoDeiConti1::class, 'id_piano_dei_conti1');
    }

    public function terziLivelli()
    {
        return $this->hasMany(PianoDeiConti3::class, 'id_piano_dei_conti2')->orderBy('numero', 'asc');
    }
}