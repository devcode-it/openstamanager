<?php

namespace Modules\Anagrafiche;

use Base\Model;

class Nazione extends Model
{
    protected $table = 'an_nazioni';

    public function anagrafiche()
    {
        return $this->hasMany(Anagrafica::class, 'id_nazione');
    }
}
