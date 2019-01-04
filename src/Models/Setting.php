<?php

namespace Models;

use Common\Model;
use Traits\StoreTrait;

class Setting extends Model
{
    use StoreTrait;

    protected $table = 'zz_settings';

    protected $appends = [
        'description',
    ];

    public function getDescriptionAttribute()
    {
        $value = $this->valore;

        // Valore corrispettivo
        $query = str_replace('query=', '', $this->tipo);
        if ($query != $this->tipo) {
            $data = database()->fetchArray($query);
            if (!empty($data)) {
                $value = $data[0]['descrizione'];
            }
        }

        return $value;
    }
}
