<?php

/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
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

namespace Models;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Traits\RecordTrait;

/**
 * Voce della Navbar Right/Left (tabella `zz_links`).
 *
 * Campi traducibili (`label`, `title`) vivono in `zz_links_lang` — vedi RecordTrait.
 */
class Link extends Model
{
    use SimpleModelTrait;
    use RecordTrait;

    protected $table = 'zz_links';

    protected static $translated_fields = [
        'label',
        'title',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent')->orderBy('order');
    }

    public function hostModule()
    {
        return $this->belongsTo(Module::class, 'id_module');
    }

    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    public function getLabelAttribute(): string
    {
        return (string) $this->getTranslation('label');
    }

    public function getTitleAttribute(): string
    {
        return (string) $this->getTranslation('title');
    }

    public static function getTranslatedFields()
    {
        return self::$translated_fields;
    }

    public function getModuleAttribute()
    {
        return $this->hostModule ? $this->hostModule->name : null;
    }
}
