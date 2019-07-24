<?php

namespace Common\Components;

trait MorphTrait
{
    public function hasOriginal()
    {
        return !empty($this->original_type) && !empty($this->original);
    }

    public function original()
    {
        return $this->morphedByMany($this->original_type, 'original', $this->table, 'id');
    }

    public function getOriginal()
    {
        return $this->original()->first();
    }
}
