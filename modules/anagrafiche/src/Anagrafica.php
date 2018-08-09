<?php

namespace Modules\Anagrafiche;

use Illuminate\Database\Eloquent\Model;
use Modules\Fatture\Fattura;

class Anagrafica extends Model
{
    protected $table = 'an_anagrafiche';
    protected $primaryKey = 'idanagrafica';

    public function fatture()
    {
        return $this->hasMany(Fattura::class, 'idanagrafica');
    }
}
