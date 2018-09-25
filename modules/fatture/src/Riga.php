<?php

namespace Modules\Fatture;

use Illuminate\Database\Eloquent\Model;
use Traits\RowTrait;

class Riga extends Model
{
    use RowTrait;

    protected $table = 'co_righe_documenti';

    public function __construct(Fattura $fattura, array $attributes = array())
    {
        parent::__construct($attributes);

        $this->fattura()->associate($fattura);
        $this->save();
    }

    public function fattura()
    {
        return $this->belongsTo(Fattura::class, 'iddocumento');
    }
}
