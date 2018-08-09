<?php

namespace Modules\Fatture;

use Illuminate\Database\Eloquent\Model;

class Stato extends Model
{
    protected $table = 'co_statidocumento';

    public function fatture()
    {
        return $this->hasMany(Fattura::class, 'idstatodocumento');
    }
}
