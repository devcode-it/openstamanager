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
use Util\Query;

/**
 * Colonna/vista configurabile per le liste dei moduli.
 *
 * ## Campi traducibili – regola Dual Write
 * I campi in {@see View::$translated_fields} NON risiedono in questa tabella.
 * Risiedono in `zz_views_lang` (id_record, id_lang, title).
 *
 * Ogni INSERT su `zz_views` DEVE essere seguito da un INSERT su `zz_views_lang`:
 * ```php
 * $id = $dbo->insert('zz_views', ['id_module' => $id_module, 'name' => 'campo', 'query' => '...']);
 * $dbo->insert('zz_views_lang', ['id_record' => $id, 'id_lang' => $id_lang, 'title' => 'Etichetta']);
 * ```
 * Per aggiornare una traduzione usa {@see RecordTrait::setTranslation()}.
 */
class View extends Model
{
    use SimpleModelTrait;
    use RecordTrait;
    protected $table = 'zz_views';

    protected static $translated_fields = [
        'title',
    ];

    public function getQueryAttribute($value)
    {
        return Query::replacePlaceholder($value);
    }

    /* Relazioni Eloquent */

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'zz_group_view', 'id_vista', 'id_gruppo');
    }

    public function module()
    {
        return $this->belongsTo(Module::class, 'id_module');
    }

    public function getModuleAttribute()
    {
        return '';
    }

    public static function getTranslatedFields()
    {
        return self::$translated_fields;
    }
}
