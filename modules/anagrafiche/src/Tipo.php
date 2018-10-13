<?php

namespace Modules\Anagrafiche;

use Common\Model;

class Tipo extends Model
{
    protected $table = 'an_tipianagrafiche';

    protected $appends = [
        'id',
    ];

    protected $hidden = [
        'id_tipo_anagrafica',
    ];

    /**
     * Restituisce l'identificativo.
     *
     * @return int
     */
    public function getIdAttribute()
    {
        return $this->id_tipo_anagrafica;
    }

    public function anagrafiche()
    {
        return $this->hasMany(Anagrafica::class, 'an_tipianagrafiche_anagrafiche', 'id_tipo_anagrafica', 'idanagrafica');
    }
}
