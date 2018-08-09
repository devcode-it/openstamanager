<?php

namespace Modules\Fatture;

use Illuminate\Database\Eloquent\Model;

class Riga extends Model
{
    protected $table = 'co_righe_documenti';

    public function fattura()
    {
        return $this->belongsTo(Fattura::class, 'iddocumento');
    }

    public function getImponibile()
    {
        return $this->subtotale - $this->sconto;
    }
}
