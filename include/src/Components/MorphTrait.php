<?php

namespace Common\Components;

use Common\RowReference;

trait MorphTrait
{
    protected $original_model = null;

    public function hasOriginal()
    {
        return !empty($this->original_type) && !empty($this->getOriginal());
    }

    public function getOriginal()
    {
        if (!isset($this->original_model) && !empty($this->original_type)) {
            $class = $this->original_type;

            $this->original_model = $class::find($this->original_id);
        }

        return $this->original_model;
    }

    public function referenceSources()
    {
        $class = get_class($this);

        return $this->hasMany(RowReference::class, 'target_id')
            ->where('target_type', $class);
    }

    public function referenceTargets()
    {
        $class = get_class($this);

        return $this->hasMany(RowReference::class, 'source_id')
            ->where('source_type', $class);
    }
}
