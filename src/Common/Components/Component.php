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
use Common\RowReference;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Classe dedicata alla gestione delle informazioni di base dei componenti dei Documenti, e in particolare di:
 * - Collegamento con il Documento do origine
 * - Riferimento di origine
 * - Riferimenti informativi tra righe di altri documenti
 * - Importazione tra documenti distinti di un componente.
 *
 * @property string original_type
 * @property string original_id
 *
 * @template T
 *
 * @since 2.4.18
 */
abstract class Component extends Model
{
    /**
     * Componente di origine da cui il compontente corrente deriva.
     *
     * @var Component|null
     */
    protected $original_model = null;

    protected $guarded = [];

    protected $appends = [
        'max_qta',
    ];

    protected $hidden = [
        'document',
    ];

    public function hasOriginalComponent()
    {
        return !empty($this->original_type) && !empty($this->getOriginalComponent());
    }

    public function getOriginalComponent()
    {
        if (!isset($this->original_model) && !empty($this->original_type)) {
            $class = $this->original_type;

            $this->original_model = $class::find($this->original_id);
        }

        return $this->original_model;
    }

    public function referenceSources()
    {
        $class = get_class($this);

        return $this->hasMany(RowReference::class, 'target_id')
            ->where('target_type', $class);
    }

    public function referenceTargets()
    {
        $class = get_class($this);

        return $this->hasMany(RowReference::class, 'source_id')
            ->where('source_type', $class);
    }

    /**
     * Restituisce l'eventuale limite sulla quantità massima derivato dal componente di origine.
     *
     * @return mixed|null
     */
    public function getMaxQtaAttribute()
    {
        if (!$this->hasOriginalComponent()) {
            return null;
        }

        $original = $this->getOriginalComponent();

        return $original->qta_rimanente + $this->qta;
    }

    /**
     * Modifica la quantità del componente.
     *
     * @param float $value
     *
     * @return float
     */
    public function setQtaAttribute($value)
    {
        list($qta, $diff) = $this->parseQta($value);
        $this->attributes['qta'] = $qta;

        // Aggiornamento della quantità evasa di origine
        if ($this->hasOriginalComponent()) {
            $original = $this->getOriginalComponent();

            $original->qta_evasa += $diff;
            $original->save();
        }

        return $diff;
    }

    /**
     * Restituisce la quantità rimanente del componente.
     *
     * @return float
     */
    public function getQtaRimanenteAttribute()
    {
        return $this->qta - $this->qta_evasa;
    }

    /**
     * Gestisce la possibilità di eliminare il componente.
     *
     * @return bool
     */
    public function canDelete()
    {
        return true;
    }

    public function delete()
    {
        if (!$this->canDelete()) {
            throw new InvalidArgumentException();
        }

        if ($this->hasOriginalComponent()) {
            $original = $this->getOriginalComponent();
        }

        $this->qta = 0;
        $result = parent::delete();

        // Trigger per la modifica delle righe
        $this->getDocument()->triggerComponent($this);

        // Trigger per l'evasione delle quantità
        if ($this->hasOriginalComponent()) {
            $original->getDocument()->triggerEvasione($this);
        }

        // Ordine delle righe successivamente alla rimozione
        if (empty($this->disableOrder)) {
            reorderRows($this->table, $this->getDocumentID(), $this->getDocument()['id']);
        }

        return $result;
    }

    /**
     * Copia l'oggetto (articolo, riga, descrizione) nel corrispettivo per il documento indicato.
     *
     * @param Document      $document           Documento di destinazione
     * @param float|null    $qta                Quantità da riportare
     * @param boolean       $evadi_qta_parent   Definisce se evadere la quantità di provenienza 
     *
     * @return self
     */
    public function copiaIn(Document $document, $qta = null, $evadi_qta_parent = true)
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
        unset($attributes[$model->getDocumentID()]);

        // Riferimento di origine per l'evasione automatica della riga
        if ($evadi_qta_parent) {
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
        $model->setDocument($document);

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
     * Imposta l'origine del componente, restituendo un array contenente i replace da effettuare per modificare la descrizione in modo coerente.
     *
     * @param string $type
     * @param string $id
     *
     * @return array
     */
    public function impostaOrigine($type, $id)
    {
        $riferimento_precedente = null;
        $nuovo_riferimento = null;

        // Rimozione del riferimento precedente dalla descrizione
        if ($this->hasOriginalComponent()) {
            $riferimento = $this->getOriginalComponent()->getDocument()->getReference();
            $riferimento_precedente = "\nRif. ".strtolower($riferimento);
        }

        $this->original_id = $id;
        $this->original_type = $type;

        // Aggiunta del riferimento nella descrizione
        $origine = $type::find($id);
        if (!empty($origine)) {
            $riferimento = $origine->getDocument()->getReference();
            $nuovo_riferimento = "\nRif. ".strtolower($riferimento);
        }

        return [$riferimento_precedente, $nuovo_riferimento];
    }

    /**
     * Imposta il proprietario dell'oggetto e l'ordine relativo all'interno delle righe.
     *
     * @param Document $document Documento di riferimento
     * @psalm-param T $document
     */
    public function setDocument(Document $document)
    {
        $this->document()->associate($document);

        // Ordine delle righe
        if (empty($this->disableOrder)) {
            $this->order = orderValue($this->table, $this->getDocumentID(), $document->id);
        }
    }

    /**
     * @return Document
     * @psalm-return T
     */
    public function getDocument()
    {
        return $this->document;
    }

    abstract public function document();

    /**
     * @return string
     */
    abstract public function getDocumentID();

    public function save(array $options = [])
    {
        $result = parent::save($options);

        // Trigger per la modifica delle righe
        $this->getDocument()->triggerComponent($this);

        // Trigger per l'evasione delle quantità
        if ($this->hasOriginalComponent()) {
            $original = $this->getOriginalComponent();

            $original->getDocument()->triggerEvasione($this);
        }

        return $result;
    }

    public function replicate(array $except = null)
    {
        $new = parent::replicate($except);

        $new->qta_evasa = 0;
        $new->original_type = null;
        $new->original_id = null;

        return $new;
    }

    /**
     * Verifica e calcola quantità e differenziale delle quantità.
     *
     * @param $value
     *
     * @return array [nuova quantità, differenza rispetto alla quantità precedente]
     */
    protected function parseQta($value)
    {
        $previous = $this->qta;
        $diff = $value - $previous;

        // Controlli su eventuale massimo per la quantità
        if ($this->hasOriginalComponent()) {
            $original = $this->getOriginalComponent();

            // Controllo per evitare di superare la quantità totale del componente di origine
            if ($original->qta_rimanente < $diff) {
                $diff = $original->qta_rimanente;
                $value = $previous + $diff;
            }
        }

        return [$value, $diff];
    }

    /**
     * Azione personalizzata per la copia dell'oggetto (inizializzazione della copia).
     *
     * @param $original
     */
    protected function customInitCopiaIn($original)
    {
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

    protected static function boot()
    {
        // Pre-caricamento Documento
        static::addGlobalScope('document', function (Builder $builder) {
            $builder->with('document');
        });

        parent::boot();
    }
}
