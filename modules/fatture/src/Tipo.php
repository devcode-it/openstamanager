<?php

namespace Modules\Fatture;

use Common\Model;

class Tipo extends Model
{
    protected $table = 'co_tipidocumento';

    public function fatture()
    {
        return $this->hasMany(Fattura::class, 'id_tipo_documento');
    }
}
