<?php

namespace Common\Components;

use Common\Document;
use Common\Model;
use Illuminate\Database\Eloquent\Builder;

abstract class Description extends Model
{
    protected $guarded = [];

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

    public function copiaIn(Document $document)
    {
        $class = get_class($document);
        $namespace = implode('\\', explode('\\', $class, -1));

        $current = get_class($this);
        $pieces = explode('\\', $current);
        $type = end($pieces);

        $object = $namespace.'\\Components\\'.$type;

        $attributes = $this->getAttributes();
        unset($attributes['id']);

        $model = $object::make($document);
        $model->save();

        $model = $object::find($model->id);
        $accepted = $model->getAttributes();

        $attributes = array_intersect_key($attributes, $accepted);
        $model->fill($attributes);

        return $model;
    }

    abstract public function parent();

    abstract public function getParentID();

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
}
