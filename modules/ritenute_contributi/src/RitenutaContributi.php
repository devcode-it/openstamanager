<?php

namespace Modules\RitenuteContributi;

use Common\Model;
use Modules\Fatture\Fattura;

class RitenutaContributi extends Model
{
    protected $table = 'co_ritenuta_contributi';

    public function fatture()
    {
        return $this->hasMany(Fattura::class, 'id_ritenuta_contributi');
    }
}
