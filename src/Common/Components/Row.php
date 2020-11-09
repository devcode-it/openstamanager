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
use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Builder;

abstract class Row extends Accounting
{
    use SimpleModelTrait;

    public static function build(Document $document)
    {
        $model = new static();
        $model->setDocument($document);

        return $model;
    }

    public function isDescrizione()
    {
        return false;
    }

    public function isSconto()
    {
        return false;
    }

    public function isRiga()
    {
        return true;
    }

    public function isArticolo()
    {
        return false;
    }

    protected static function boot()
    {
        parent::boot();

        $table = static::getTableName();
        static::addGlobalScope('not_articles', function (Builder $builder) use ($table) {
            $builder->whereNull($table.'.idarticolo')->orWhere($table.'.idarticolo', '=', 0);
        });

        static::addGlobalScope('not_discounts', function (Builder $builder) use ($table) {
            $builder->where($table.'.is_sconto', '=', 0);
        });

        static::addGlobalScope('not_descriptions', function (Builder $builder) use ($table) {
            $builder->where($table.'.is_descrizione', '=', 0);
        });
    }

    /**
     * Azione personalizzata per la copia dell'oggetto (dopo la copia).
     *
     * Forza il salvataggio del prezzo_unitario, per rendere compatibile il sistema con gli Interventi.
     *
     * @param $original
     */
    protected function customAfterDataCopiaIn($original)
    {
        $this->prezzo_unitario = $original->prezzo_unitario;

        parent::customAfterDataCopiaIn($original);
    }
}
