<?php

namespace Plugins\DettagliArticolo;

use Common\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Anagrafiche\Anagrafica;
use Modules\Articoli\Articolo;

/**
 * Classe per la gestione delle relazioni tra articolo e fornitore.
 *
 * @since 2.4.15
 */
class DettaglioFornitore extends Model
{
    use SoftDeletes;

    protected $table = 'mg_fornitore_articolo';

    /**
     * Crea una nuova relazione tra Articolo e Fornitore.
     *
     * @return self
     */
    public static function build(Anagrafica $fornitore, Articolo $articolo)
    {
        $model = parent::build();

        $model->anagrafica()->associate($fornitore);
        $model->articolo()->associate($articolo);

        $model->save();

        return $model;
    }

    // Relazioni Eloquent

    public function anagrafica()
    {
        return $this->belongsTo(Anagrafica::class, 'id_fornitore');
    }

    public function articolo()
    {
        return $this->belongsTo(Articolo::class, 'id_articolo');
    }
}
