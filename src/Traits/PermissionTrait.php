<?php

namespace Traits;

trait PermissionTrait
{
    /**
     * Restituisce i permessi relativi all'account in utilizzo.
     *
     * @return string
     */
    public function getPermissionAttribute()
    {
        if (auth()->user()->is_admin) {
            return 'rw';
        }

        $group = auth()->user()->group->id;

        $pivot = $this->pivot ?: $this->groups->first(function ($item) use ($group) {
            return $item->id == $group;
        })->pivot;

        return $pivot->permessi ?: '-';
    }
}
