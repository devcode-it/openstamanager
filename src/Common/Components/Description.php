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
use Common\Model;
use Illuminate\Database\Eloquent\Builder;

abstract class Description extends Model
{
    use MorphTrait;

    protected $guarded = [];

    protected $appends = [
        'max_qta',
    ];

    protected $hidden = [
        'parent',
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

    public function toArray()
    {
        $array = parent::toArray();

        $result = array_merge($array, [
            'spesa' => $this->spesa,
            'imponibile' => $this->imponibile,
            'sconto' => $this->sconto,
            'totale_imponibile' => $this->totale_imponibile,
            'iva' => $this->iva,
            'totale' => $this->totale,
        ]);

        return $result;
    }

    /**
     * Imposta il proprietario dell'oggetto e l'ordine relativo all'interno delle righe.
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
        unset($attributes['order']);

        if ($qta !== null) {
            $attributes['qta'] = $qta;
        }

        $attributes['qta_evasa'] = 0;

        // Creazione del nuovo oggetto
        $model = new $object();

        // Rimozione attributo in conflitto
        unset($attributes[$model->getParentID()]);

        // Riferimento di origine per l'evasione automatica della riga
        $is_evasione = true;
        if ($is_evasione) {
            // Mantenimento dell'origine della riga precedente
            $model->original_id = $attributes['original_id'];
            $model->original_type = $attributes['original_type'];

            // Aggiornamento dei riferimenti
            list($riferimento_precedente, $nuovo_riferimento) = $model->impostaOrigine($current, $this->id);

            // Correzione della descrizione
            $attributes['descrizione'] = str_replace($riferimento_precedente, '', $attributes['descrizione']);
            $attributes['descrizione'] .= $nuovo_riferimento;
        }
        unset($attributes['original_id']);
        unset($attributes['original_type']);

        // Impostazione del genitore
        $model->setParent($document);

        // Azioni specifiche di inizializzazione
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

    /**
     * Imposta l'origine dell'elemento, restituendo un array contenente i replace da effettuare per modificare la descrizione in modo coerente.
     *
     * @param $type
     * @param $id
     */
    public function impostaOrigine($type, $id)
    {
        $riferimento_precedente = null;
        $nuovo_riferimento = null;

        // Rimozione del riferimento precedente dalla descrizione
        if ($this->hasOriginal()) {
            $riferimento = $this->getOriginal()->parent->getReference();
            $riferimento_precedente = "\nRif. ".strtolower($riferimento);
        }

        $this->original_id = $id;
        $this->original_type = $type;

        // Aggiunta del riferimento nella descrizione
        $origine = $type::find($id);
        if (!empty($origine)) {
            $riferimento = $origine->parent->getReference();
            $nuovo_riferimento = "\nRif. ".strtolower($riferimento);
        }

        return [$riferimento_precedente, $nuovo_riferimento];
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
        // Precaricamento Documento
        static::addGlobalScope('parent', function (Builder $builder) {
            $builder->with('parent');
        });

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
