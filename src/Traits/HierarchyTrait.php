<?php

namespace Traits;

trait HierarchyTrait
{
    public function children()
    {
        return $this->hasMany(self::class, self::$parent_identifier);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, self::$parent_identifier);
    }

    public function allParents()
    {
        return $this->parent()->with('allParents');
    }

    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }

    public static function getHierarchy()
    {
        return self::with('allChildren')
            ->whereNull(self::$parent_identifier)
            ->get();
    }
}
