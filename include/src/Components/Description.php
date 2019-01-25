<?php

namespace Common\Components;

use Common\Document;
use Common\Model;
use Illuminate\Database\Eloquent\Builder;

abstract class Description extends Model
{
    protected $guarded = [];

    public static function build(Document $document, $bypass = false)
    {
        $model = parent::build();

        if (!$bypass) {
            $model->is_descrizione = 1;
        }

        $model->setParent($document);

        return $model;
    }

    /**
     * Imposta il proprietario dell'oggetto e l'ordine relativo all'interno delle righe.
     *
     * @param Document $document
     */
    public function setParent(Document $document)
    {
        $this->parent()->associate($document);

        // Ordine delle righe
        if (empty($this->disableOrder)) {
            $this->order = orderValue($this->table, $this->getParentID(), $document->id);
        }

        $this->save();
    }

    /**
     * Copia l'oggetto (articolo, riga, descrizione) nel corrispettivo per il documento indicato.
     *
     * @param Document   $document
     * @param float|null $qta
     *
     * @return self
     */
    public function copiaIn(Document $document, $qta = null)
    {
        // Individuazione classe di destinazione
        $class = get_class($document);
        $namespace = implode('\\', explode('\\', $class, -1));

        $current = get_class($this);
        $pieces = explode('\\', $current);
        $type = end($pieces);

        $object = $namespace.'\\Components\\'.$type;

        // Attributi dell'oggetto da copiare
        $attributes = $this->getAttributes();
        unset($attributes['id']);

        if ($qta !== null) {
            $attributes['qta'] = $qta;
        }

        // Creazione del nuovo oggetto
        $model = new $object();
        $model->setParent($document);

        $model->customCopiaIn($this);

        $model->save();

        // Impostazione degli attributi
        $model = $object::find($model->id);
        $accepted = $model->getAttributes();

        $attributes = array_intersect_key($attributes, $accepted);
        $model->fill($attributes);

        $model->save();

        // Rimozione quantità evasa
        $this->qta_evasa = $this->qta_evasa + $attributes['qta'];

        return $model;
    }

    abstract public function parent();

    abstract public function getParentID();

    /**
     * Azione personalizzata per la copia dell'oggetto.
     *
     * @param $original
     */
    protected function customCopiaIn($original)
    {
    }

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
