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

namespace Common\Components;

use Common\Document;
use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Builder;

abstract class Description extends Component
{
    use SimpleModelTrait;

    public static function build(Document $document)
    {
        $model = new static();

        $model->is_descrizione = 1;
        $model->qta = 1;

        $model->setDocument($document);

        return $model;
    }

    public function isDescrizione()
    {
        return true;
    }

    public function isSconto()
    {
        return false;
    }

    public function isRiga()
    {
        return false;
    }

    public function isArticolo()
    {
        return false;
    }

    /**
     * Azione personalizzata per la copia dell'oggetto (inizializzazione della copia).
     *
     * @param $original
     */
    protected function customInitCopiaIn($original)
    {
        $this->is_descrizione = $original->is_descrizione;
    }

    protected static function boot($bypass = false)
    {
        // Pre-caricamento Documento
        parent::boot();

        $table = static::getTableName();
        static::addGlobalScope('descriptions', function (Builder $builder) use ($table) {
            $builder->where($table.'.is_descrizione', '=', 1);
        });
    }
}
