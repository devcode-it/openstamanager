<?php

namespace Common\Components;

use Common\Document;
use Common\Model;
use Illuminate\Database\Eloquent\Builder;

abstract class Description extends Model
{
    use MorphTrait;

    protected $guarded = [];

    protected $appends = [
        'max_qta',
    ];

    public static function build(Document $document, $bypass = false)
    {
        $model = parent::build();

        if (!$bypass) {
            $model->is_descrizione = 1;
            $model->qta = 1;
        }

        $model->setParent($document);

        return $model;
    }

    public function getMaxQtaAttribute()
    {
        if (!$this->hasOriginal()) {
            return null;
        }

        $original = $this->getOriginal();

        return $original->qta_rimanente + $this->qta;
    }

    /**
     * Modifica la quantità dell'elemento.
     *
     * @param float $value
     *
     * @return float
     */
    public function setQtaAttribute($value)
    {
        $previous = $this->qta;
        $diff = $value - $previous;

        if ($this->hasOriginal()) {
            $original = $this->getOriginal();

            if ($original->qta_rimanente < $diff) {
                $diff = $original->qta_rimanente;
                $value = $previous + $diff;
            }
        }

        $this->attributes['qta'] = $value;

        if ($this->hasOriginal()) {
            $original = $this->getOriginal();

            $original->qta_evasa += $diff;
            $original->save();
        }

        return $diff;
    }

    /**
     * Restituisce la quantità rimanente dell'elemento.
     *
     * @return float
     */
    public function getQtaRimanenteAttribute()
    {
        return $this->qta - $this->qta_evasa;
    }

    public function canDelete()
    {
        return true;
    }

    public function delete()
    {
        if (!$this->canDelete()) {
            throw new \InvalidArgumentException();
        }

        if ($this->hasOriginal()) {
            $original = $this->getOriginal();
        }

        $this->qta = 0;
        $result = parent::delete();

        // Trigger per la modifica delle righe
        $this->parent->triggerComponent($this);

        // Trigger per l'evasione delle quantità
        if ($this->hasOriginal()) {
            $original->parent->triggerEvasione($this);
        }

        return $result;
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
        unset($attributes['original_id']);
        unset($attributes['original_type']);
        unset($attributes['order']);

        if ($qta !== null) {
            $attributes['qta'] = $qta;
        }

        $attributes['qta_evasa'] = 0;

        // Creazione del nuovo oggetto
        $model = new $object();

        // Rimozione attributo in conflitto
        unset($attributes[$model->getParentID()]);

        $model->original_id = $this->id;
        $model->original_type = $current;

        // Impostazione del genitore
        $model->setParent($document);

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

        // Azioni specifiche successive
        $model->customAfterDataCopiaIn($this);

        $model->save();

        return $model;
    }

    abstract public function parent();

    abstract public function getParentID();

    public function isDescrizione()
    {
        return !$this->isArticolo() && !$this->isSconto() && !$this->isRiga() && $this instanceof Description;
    }

    public function isSconto()
    {
        return $this instanceof Discount;
    }

    public function isRiga()
    {
        return !$this->isArticolo() && !$this->isSconto() && $this instanceof Row;
    }

    public function isArticolo()
    {
        return $this instanceof Article;
    }

    public function save(array $options = [])
    {
        $result = parent::save($options);

        // Trigger per la modifica delle righe
        $this->parent->triggerComponent($this);

        // Trigger per l'evasione delle quantità
        if ($this->hasOriginal()) {
            $original = $this->getOriginal();

            $original->parent->triggerEvasione($this);
        }

        return $result;
    }

    /**
     * Azione personalizzata per la copia dell'oggetto (inizializzazione della copia).
     *
     * @param $original
     */
    protected function customInitCopiaIn($original)
    {
        $this->is_descrizione = $original->is_descrizione;
        $this->is_sconto = $original->is_sconto;
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

        $table = parent::getTableName();

        if (!$bypass) {
            static::addGlobalScope('descriptions', function (Builder $builder) use ($table) {
                $builder->where($table.'.is_descrizione', '=', 1);
            });
        } else {
            static::addGlobalScope('not_descriptions', function (Builder $builder) use ($table) {
                $builder->where($table.'.is_descrizione', '=', 0);
            });
        }
    }
}
