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

use Common\Document;
use Illuminate\Database\Eloquent\Builder;

abstract class Discount extends Row
{
    protected $guarded = [];

    public static function build(Document $document)
    {
        $model = parent::build($document, true);

        $model->is_sconto = 1;
        $model->qta = 1;

        return $model;
    }

    public function getIvaAttribute()
    {
        return $this->attributes['iva'];
    }

    public function isMaggiorazione()
    {
        return $this->totale_imponibile < 0;
    }

    /**
     * Effettua i conti per l'IVA.
     */
    protected function fixIva()
    {
        $this->attributes['iva'] = parent::getIvaAttribute();

        $descrizione = $this->aliquota->descrizione;
        if (!empty($descrizione)) {
            $this->attributes['desc_iva'] = $descrizione;
        }

        $this->fixIvaIndetraibile();
    }

    protected static function boot($bypass = false)
    {
        parent::boot(true);

        $table = parent::getTableName();

        static::addGlobalScope('discounts', function (Builder $builder) use ($table) {
            $builder->where($table.'.is_sconto', '=', 1);
        });
    }
}
