<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

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
