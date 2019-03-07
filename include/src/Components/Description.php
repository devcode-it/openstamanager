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

        // Azioni specifiche di inizalizzazione
        $model->customInitCopiaIn($this);

        $model->save();

        // Impostazione degli attributi
        $model = $object::find($model->id);
        $accepted = $model->getAttributes();

        // Azioni specifiche precedenti
        $model->customBeforeDataCopiaIn($this);

        $attributes = array_intersect_key($attributes, $accepted);
        $model->fill($attributes);

        // Impostazione del genitore
        $model->setParent($document);

        // Azioni specifiche successive
        $model->customAfterDataCopiaIn($this);

        $model->save();

        // Rimozione quantità evasa
        $this->qta_evasa = $this->qta_evasa + abs($attributes['qta']);
        $this->save();

        return $model;
    }

    abstract public function parent();

    abstract public function getParentID();

    public function isDescrizione()
    {
        return $this->is_descrizione == 1;
    }

    public function isRiga()
    {
        return !$this->isDescrizione() && !$this->isArticolo();
    }

    public function isArticolo()
    {
        return !empty($this->idarticolo);
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

    /**
     * Azione personalizzata per la copia dell'oggetto (dopo la copia).
     *
     * @param $original
     */
    protected function customBeforeDataCopiaIn($original)
    {
    }

    /**
     * Azione personalizzata per la copia dell'oggetto (dopo la copia).
     *
     * @param $original
     */
    protected function customAfterDataCopiaIn($original)
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
