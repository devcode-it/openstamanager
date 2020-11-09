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

abstract class Discount extends Accounting
{
    use SimpleModelTrait;

    public static function build(Document $document)
    {
        $model = new static();
        $model->setDocument($document);

        $model->is_sconto = 1;
        $model->qta = 1;

        return $model;
    }

    public function isDescrizione()
    {
        return false;
    }

    public function isSconto()
    {
        return true;
    }

    public function isRiga()
    {
        return false;
    }

    public function isArticolo()
    {
        return false;
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
     * Imposta lo sconto unitario secondo le informazioni indicate per valore e tipologia (UNT o PRC).
     *
     * @param float $valore_unitario
     * @param int   $id_iva
     */
    public function setScontoUnitario($valore_unitario, $id_iva)
    {
        $this->id_iva = $id_iva;

        // Gestione IVA incorporata
        if ($this->incorporaIVA()) {
            $this->sconto_unitario_ivato = $valore_unitario;
        } else {
            $this->sconto_unitario = $valore_unitario;
        }
    }

    public function setPrezzoUnitario($prezzo_unitario, $id_iva)
    {
        throw new \InvalidArgumentException();
    }

    public function setSconto($value, $type)
    {
        throw new \InvalidArgumentException();
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

    protected function customInitCopiaIn($original)
    {
        $this->is_sconto = $original->is_sconto;
    }

    protected static function boot($bypass = false)
    {
        parent::boot();

        $table = static::getTableName();
        static::addGlobalScope('discounts', function (Builder $builder) use ($table) {
            $builder->where($table.'.is_sconto', '=', 1);
        });
    }
}
