<?php

namespace Modules\Anagrafiche;

use Common\Model;

class Sede extends Model
{
    protected $table = 'an_sedi';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Crea una nuova sede.
     *
     * @param Anagrafica $anagrafica
     *
     * @return self
     */
    public static function build(Anagrafica $anagrafica, $is_sede_legale = false)
    {
        $model = parent::make();

        if (!empty($is_sede_legale)) {
            $model->nomesede = 'Sede legale';
        }
        $model->anagrafica()->associate($anagrafica);
        $model->save();

        return $model;
    }

    public function anagrafica()
    {
        return $this->belongsTo(Anagrafica::class, 'idanagrafica');
    }

    public function nazione()
    {
        return $this->belongsTo(Nazione::class, 'id_nazione');
    }
}
