<?php

namespace Modules\Impianti;

use Common\Model;
use Modules\Anagrafiche\Anagrafica;

class Impianto extends Model
{
    protected $table = 'my_impianti';

    // Relazioni Eloquent
    public function anagrafica()
    {
        return $this->belongsTo(Anagrafica::class, 'idanagrafica');
    }
}
