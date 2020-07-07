<?php

namespace Common;

class RowReference extends Model
{
    protected $table = 'co_riferimenti_righe';

    public function source()
    {
        return $this->morphTo();
    }

    public function target()
    {
        return $this->morphTo();
    }
}
