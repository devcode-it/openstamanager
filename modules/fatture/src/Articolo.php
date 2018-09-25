<?php

namespace Modules\Fatture;

use Illuminate\Database\Eloquent\Model;
use Modules\Articoli\Articolo as Original;
use Traits\ArticleTrait;

class Articolo extends Model
{
    use ArticleTrait;

    protected $table = 'co_righe_documenti';

    public function __construct(Fattura $fattura, Original $articolo, array $attributes = array())
    {
        parent::__construct($attributes);

        $this->fattura()->associate($fattura);
        $this->articolo()->associate($articolo);

        // Salvataggio delle informazioni
        $this->descrizione = isset($attributes['descrizione']) ? $attributes['descrizione'] : $articolo->descrizione;
        $this->abilita_serial = $articolo->abilita_serial;

        $this->save();
    }

    public function articolo()
    {
        return $this->belongsTo(Original::class, 'idarticolo');
    }

    public function fattura()
    {
        return $this->belongsTo(Fattura::class, 'iddocumento');
    }
}
