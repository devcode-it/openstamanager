<?php

namespace Common\Components;

use Illuminate\Database\Eloquent\Builder;
use Common\Model;
use Common\Document;

abstract class Description extends Model
{
    protected static function boot($bypass = false)
    {
        parent::boot();

        if (!$bypass) {
            static::addGlobalScope('descriptions', function (Builder $builder) {
                $builder->where('is_descrizione', '=', 1);
            });
        } else {
            static::addGlobalScope('not_descriptions', function (Builder $builder) {
                $builder->where('is_descrizione', '=', 0);
            });
        }

        static::addGlobalScope('not_discount', function (Builder $builder) {
            $builder->where('sconto_globale', '=', 0);
        });
    }

    public static function make(Document $document, $bypass = false)
    {
        $model = parent::make();

        if (!$bypass) {
            $model->is_descrizione = 1;
        }

        $model->setParent($document);

        return $model;
    }

    public function setParent(Document $document)
    {
        $this->parent()->associate($document);

        // Ordine delle righe
        if (empty($this->disableOrder)) {
            $this->order = orderValue($this->table, $this->getParentID(), $document->id);
        }

        $this->save();
    }

    abstract public function parent();
    abstract public function getParentID();
}
